# Development guide — PRD 1.1

Use feature branches and PRs targeting `develop`. Member 3 owns `backend/`, business DB migrations, API contracts and deployment integration. Member 1 owns inference in `ai-service/`. Members 2 and 4 share one Laravel project in `web/`; coordinate routes, layout and the reusable FastApiClient. `frontend/` is legacy React, retained until migration is complete.

Start with register/login → wardrobe → saved outfit, then integrate AI. Use `/docs` for the current OpenAPI schema. Laravel is responsible for CSRF, server-side session, regenerating session IDs on login and invalidating on logout/401. FastAPI is responsible for identity, business validation and ownership. Never query the domain database from Laravel.

Do not commit secrets, user photos, private datasets or model weights. Track dependency locks and document environment additions in `.env.example`. Do not rerun the old React bootstrap script.

Run `cd backend && .venv/bin/python -m pytest -q` before handing off. Tests isolate storage/database and mock the AI network. They do not demonstrate model quality or end-to-end Blade behavior.
