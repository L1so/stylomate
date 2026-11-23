from base64 import b64encode
from io import BytesIO
import json
from datetime import datetime, timedelta, timezone
import jwt
import httpx
import pytest
from PIL import Image
from fastapi.testclient import TestClient
from app.main import app
from app.config import settings

client = TestClient(app, backend_options={'use_uvloop': True})

def picture():
    output = BytesIO()
    Image.new('RGB', (12, 12), 'red').save(output, 'PNG')
    return output.getvalue()

def auth(email='alice@example.com'):
    body = {'name': 'Alice', 'email': email, 'password': 'password123'}
    assert client.post('/api/auth/register', json=body).status_code == 201
    response = client.post('/api/auth/login', json=body)
    assert response.status_code == 200
    return {'Authorization': 'Bearer ' + response.json()['data']['access_token']}

def add(headers, category='top', style='casual'):
    response = client.post('/api/wardrobe', headers=headers, data={'name': 'Shirt', 'category': category, 'color': 'white', 'style': style}, files={'image': ('test.png', picture(), 'image/png')})
    assert response.status_code == 201, response.text
    return response.json()['data']['id']

def test_auth_validation_and_expiry():
    headers = auth()
    assert client.get('/api/auth/me', headers=headers).status_code == 200
    assert client.get('/api/auth/me').status_code == 401
    assert client.post('/api/auth/login', json={'email': 'alice@example.com', 'password': 'wrong'}).status_code == 401
    assert client.post('/api/auth/register', json={'name': 'A', 'email': 'ALICE@example.com', 'password': 'password123'}).status_code == 409
    token = jwt.encode({'sub': '1', 'exp': datetime.now(timezone.utc) - timedelta(seconds=1)}, settings.jwt_secret_key, algorithm='HS256')
    assert client.get('/api/auth/me', headers={'Authorization': 'Bearer ' + token}).status_code == 401
    invalid = client.post('/api/auth/register', json={'name': 'A', 'email': 'invalid', 'password': 'secret'})
    assert invalid.status_code == 422 and 'secret' not in invalid.text

def test_try_on_wakes_private_host_and_waits_before_upload(monkeypatch):
    headers = auth()
    identity = add(headers)
    monkeypatch.setattr(settings, 'ai_hf_space_token', 'gateway-test')
    monkeypatch.setattr(settings, 'ai_service_token', 'worker-test')
    monkeypatch.setattr(settings, 'ai_cold_start_timeout_seconds', 10)
    monkeypatch.setattr('app.services.ai.sleep', lambda _: None)
    original = httpx.Client
    requests = []
    responses = iter([
        httpx.Response(503),
        httpx.Response(200, text='Space is starting'),
        httpx.Response(200, json={'status': 'ok'}),
        httpx.Response(200, content=picture()),
    ])

    def respond(request):
        requests.append((request.method, request.url.path))
        assert request.headers['Authorization'] == 'Bearer gateway-test'
        assert request.headers['X-StyloMate-Token'] == 'worker-test'
        return next(responses)

    monkeypatch.setattr('app.routers.api.httpx.Client',
                        lambda **kwargs: original(transport=httpx.MockTransport(respond), **kwargs))
    assert client.post('/api/try-on/warmup').status_code == 401
    assert client.post('/api/try-on/warmup', headers=headers).status_code == 204
    response = client.post('/api/try-on/generate', headers=headers, data={'wardrobe_item_id': identity},
                           files={'person_image': ('p.png', picture(), 'image/png')})
    assert response.status_code == 201, response.text
    assert requests == [('GET', '/health')] * 3 + [('POST', '/generate')]

def test_try_on_does_not_upload_on_failed_host_auth(monkeypatch):
    headers = auth()
    identity = add(headers)
    monkeypatch.setattr(settings, 'ai_cold_start_timeout_seconds', 10)
    original = httpx.Client

    def respond(request):
        assert request.method == 'GET' and request.url.path == '/health'
        return httpx.Response(401)

    monkeypatch.setattr('app.routers.api.httpx.Client',
                        lambda **kwargs: original(transport=httpx.MockTransport(respond), **kwargs))
    response = client.post('/api/try-on/generate', headers=headers, data={'wardrobe_item_id': identity},
                           files={'person_image': ('p.png', picture(), 'image/png')})
    assert response.status_code == 503

def test_try_on_zerogpu_submits_once_and_reads_completion(monkeypatch):
    headers = auth()
    identity = add(headers)
    monkeypatch.setattr(settings, 'ai_hf_zerogpu', True)
    monkeypatch.setattr(settings, 'ai_hf_space_token', 'gateway-test')
    original = httpx.Client
    requests = []
    endpoint = '/gradio_api/call/generate'
    output = b64encode(picture()).decode()

    def respond(request):
        assert request.headers['Authorization'] == 'Bearer gateway-test'
        requests.append((request.method, request.url.path))
        if request.method == 'POST':
            person, garment, category = json.loads(request.content)['data']
            assert person and garment and category == 'upper-body'
            return httpx.Response(200, json={'event_id': 'queue-test-1'})
        return httpx.Response(200, text=f'event: heartbeat\ndata: null\n\nevent: complete\ndata: ["{output}"]\n\n')

    monkeypatch.setattr('app.routers.api.httpx.Client',
                        lambda **kwargs: original(transport=httpx.MockTransport(respond), **kwargs))
    response = client.post('/api/try-on/generate', headers=headers, data={'wardrobe_item_id': identity},
                           files={'person_image': ('p.png', picture(), 'image/png')})
    assert response.status_code == 201, response.text
    assert requests == [('POST', endpoint), ('GET', endpoint + '/queue-test-1')]

def test_wardrobe_ownership_upload_and_update():
    alice, bob = auth(), auth('bob@example.com')
    identity = add(alice)
    for suffix in ('', '/image'):
        assert client.get(f'/api/wardrobe/{identity}{suffix}', headers=bob).status_code == 404
    assert client.patch(f'/api/wardrobe/{identity}', headers=bob, data={'name': 'stolen'}).status_code == 404
    assert client.delete(f'/api/wardrobe/{identity}', headers=bob).status_code == 404
    assert client.get('/api/wardrobe', headers=bob).json()['data'] == []
    assert client.patch(f'/api/wardrobe/{identity}', headers=alice, data={'name': 'Updated'}).json()['data']['name'] == 'Updated'
    assert client.get(f'/api/wardrobe/{identity}/image', headers=alice).status_code == 200
    bad = client.post('/api/wardrobe', headers=alice, data={'name': 'X', 'category': 'top', 'color': 'red', 'style': 'casual'}, files={'image': ('x.png', b'not an image', 'image/png')})
    assert bad.status_code == 422
    assert client.delete(f'/api/wardrobe/{identity}', headers=alice).status_code == 204

def test_outfits_and_recommendations():
    alice, bob = auth(), auth('bob@example.com')
    top, bottom = add(alice), add(alice, 'bottom')
    other = add(bob)
    assert client.post('/api/outfits', headers=alice, json={'name': 'No', 'wardrobe_item_ids': [other, bottom]}).status_code == 404
    response = client.post('/api/outfits', headers=alice, json={'name': 'Look', 'wardrobe_item_ids': [top, bottom]})
    assert response.status_code == 201
    identity = response.json()['data']['id']
    assert client.delete(f'/api/outfits/{identity}', headers=bob).status_code == 404
    assert client.delete(f'/api/wardrobe/{top}', headers=alice).status_code == 409
    assert len(client.get('/api/recommendations', headers=alice).json()['data']) == 1
    assert client.get('/api/recommendations?style=formal', headers=alice).json()['data'] == []
    assert client.delete(f'/api/outfits/{identity}', headers=alice).status_code == 204
    assert client.delete(f'/api/wardrobe/{top}', headers=alice).status_code == 204

def test_try_on_generate_save_and_access(monkeypatch):
    alice, bob = auth(), auth('bob@example.com')
    identity = add(alice)
    original = httpx.Client
    def factory(**kwargs):
        return original(transport=httpx.MockTransport(lambda request: httpx.Response(200, content=picture())), **kwargs)
    monkeypatch.setattr('app.routers.api.httpx.Client', factory)
    response = client.post('/api/try-on/generate', headers=alice, data={'wardrobe_item_id': identity}, files={'person_image': ('p.png', picture(), 'image/png')})
    assert response.status_code == 201, response.text
    generation = response.json()['data']['generation_id']
    assert client.get('/api/try-on/history', headers=alice).json()['data'] == []
    assert client.post('/api/try-on/save', headers=bob, json={'generation_id': generation}).status_code == 404
    assert client.get(f'/api/try-on/{generation}/image', headers=bob).status_code == 404
    assert client.post('/api/try-on/save', headers=alice, json={'generation_id': generation}).status_code == 200
    assert len(client.get('/api/try-on/history', headers=alice).json()['data']) == 1
    assert client.delete(f'/api/try-on/{generation}', headers=alice).status_code == 204

def test_lower_body_and_full_outfit_run_expected_ai_stages(monkeypatch):
    alice, bob = auth(), auth('bob@example.com')
    top = add(alice, 'top')
    bottom = add(alice, 'bottom')
    outfit_response = client.post('/api/outfits', headers=alice, json={
        'name': 'Full Look',
        'wardrobe_item_ids': [top, bottom],
    })
    assert outfit_response.status_code == 201
    outfit_id = outfit_response.json()['data']['id']

    original = httpx.Client
    categories = []

    def respond(request):
        body = request.content
        categories.append('lower-body' if b'lower-body' in body else 'upper-body')
        return httpx.Response(200, content=picture())

    monkeypatch.setattr(
        'app.routers.api.httpx.Client',
        lambda **kwargs: original(transport=httpx.MockTransport(respond), **kwargs),
    )

    lower = client.post(
        '/api/try-on/generate',
        headers=alice,
        data={'wardrobe_item_id': bottom},
        files={'person_image': ('p.png', picture(), 'image/png')},
    )
    assert lower.status_code == 201, lower.text
    assert lower.json()['data']['wardrobe_item_id'] == bottom

    full = client.post(
        '/api/try-on/generate',
        headers=alice,
        data={'outfit_id': outfit_id},
        files={'person_image': ('p.png', picture(), 'image/png')},
    )
    assert full.status_code == 201, full.text
    assert full.json()['data']['outfit_id'] == outfit_id
    assert full.json()['data']['wardrobe_item_id'] is None
    assert full.json()['data']['catalog_item_id'] is None
    assert categories == ['lower-body', 'lower-body', 'upper-body']

    foreign = client.post(
        '/api/try-on/generate',
        headers=bob,
        data={'outfit_id': outfit_id},
        files={'person_image': ('p.png', picture(), 'image/png')},
    )
    assert foreign.status_code == 404

def test_ai_failure(monkeypatch):
    headers = auth()
    identity = add(headers)
    original = httpx.Client
    monkeypatch.setattr('app.routers.api.httpx.Client', lambda **kwargs: original(transport=httpx.MockTransport(lambda r: httpx.Response(500)), **kwargs))
    response = client.post('/api/try-on/generate', headers=headers, data={'wardrobe_item_id': identity}, files={'person_image': ('p.png', picture(), 'image/png')})
    assert response.status_code == 503


@pytest.mark.parametrize('zero_gpu', [False, True])
def test_ai_reports_missing_lower_body_as_a_photo_validation_error(monkeypatch, zero_gpu):
    headers = auth()
    identity = add(headers, 'bottom')
    original = httpx.Client
    monkeypatch.setattr(settings, 'ai_hf_zerogpu', zero_gpu)

    def respond(request):
        if not zero_gpu:
            return httpx.Response(422, headers={'X-StyloMate-Error': 'lower_body_not_visible'})
        if request.method == 'POST':
            return httpx.Response(200, json={'event_id': 'test'})
        return httpx.Response(200, content='event: error\ndata: {"error": "lower_body_not_visible"}\n\n')

    monkeypatch.setattr(
        'app.routers.api.httpx.Client',
        lambda **kwargs: original(transport=httpx.MockTransport(respond), **kwargs),
    )
    response = client.post(
        '/api/try-on/generate', headers=headers, data={'wardrobe_item_id': identity},
        files={'person_image': ('p.png', picture(), 'image/png')},
    )
    assert response.status_code == 422
    assert response.json()['error']['fields'][0]['field'] == 'person_image'

def test_preview_expiry_cleanup_and_saved_retention(database, monkeypatch):
    from sqlalchemy.orm import Session
    from app.models import TryOnResult
    from app.services.images import store_image, image_path
    from app.maintenance import cleanup_previews
    headers = auth()
    identity = add(headers)
    expired_path = store_image(picture())
    saved_path = store_image(picture())
    with Session(database) as db:
        expired = TryOnResult(user_id=1, wardrobe_item_id=identity, result_image_path=expired_path, saved=False, created_at=datetime.now(timezone.utc) - timedelta(hours=2))
        saved = TryOnResult(user_id=1, wardrobe_item_id=identity, result_image_path=saved_path, saved=True, created_at=datetime.now(timezone.utc) - timedelta(hours=2))
        db.add_all([expired, saved])
        db.commit()
        expired_id, saved_id = expired.id, saved.id
    assert client.post('/api/try-on/save', headers=headers, json={'generation_id': expired_id}).status_code == 410
    assert client.get(f'/api/try-on/{expired_id}/image', headers=headers).status_code == 410
    monkeypatch.setattr('app.maintenance.SessionLocal', lambda: Session(database))
    cleanup_previews()
    assert client.get(f'/api/try-on/{expired_id}/image', headers=headers).status_code == 404
    assert client.get(f'/api/try-on/{saved_id}/image', headers=headers).status_code == 200
    assert image_path(saved_path).is_file()

def test_catalog_seed_is_idempotent(database, monkeypatch):
    from sqlalchemy.orm import Session
    from app.maintenance import seed_catalog
    monkeypatch.setattr('app.maintenance.SessionLocal', lambda: Session(database))
    seed_catalog()
    seed_catalog()
    items = client.get('/api/catalog').json()['data']
    assert len(items) == 3
    assert client.get(items[0]['image_url']).status_code == 200
    assert len(client.get('/api/catalog?category=bottom').json()['data']) == 1

def test_catalog_pagination(database, monkeypatch):
    from sqlalchemy.orm import Session
    from app.maintenance import seed_catalog
    monkeypatch.setattr('app.maintenance.SessionLocal', lambda: Session(database))
    seed_catalog()
    first = client.get('/api/catalog?limit=2').json()
    assert len(first['data']) == 2
    assert first['meta'] == {'total': 3, 'limit': 2, 'offset': 0}
    second = client.get('/api/catalog?limit=2&offset=2').json()
    assert len(second['data']) == 1
    assert second['meta']['total'] == 3
    assert client.get('/api/catalog?category=bottom&limit=1').json()['meta']['total'] == 1
    assert client.get('/api/catalog?limit=49').status_code == 422
    assert client.get('/api/catalog?offset=-1').status_code == 422
    for item in first['data'] + second['data']:
        assert item['thumbnail_url'].startswith(f"/api/catalog/{item['id']}/image?size=thumb&v=")
        assert item['image_url'].startswith(f"/api/catalog/{item['id']}/image?v=")

def test_private_lists_are_paginated():
    alice, bob = auth(), auth('bob@example.com')
    top, bottom = add(alice), add(alice, 'bottom')
    add(alice)
    first = client.get('/api/wardrobe?limit=2', headers=alice).json()
    assert len(first['data']) == 2
    assert first['meta'] == {'total': 3, 'limit': 2, 'offset': 0}
    assert len(client.get('/api/wardrobe?limit=2&offset=2', headers=alice).json()['data']) == 1
    assert client.get('/api/wardrobe?limit=101', headers=alice).status_code == 422
    assert client.get('/api/wardrobe', headers=alice).json()['meta']['limit'] == 100
    assert client.get('/api/wardrobe', headers=bob).json() == {'data': [], 'meta': {'total': 0, 'limit': 100, 'offset': 0}}
    for name in ('Look', 'Second look'):
        assert client.post('/api/outfits', headers=alice, json={'name': name, 'wardrobe_item_ids': [top, bottom]}).status_code == 201
    outfits = client.get('/api/outfits?limit=1', headers=alice).json()
    assert len(outfits['data']) == 1
    assert outfits['meta'] == {'total': 2, 'limit': 1, 'offset': 0}
    assert client.get('/api/try-on/history', headers=alice).json() == {'data': [], 'meta': {'total': 0, 'limit': 100, 'offset': 0}}

def test_wardrobe_thumbnail_is_private_and_resized():
    alice, bob = auth(), auth('bob@example.com')
    output = BytesIO()
    Image.new('RGB', (1200, 1600), 'white').save(output, 'JPEG')
    response = client.post('/api/wardrobe', headers=alice, data={'name': 'Big', 'category': 'top', 'color': 'white', 'style': 'casual'}, files={'image': ('big.jpg', output.getvalue(), 'image/jpeg')})
    assert response.status_code == 201, response.text
    identity = response.json()['data']['id']
    thumb = client.get(f'/api/wardrobe/{identity}/image?size=thumb', headers=alice)
    assert thumb.status_code == 200
    assert thumb.headers['cache-control'] == 'private, no-store'
    with Image.open(BytesIO(thumb.content)) as im:
        assert im.width <= 600 and im.height <= 800
    assert client.get(f'/api/wardrobe/{identity}/image?size=thumb').status_code == 401
    assert client.get(f'/api/wardrobe/{identity}/image?size=thumb', headers=bob).status_code == 404

def test_thumbnail_falls_back_to_original_when_source_is_broken():
    from pathlib import Path
    from app.services.images import store_image, thumbnail_path, image_path
    name = store_image(b'not an image')
    assert thumbnail_path(name) == image_path(name)
    storage = Path(settings.storage_dir)
    stem = Path(name).stem
    assert not (storage / f'{stem}.thumb.jpg').exists()
    assert not list(storage.glob(f'{stem}*.tmp'))

def test_catalog_thumbnail_is_resized_cached_and_removed(database, monkeypatch):
    from pathlib import Path
    from sqlalchemy.orm import Session
    from app.models import CatalogItem
    from app.services.images import store_image, remove_image
    output = BytesIO()
    Image.new('RGB', (1200, 1600), 'white').save(output, 'JPEG')
    path = store_image(output.getvalue())
    with Session(database) as db:
        item = CatalogItem(name='Big', category='top', color='white', style='casual', image_path=path)
        db.add(item)
        db.commit()
        identity = item.id
    storage = Path(settings.storage_dir)
    original = storage / path
    thumb = storage / (Path(path).stem + '.thumb.jpg')
    response = client.get(f'/api/catalog/{identity}/image?size=thumb')
    assert response.status_code == 200
    assert response.headers['cache-control'] == 'public, max-age=86400'
    with Image.open(BytesIO(response.content)) as im:
        assert im.width <= 600 and im.height <= 800
    assert thumb.is_file()
    full = client.get(f'/api/catalog/{identity}/image')
    assert full.status_code == 200
    with Image.open(BytesIO(full.content)) as im:
        assert im.size == (1200, 1600)
    remove_image(path)
    assert not original.exists()
    assert not thumb.exists()
    assert client.get(f'/api/catalog/{identity}/image?size=other').status_code == 422

def test_upload_limit_and_ai_timeout(monkeypatch):
    headers = auth()
    identity = add(headers)
    oversized = client.post('/api/wardrobe', headers=headers, data={'name': 'X', 'category': 'top', 'color': 'red', 'style': 'casual'}, files={'image': ('x.png', b'x' * (10 * 1024 * 1024 + 1), 'image/png')})
    assert oversized.status_code == 413
    original = httpx.Client
    def fail(request):
        raise httpx.ReadTimeout('timeout')
    monkeypatch.setattr('app.routers.api.httpx.Client', lambda **kwargs: original(transport=httpx.MockTransport(fail), **kwargs))
    response = client.post('/api/try-on/generate', headers=headers, data={'wardrobe_item_id': identity}, files={'person_image': ('p.png', picture(), 'image/png')})
    assert response.status_code == 504
