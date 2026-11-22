from io import BytesIO
from pathlib import Path
from uuid import uuid4
import warnings
from PIL import Image, UnidentifiedImageError
from fastapi import HTTPException
from app.config import settings

MAX_BYTES = 10 * 1024 * 1024

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

def remove_image(name):
    try:
        image_path(name).unlink(missing_ok=True)
    except HTTPException:
        pass
