# StyloMate

AI-powered sustainable fashion web application (MVP).

## Local development

Prerequisites: Git, Node 22.12+ or 24+, Python 3.10+ with venv, Docker Compose v2.

1. Copy root `.env.example` to `.env` if missing. Replace POSTGRES_PASSWORD and JWT_SECRET_KEY with unique random values, and update DATABASE_URL to match (hex passwords avoid URL encoding issues). Do not overwrite an existing .env or commit it.
2. Start the local database from the root:

```bash
docker compose up -d db
```

3. Backend (separate terminal):

```bash
cd backend
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
alembic upgrade head
uvicorn app.main:app --reload --port 8000
```

Swagger: http://localhost:8000/docs

4. Web (Laravel 12 + Blade + Tailwind, PHP 8.2+, separate terminal), per PRD v1.1 Option A:

```bash
cd web
composer install
npm ci
cp -n .env.example .env
php artisan key:generate
composer run dev
```

Open http://localhost:8080. Laravel has no business database; all data comes from FastAPI (`FASTAPI_BASE_URL` including `/api`, default http://127.0.0.1:8000/api) through `app/Services/FastApiClient.php`. Set `FASTAPI_USE_MOCK=true` to use sample data before the backend is ready. Details: `web/README.md`.

`frontend/` is the legacy React starter kept until the migration is approved (PRD v1.1 §16.1). Do not develop new UI there.

5. AI: see `ai-service/README.md`. If hosted elsewhere, set AI_SERVICE_URL to an address reachable from the backend. The bootstrap does not install AI dependencies or model weights.

## Branches

- `main`: stable release
- `develop`: integration (PR target)
- `feature/ai-try-on`: member 1
- `feature/frontend-core`: member 2 (including Figma)
- `feature/backend-core`: member 3
- `feature/fashion-features`: member 4

See `docs/` for the proposed contracts and PRD. This project is a development starter, not a complete application or production configuration. Authentication, business APIs, database models, UI screens and AI inference still need to be implemented.
