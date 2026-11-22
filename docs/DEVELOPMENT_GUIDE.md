# Development guide

Use feature branches and open pull requests targeting develop. Merge tested code into main only for a stable release. Members 2 and 4 share one Laravel project in `web/` (PRD v1.1): reuse the Blade components in `web/resources/views/components` and call FastAPI only through `web/app/Services/FastApiClient.php`. Use `FASTAPI_USE_MOCK=true` until contracts are implemented. Keep package-lock.json, composer.lock and requirements.txt tracked.

Never commit .env, user photos, private datasets or model weights. Document any new environment variables in .env.example.
