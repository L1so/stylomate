from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app, backend_options={'use_uvloop': True})

def test_health():
    response = client.get('/api/health')
    assert response.status_code == 200
    assert response.json() == {'status': 'ok'}
