from datetime import datetime, timedelta, timezone
import jwt
from pwdlib import PasswordHash
from fastapi import Depends, HTTPException
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from sqlalchemy.orm import Session
from app.config import settings
from app.database import get_db
from app.models import User

passwords = PasswordHash.recommended()
bearer = HTTPBearer(auto_error=False)

def issue_token(user):
    now = datetime.now(timezone.utc)
    return jwt.encode({'sub': str(user.id), 'iat': now, 'exp': now + timedelta(minutes=settings.access_token_expire_minutes)}, settings.jwt_secret_key, algorithm=settings.jwt_algorithm)

def current_user(credentials: HTTPAuthorizationCredentials = Depends(bearer), db: Session = Depends(get_db)):
    try:
        if credentials is None:
            raise ValueError()
        payload = jwt.decode(credentials.credentials, settings.jwt_secret_key, algorithms=[settings.jwt_algorithm], options={'require': ['exp', 'sub']})
        user = db.get(User, int(payload['sub']))
        if user is None:
            raise ValueError()
        return user
    except (jwt.InvalidTokenError, ValueError, TypeError):
        raise HTTPException(401, 'Authentication required', headers={'WWW-Authenticate': 'Bearer'})
