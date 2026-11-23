# StyloMate

PRD 1.1: Browser → Laravel Blade (`web/`, members 2/4) → FastAPI (`backend/`, member 3) → PostgreSQL / private image storage / AI service (member 1).

The FastAPI MVP implements auth, catalog, wardrobe, outfits, rule-based recommendations and AI orchestration. Laravel Blade lives in `web/`; `frontend/` is legacy React and is not the target UI. AI inference requires the real service from member 1. See `docs/API_CONTRACT.md` for integration details and limitations.

## Cara nyalain

Di satu terminal

```bash
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000
```

Di terminal lainnya.

```bash
cd web
composer install
npm ci
composer run dev
```

## Backend development

Requirements: Python 3.10+, Docker Compose v2, installed dependencies from `backend/requirements.txt`.

1. Copy `.env.example` to `.env` only if missing. Set a unique PostgreSQL password and a random JWT secret (at least 32 characters); keep DATABASE_URL consistent. Do not commit `.env`.
2. Start PostgreSQL from repo root:

```bash
docker compose up -d db
```

3. In a backend terminal:

```bash
cd backend
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
alembic upgrade head
python -m app.maintenance seed-catalog
uvicorn app.main:app --reload --host 127.0.0.1 --port 8000
```

Open http://localhost:8000/docs for interactive API testing. Catalog seed is idempotent and contains clearly labelled illustration placeholders, **not garment photos suitable for AI evaluation**.

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

Tests use an isolated SQLite database and temporary images, never the configured business database:

```bash
cd backend
.venv/bin/python -m pytest -q
```

PostgreSQL migrations and a real AI inference must also be verified in your deployment environment. Compose currently starts only PostgreSQL; FastAPI runs using the command above.

## Laravel handoff (members 2 and 4)

The Laravel Blade project lives in `web/`. Set `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` and `FASTAPI_BASE_URL=http://127.0.0.1:8000/api` locally. Do not run business migrations or use Eloquent for StyloMate data. Use one reusable `FastApiClient` for JSON/multipart, bearer token, errors and timeouts. Vite is still needed for Blade CSS/assets. Choose Laravel/PHP versions compatible with the team's machines.

Login token stays in Laravel's server session. Private image URLs must be fetched through an authenticated Laravel proxy route; an `<img>` pointing directly to FastAPI will not carry that token. See contract.

Keep React legacy until the Blade replacement works. Members 2/4 own controllers and views; member 3 owns the data API.

## Operations

Private images default to `storage/images/`. Back up this directory together with PostgreSQL; never publish it as a static directory. Original person photos are processed in memory, not persisted by FastAPI. Unsaved generated previews are temporary and expire after one hour. Schedule this command every 15 minutes (cron or your scheduler):

```bash
cd backend
.venv/bin/python -m app.maintenance cleanup-previews
```

Without this scheduled command, expired preview files remain on disk. Configure `AI_SERVICE_URL`, optional shared `AI_SERVICE_TOKEN`, and `AI_TIMEOUT_SECONDS`. AI must run on a private network or enforce authentication. Laravel/reverse-proxy request timeouts must exceed the configured inference timeout plus upload overhead; move to jobs/polling if hosting cannot support this.

This is an MVP, not a production-hardened deployment. Before public exposure add login rate limiting, HTTPS, total request-size limits at the ingress, monitoring and actual integration testing.

## Branches

- `main`: stable
- `develop`: integration / PR target
- `feature/ai-try-on`: member 1
- `feature/frontend-core`: member 2, now Laravel Blade
- `feature/backend-core`: member 3
- `feature/fashion-features`: member 4, Laravel Blade
