# Proposed API contract

Base URL: http://localhost:8000/api

| Method | Path | Responsibility |
|---|---|---|
| GET | /health | Backend readiness (process) |
| GET | /health/db | Database connectivity |
| POST | /auth/register | Backend |
| POST | /auth/login | Backend |
| GET | /auth/me | Backend |
| GET | /catalog | Backend |
| GET | /catalog/{id} | Backend |
| GET, POST | /wardrobe | Backend |
| GET, PATCH, DELETE | /wardrobe/{id} | Backend |
| GET, POST | /outfits | Backend |
| DELETE | /outfits/{id} | Backend |
| GET | /recommendations?style=casual | Fashion features |
| POST | /try-on/generate | Backend + AI |
| POST | /try-on/save | Backend |
| GET | /try-on/history | Backend |
| DELETE | /try-on/{id} | Backend |

Only health endpoints are implemented by this bootstrap. Agree on schemas, auth, errors, multipart and generation/save workflow before implementing. See ai-service/README.md for the internal AI API.
