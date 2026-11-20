from pathlib import Path
from pydantic_settings import BaseSettings, SettingsConfigDict

ROOT_DIR = Path(__file__).resolve().parents[2]

class Settings(BaseSettings):
    database_url: str
    jwt_secret_key: str
    jwt_algorithm: str = "HS256"
    access_token_expire_minutes: int = 60
    ai_service_url: str = "http://localhost:8001"
    ai_timeout_seconds: int = 300
    model_config = SettingsConfigDict(env_file=ROOT_DIR / ".env", extra="ignore")

settings = Settings()
