import os
import tempfile
os.environ['DATABASE_URL'] = 'sqlite://'
os.environ['JWT_SECRET_KEY'] = 'test-only-secret-key-at-least-32-characters'
os.environ['STORAGE_DIR'] = tempfile.mkdtemp(prefix='stylomate-tests-')

import pytest
from sqlalchemy import create_engine, event
from sqlalchemy.orm import Session
from sqlalchemy.pool import StaticPool
from app.database import Base, get_db
from app.main import app
from app import models

@pytest.fixture(autouse=True)
def database(tmp_path, monkeypatch):
    from app.config import settings
    monkeypatch.setattr(settings, 'storage_dir', str(tmp_path / 'images'))
    engine = create_engine('sqlite://', connect_args={'check_same_thread': False}, poolclass=StaticPool)
    @event.listens_for(engine, 'connect')
    def foreign_keys(connection, record):
        connection.execute('PRAGMA foreign_keys=ON')
    Base.metadata.create_all(engine)
    def override():
        with Session(engine) as session:
            yield session
    app.dependency_overrides[get_db] = override
    yield engine
    app.dependency_overrides.clear()
    engine.dispose()
