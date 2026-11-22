"""Run with python -m app.maintenance seed-catalog|cleanup-previews."""
import argparse
from datetime import datetime, timedelta, timezone
from io import BytesIO
from PIL import Image, ImageDraw
from sqlalchemy import select
from app.database import SessionLocal
from app.models import CatalogItem, TryOnResult
from app.services.images import store_image, remove_image

def seed_catalog():
    # Explicit demo illustrations; these are not AI-ready garment photos.
    entries = [('Demo White Shirt', 'top', 'white', 'casual'), ('Demo Navy Trousers', 'bottom', 'navy', 'casual'), ('Demo Black Formal Shirt', 'top', 'black', 'formal')]
    with SessionLocal() as db:
        for name, category, color, style in entries:
            if db.scalar(select(CatalogItem.id).where(CatalogItem.name == name)):
                continue
            image = Image.new('RGB', (400, 400), '#dddddd')
            draw = ImageDraw.Draw(image)
            draw.rectangle((100, 80, 300, 300), fill=color, outline='gray')
            draw.text((20, 350), name + ' (placeholder)', fill='black')
            output = BytesIO()
            image.save(output, 'JPEG')
            path = store_image(output.getvalue())
            db.add(CatalogItem(name=name, category=category, color=color, style=style, brand='Demo', image_path=path))
            try:
                db.commit()
            except Exception:
                db.rollback()
                remove_image(path)
                raise
    print('Demo catalog seeded. Replace placeholders with licensed garment photos for AI.')

def cleanup_previews():
    cutoff = datetime.now(timezone.utc) - timedelta(hours=1)
    with SessionLocal() as db:
        rows = list(db.scalars(select(TryOnResult).where(TryOnResult.saved.is_(False), TryOnResult.created_at < cutoff)))
        paths = [row.result_image_path for row in rows]
        for row in rows:
            db.delete(row)
        db.commit()
        for path in paths:
            remove_image(path)
    print(f'Removed {len(paths)} expired previews.')

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('command', choices=['seed-catalog', 'cleanup-previews'])
    args = parser.parse_args()
    {'seed-catalog': seed_catalog, 'cleanup-previews': cleanup_previews}[args.command]()
