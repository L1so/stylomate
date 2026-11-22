from datetime import datetime, timezone
from sqlalchemy import Column, Integer, String, DateTime, ForeignKey, CheckConstraint, UniqueConstraint, Boolean
from app.database import Base

def now():
    return datetime.now(timezone.utc)

class User(Base):
    __tablename__ = 'users'
    id = Column(Integer, primary_key=True)
    name = Column(String(100), nullable=False)
    email = Column(String(254), nullable=False, unique=True)
    password_hash = Column(String(255), nullable=False)
    created_at = Column(DateTime(timezone=True), default=now, nullable=False)

class WardrobeItem(Base):
    __tablename__ = 'wardrobe_items'
    id = Column(Integer, primary_key=True)
    user_id = Column(ForeignKey('users.id'), nullable=False, index=True)
    name = Column(String(100), nullable=False)
    category = Column(String(30), nullable=False)
    color = Column(String(30), nullable=False)
    style = Column(String(30), nullable=False)
    image_path = Column(String(255), nullable=False)
    created_at = Column(DateTime(timezone=True), default=now, nullable=False)

class CatalogItem(Base):
    __tablename__ = 'catalog_items'
    id = Column(Integer, primary_key=True)
    name = Column(String(100), nullable=False)
    brand = Column(String(100), nullable=True)
    category = Column(String(30), nullable=False)
    color = Column(String(30), nullable=False)
    style = Column(String(30), nullable=False)
    image_path = Column(String(255), nullable=False)
    product_url = Column(String(2048), nullable=True)

class Outfit(Base):
    __tablename__ = 'outfits'
    id = Column(Integer, primary_key=True)
    user_id = Column(ForeignKey('users.id'), nullable=False, index=True)
    name = Column(String(100), nullable=False)
    created_at = Column(DateTime(timezone=True), default=now, nullable=False)

class OutfitItem(Base):
    __tablename__ = 'outfit_items'
    __table_args__ = (UniqueConstraint('outfit_id', 'wardrobe_item_id'),)
    id = Column(Integer, primary_key=True)
    outfit_id = Column(ForeignKey('outfits.id', ondelete='CASCADE'), nullable=False)
    wardrobe_item_id = Column(ForeignKey('wardrobe_items.id'), nullable=False)

class TryOnResult(Base):
    __tablename__ = 'try_on_results'
    __table_args__ = (CheckConstraint('(wardrobe_item_id IS NULL) <> (catalog_item_id IS NULL)', name='exactly_one_source'),)
    id = Column(Integer, primary_key=True)
    user_id = Column(ForeignKey('users.id'), nullable=False, index=True)
    wardrobe_item_id = Column(ForeignKey('wardrobe_items.id'), nullable=True)
    catalog_item_id = Column(ForeignKey('catalog_items.id'), nullable=True)
    result_image_path = Column(String(255), nullable=False)
    saved = Column(Boolean, default=False, nullable=False)
    created_at = Column(DateTime(timezone=True), default=now, nullable=False)
