from typing import Literal
from pydantic import BaseModel, EmailStr, Field, field_validator

Category = Literal['top', 'bottom', 'dress', 'outerwear', 'shoes', 'accessory']
Style = Literal['casual', 'formal', 'semi-formal', 'sporty']
class Register(BaseModel):
    name: str = Field(min_length=1, max_length=100)
    email: EmailStr
    password: str = Field(min_length=8, max_length=128)
    @field_validator('name')
    @classmethod
    def name_not_blank(cls, value):
        if not value.strip():
            raise ValueError('Name cannot be blank')
        return value.strip()
class Login(BaseModel):
    email: EmailStr
    password: str = Field(min_length=1, max_length=128)
class OutfitCreate(BaseModel):
    name: str = Field(min_length=1, max_length=100)
    wardrobe_item_ids: list[int] = Field(min_length=2, max_length=20)
class SaveResult(BaseModel):
    generation_id: int = Field(gt=0)
