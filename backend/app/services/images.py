from io import BytesIO
from pathlib import Path
from uuid import uuid4
import warnings
from PIL import Image, UnidentifiedImageError
from fastapi import HTTPException
from app.config import settings

MAX_BYTES = 10 * 1024 * 1024
THUMBNAIL_SIZE = (600, 800)

def normalize_image(data):
    if len(data) > MAX_BYTES:
        raise HTTPException(413, 'Image exceeds 10 MB')
    try:
        with warnings.catch_warnings():
            warnings.simplefilter('error', Image.DecompressionBombWarning)
            with Image.open(BytesIO(data)) as im:
                if im.format not in ('JPEG', 'PNG') or im.width * im.height > 20_000_000:
                    raise ValueError()
                im.load()
                output = BytesIO()
                im.convert('RGB').save(output, format='JPEG', quality=90)
                return output.getvalue()
    except (UnidentifiedImageError, OSError, ValueError, Image.DecompressionBombError, Image.DecompressionBombWarning):
        raise HTTPException(422, 'Upload a valid JPEG or PNG image (maximum 20 megapixels)')

def store_image(data):
    directory = Path(settings.storage_dir)
    directory.mkdir(parents=True, exist_ok=True)
    name = uuid4().hex + '.jpg'
    (directory / name).write_bytes(data)
    return name

def image_path(name):
    base = Path(settings.storage_dir).resolve()
    path = (base / name).resolve()
    if path.parent != base or not path.is_file():
        raise HTTPException(404, 'Image not found')
    return path

def thumbnail_path(name):
    source = image_path(name)
    target = source.with_name(source.stem + '.thumb.jpg')
    if not target.is_file():
        partial = source.with_name(f'{source.stem}.{uuid4().hex}.tmp')
        try:
            with Image.open(source) as im:
                im.thumbnail(THUMBNAIL_SIZE)
                im.convert('RGB').save(partial, format='JPEG', quality=85)
            partial.replace(target)
        except (OSError, Image.DecompressionBombError):
            return source
        finally:
            partial.unlink(missing_ok=True)
    return target

def sized_image_path(name, size):
    return thumbnail_path(name) if size == 'thumb' else image_path(name)

def remove_image(name):
    try:
        path = image_path(name)
    except HTTPException:
        return
    path.with_name(path.stem + '.thumb.jpg').unlink(missing_ok=True)
    path.unlink(missing_ok=True)
