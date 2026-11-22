from pathlib import Path
from pydantic import Field, field_validator
from pydantic_settings import BaseSettings, SettingsConfigDict

ROOT_DIR = Path(__file__).resolve().parents[2]

class Settings(BaseSettings):
    database_url: str
    jwt_secret_key: str
    jwt_algorithm: str = "HS256"
    access_token_expire_minutes: int = 60
    ai_service_url: str = "http://localhost:8001"
    ai_timeout_seconds: int = 300
    ai_cold_start_timeout_seconds: int = Field(default=0, ge=0)
    storage_dir: str = str(ROOT_DIR / "storage" / "images")
    ai_service_token: str = ""
    ai_hf_space_token: str = ""
    ai_hf_zerogpu: bool = False
    @field_validator('jwt_secret_key')
    @classmethod
    def strong_secret(cls, value):
        if len(value) < 32:
            raise ValueError('JWT_SECRET_KEY must contain at least 32 characters')
        return value

    model_config = SettingsConfigDict(env_file=ROOT_DIR / ".env", extra="ignore")

settings = Settings()
