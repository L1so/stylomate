from fastapi import APIRouter, Depends, HTTPException, UploadFile, File, Form, Response
from fastapi.responses import FileResponse
from sqlalchemy import select, delete, case
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import Session
from app.database import get_db
from app.models import User, WardrobeItem, CatalogItem, Outfit, OutfitItem, TryOnResult
from app.schemas import Register, Login, OutfitCreate, SaveResult, Category, Style
from app.services.security import current_user, passwords, issue_token
from app.services.ai import ai_headers, generate_zerogpu, probe_ai, wait_for_ai
from app.services.images import MAX_BYTES, normalize_image, store_image, image_path, remove_image
from app.config import settings
import httpx
from datetime import datetime, timedelta, timezone

router = APIRouter(prefix='/api')

def owned(db, model, identity, user):
    obj = db.get(model, identity)
    if obj is None or obj.user_id != user.id:
        raise HTTPException(404, 'Resource not found')
    if model is TryOnResult and not obj.saved:
        created = obj.created_at.replace(tzinfo=timezone.utc) if obj.created_at.tzinfo is None else obj.created_at
        if created < datetime.now(timezone.utc) - timedelta(hours=1):
            raise HTTPException(410, 'Preview expired; generate a new result')
    return obj

def profile(user):
    return {'id': user.id, 'name': user.name, 'email': user.email}

def garment(item, kind):
    result = {k: getattr(item, k) for k in ('id', 'name', 'category', 'color', 'style')}
    result['image_url'] = f'/api/{kind}/{item.id}/image'
    if kind == 'catalog':
        result.update(brand=item.brand, product_url=item.product_url)
    return result

def outfit_data(db, outfit):
    category_order = case(
        (WardrobeItem.category == 'top', 1),
        (WardrobeItem.category == 'bottom', 2),
        (WardrobeItem.category == 'shoes', 3),
        (WardrobeItem.category == 'accessory', 4),
        else_=99,
    )

    query = (
        select(OutfitItem.wardrobe_item_id)
        .join(
            WardrobeItem,
            WardrobeItem.id == OutfitItem.wardrobe_item_id,
        )
        .where(OutfitItem.outfit_id == outfit.id)
        .order_by(category_order, OutfitItem.id)
    )

    ids = list(db.scalars(query))

    return {
        'id': outfit.id,
        'name': outfit.name,
        'wardrobe_item_ids': ids,
    }

def result_data(result):
    return {'generation_id': result.id, 'id': result.id, 'saved': result.saved, 'image_url': f'/api/try-on/{result.id}/image', 'wardrobe_item_id': result.wardrobe_item_id, 'catalog_item_id': result.catalog_item_id}

@router.post('/auth/register', status_code=201)
def register(body: Register, db: Session = Depends(get_db)):
    user = User(name=body.name, email=str(body.email).lower(), password_hash=passwords.hash(body.password))
    db.add(user)
    try:
        db.commit()
    except IntegrityError:
        db.rollback()
        raise HTTPException(409, 'Email already registered')
    return {'data': profile(user)}

@router.post('/auth/login')
def login(body: Login, db: Session = Depends(get_db)):
    user = db.scalar(select(User).where(User.email == str(body.email).lower()))
    if user is None or not passwords.verify(body.password, user.password_hash):
        raise HTTPException(401, 'Invalid email or password')
    return {'data': {'access_token': issue_token(user), 'token_type': 'bearer', 'expires_in': settings.access_token_expire_minutes * 60, 'user': profile(user)}}

@router.get('/auth/me')
def me(user: User = Depends(current_user)):
    return {'data': profile(user)}

@router.get('/catalog')
def catalog(q: str = '', category: Category | None = None, db: Session = Depends(get_db)):
    query = select(CatalogItem).order_by(CatalogItem.id)
    if q:
        query = query.where(CatalogItem.name.ilike('%' + q[:100] + '%'))
    if category:
        query = query.where(CatalogItem.category == category)
    return {'data': [garment(i, 'catalog') for i in db.scalars(query)]}

def catalog_item(db, identity):
    item = db.get(CatalogItem, identity)
    if item is None:
        raise HTTPException(404, 'Resource not found')
    return item

@router.get('/catalog/{identity}')
def catalog_detail(identity: int, db: Session = Depends(get_db)):
    return {'data': garment(catalog_item(db, identity), 'catalog')}

@router.get('/catalog/{identity}/image')
def catalog_image(identity: int, db: Session = Depends(get_db)):
    return FileResponse(image_path(catalog_item(db, identity).image_path), media_type='image/jpeg')

@router.get('/wardrobe')
def wardrobe(db: Session = Depends(get_db), user: User = Depends(current_user)):
    return {'data': [garment(i, 'wardrobe') for i in db.scalars(select(WardrobeItem).where(WardrobeItem.user_id == user.id).order_by(WardrobeItem.id))]}

@router.post('/wardrobe', status_code=201)
def add_wardrobe(name: str = Form(min_length=1, max_length=100), category: Category = Form(), color: str = Form(min_length=1, max_length=30), style: Style = Form(), image: UploadFile = File(), db: Session = Depends(get_db), user: User = Depends(current_user)):
    path = store_image(normalize_image(image.file.read(MAX_BYTES + 1)))
    item = WardrobeItem(user_id=user.id, name=name, category=category, color=color.lower(), style=style, image_path=path)
    db.add(item)
    try:
        db.commit()
    except Exception:
        db.rollback()
        remove_image(path)
        raise
    return {'data': garment(item, 'wardrobe')}

@router.get('/wardrobe/{identity}')
def wardrobe_detail(identity: int, db: Session = Depends(get_db), user: User = Depends(current_user)):
    return {'data': garment(owned(db, WardrobeItem, identity, user), 'wardrobe')}

@router.get('/wardrobe/{identity}/image')
def wardrobe_image(identity: int, db: Session = Depends(get_db), user: User = Depends(current_user)):
    return FileResponse(image_path(owned(db, WardrobeItem, identity, user).image_path), media_type='image/jpeg', headers={'Cache-Control': 'private, no-store'})

@router.patch('/wardrobe/{identity}')
def update_wardrobe(identity: int, name: str | None = Form(None, min_length=1, max_length=100), category: Category | None = Form(None), color: str | None = Form(None, min_length=1, max_length=30), style: Style | None = Form(None), image: UploadFile | None = File(None), db: Session = Depends(get_db), user: User = Depends(current_user)):
    item = owned(db, WardrobeItem, identity, user)
    old = item.image_path
    for key, value in {'name': name, 'category': category, 'color': color.lower() if color else color, 'style': style}.items():
        if value is not None:
            setattr(item, key, value)
    if image:
        item.image_path = store_image(normalize_image(image.file.read(MAX_BYTES + 1)))
    new = item.image_path
    try:
        db.commit()
    except Exception:
        db.rollback()
        if new != old:
            remove_image(new)
        raise
    if new != old:
        remove_image(old)
    return {'data': garment(item, 'wardrobe')}

@router.delete('/wardrobe/{identity}', status_code=204)
def delete_wardrobe(identity: int, db: Session = Depends(get_db), user: User = Depends(current_user)):
    item = owned(db, WardrobeItem, identity, user)
    if db.scalar(select(OutfitItem.id).where(OutfitItem.wardrobe_item_id == identity)) or db.scalar(select(TryOnResult.id).where(TryOnResult.wardrobe_item_id == identity)):
        raise HTTPException(409, 'Delete related outfits and try-on results first')
    path = item.image_path
    db.delete(item)
    db.commit()
    remove_image(path)
    return Response(status_code=204)

@router.get('/outfits')
def outfits(db: Session = Depends(get_db), user: User = Depends(current_user)):
    return {'data': [outfit_data(db, i) for i in db.scalars(select(Outfit).where(Outfit.user_id == user.id).order_by(Outfit.id))]}

@router.post('/outfits', status_code=201)
def add_outfit(body: OutfitCreate, db: Session = Depends(get_db), user: User = Depends(current_user)):
    unique_ids = list(dict.fromkeys(body.wardrobe_item_ids))

    items = [
        owned(db, WardrobeItem, identity, user)
        for identity in unique_ids
    ]
    if not {'top', 'bottom'} <= {i.category for i in items}:
        raise HTTPException(422, 'An outfit requires a top and a bottom')
    outfit = Outfit(user_id=user.id, name=body.name)
    db.add(outfit)
    db.flush()
    db.add_all([OutfitItem(outfit_id=outfit.id, wardrobe_item_id=i.id) for i in items])
    db.commit()
    return {'data': outfit_data(db, outfit)}

@router.delete('/outfits/{identity}', status_code=204)
def delete_outfit(identity: int, db: Session = Depends(get_db), user: User = Depends(current_user)):
    outfit = owned(db, Outfit, identity, user)
    db.execute(delete(OutfitItem).where(OutfitItem.outfit_id == identity))
    db.delete(outfit)
    db.commit()
    return Response(status_code=204)

@router.get('/recommendations')
def recommendations(style: Style = 'casual', db: Session = Depends(get_db), user: User = Depends(current_user)):
    items = list(db.scalars(select(WardrobeItem).where(WardrobeItem.user_id == user.id, WardrobeItem.style == style).order_by(WardrobeItem.id)))
    neutral = {'black', 'white', 'gray', 'grey', 'beige', 'navy', 'hitam', 'putih', 'abu-abu', 'krem'}
    pairs = [(a, b) for a in items if a.category == 'top' for b in items if b.category == 'bottom']
    pairs.sort(key=lambda pair: (not (pair[0].color == pair[1].color or pair[0].color in neutral or pair[1].color in neutral), pair[0].id, pair[1].id))
    return {'data': [{'wardrobe_item_ids': [a.id, b.id], 'items': [garment(a, 'wardrobe'), garment(b, 'wardrobe')]} for a, b in pairs[:5]], 'meta': {'style': style, 'message': None if pairs else 'Add a top and bottom matching this style'}}

@router.post('/try-on/warmup', status_code=204)
def warmup(user: User = Depends(current_user)):
    with httpx.Client() as client:
        try:
            probe_ai(client, 3)
        except httpx.HTTPStatusError:
            pass
    return Response(status_code=204)

@router.post('/try-on/generate', status_code=201)
def generate(person_image: UploadFile = File(), wardrobe_item_id: int | None = Form(None), catalog_item_id: int | None = Form(None), db: Session = Depends(get_db), user: User = Depends(current_user)):
    if (wardrobe_item_id is None) == (catalog_item_id is None):
        raise HTTPException(422, 'Choose exactly one garment source')
    item = owned(db, WardrobeItem, wardrobe_item_id, user) if wardrobe_item_id is not None else catalog_item(db, catalog_item_id)
    if item.category != 'top':
        raise HTTPException(422, 'MVP try-on supports tops only')
    person = normalize_image(person_image.file.read(MAX_BYTES + 1))
    clothes = image_path(item.image_path).read_bytes()
    try:
        with httpx.Client(timeout=httpx.Timeout(settings.ai_timeout_seconds, connect=5)) as client:
            if settings.ai_cold_start_timeout_seconds:
                wait_for_ai(client)
            if settings.ai_hf_zerogpu:
                data = generate_zerogpu(client, person, clothes, MAX_BYTES)
            else:
                with client.stream('POST', settings.ai_service_url.rstrip('/') + '/generate', files={'person_image': ('person.jpg', person, 'image/jpeg'), 'garment_image': ('garment.jpg', clothes, 'image/jpeg')}, data={'category': 'upper-body'}, headers=ai_headers()) as response:
                    response.raise_for_status()
                    data = bytearray()
                    for chunk in response.iter_bytes():
                        data.extend(chunk)
                        if len(data) > MAX_BYTES:
                            raise HTTPException(502, 'AI returned an oversized image')
        try:
            normalized = normalize_image(bytes(data))
        except HTTPException:
            raise HTTPException(502, 'AI returned an invalid image')
    except httpx.TimeoutException:
        raise HTTPException(504, 'AI service timed out; please retry')
    except httpx.HTTPError:
        raise HTTPException(503, 'AI service unavailable; please retry')
    path = store_image(normalized)
    result = TryOnResult(user_id=user.id, wardrobe_item_id=wardrobe_item_id, catalog_item_id=catalog_item_id, result_image_path=path, saved=False)
    db.add(result)
    try:
        db.commit()
    except Exception:
        db.rollback()
        remove_image(path)
        raise
    return {'data': result_data(result)}

@router.post('/try-on/save')
def save_result(body: SaveResult, db: Session = Depends(get_db), user: User = Depends(current_user)):
    result = owned(db, TryOnResult, body.generation_id, user)
    result.saved = True
    db.commit()
    return {'data': result_data(result)}

@router.get('/try-on/history')
def history(db: Session = Depends(get_db), user: User = Depends(current_user)):
    return {'data': [result_data(i) for i in db.scalars(select(TryOnResult).where(TryOnResult.user_id == user.id, TryOnResult.saved.is_(True)).order_by(TryOnResult.id.desc()))]}

@router.get('/try-on/{identity}/image')
def result_image(identity: int, db: Session = Depends(get_db), user: User = Depends(current_user)):
    return FileResponse(image_path(owned(db, TryOnResult, identity, user).result_image_path), media_type='image/jpeg', headers={'Cache-Control': 'private, no-store'})

@router.delete('/try-on/{identity}', status_code=204)
def delete_result(identity: int, db: Session = Depends(get_db), user: User = Depends(current_user)):
    result = owned(db, TryOnResult, identity, user)
    path = result.result_image_path
    db.delete(result)
    db.commit()
    remove_image(path)
    return Response(status_code=204)
