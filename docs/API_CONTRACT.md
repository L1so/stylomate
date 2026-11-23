# FastAPI contract — PRD 1.1

Base URL: `http://127.0.0.1:8000/api` (Laravel → FastAPI, never browser → FastAPI). See `/docs` or `/openapi.json` for exact generated field schemas. This is the implemented MVP contract; coordinate changes with members 2/4 before integration.

## Shared rules

- Success: `{"data": object_or_list}`; recommendations additionally have `meta`. Health endpoints keep `{"status":"ok"}`. DELETE success is empty 204.
- Errors: `{"error":{"message":"..."}}`; validation errors add `fields: [{"field":"email","message":"..."}]` inside `error`. Inputs/passwords are not echoed.
- 401: missing/invalid/expired token (Laravel clears session and redirects login); 404: missing or foreign resource; 409: duplicate email or referenced wardrobe deletion; 410: expired preview; 413: image over limit; 422: invalid fields/image; 502: invalid AI output; 503: AI unavailable; 504: AI timeout.
- All endpoints require `Authorization: Bearer <JWT>` except auth register/login, catalog and health. Auth token uses configured lifetime, default 3600 seconds. No refresh or revocation endpoint in this MVP; Laravel logout invalidates its session, already-issued JWT remains valid until expiry.
- IDs are integers. Categories: `top`, `bottom`, `dress`, `outerwear`, `shoes`, `accessory`. Styles: `casual`, `formal`, `semi-formal`, `sporty`. Colors are lowercase free text; use consistent names from the UI.
- Images: actual JPEG/PNG, <=10 MiB and <=20 megapixels. Re-encoded as JPEG without source metadata. Ingress must additionally limit entire HTTP body; multipart parsing happens before endpoint validation.
- `image_url` is a relative FastAPI route starting `/api/`, not a disk path. Private image responses use `Cache-Control: private, no-store`.

## Endpoints

| Method | Path | Request / response notes |
|---|---|---|
| POST | `/auth/register` | JSON `name,email,password`; password 8–128 chars; 201 user `{id,name,email}`; no auto-login |
| POST | `/auth/login` | JSON `email,password`; data `{access_token,token_type,expires_in,user}` |
| GET | `/auth/me` | User `{id,name,email}` |
| GET | `/catalog` | Optional `q`, `category`; array of catalog items |
| GET | `/catalog/{id}` | One catalog item |
| GET | `/catalog/{id}/image` | Public image bytes |
| GET | `/wardrobe` | Current user's items |
| POST | `/wardrobe` | Multipart `name,category,color,style,image`; 201 item |
| GET | `/wardrobe/{id}` | Owned item |
| PATCH | `/wardrobe/{id}` | Multipart optional `name,category,color,style,image`; omitted values retained |
| DELETE | `/wardrobe/{id}` | 204; 409 when referenced by outfit or try-on |
| GET | `/wardrobe/{id}/image` | Owned image bytes |
| GET | `/outfits` | Array `{id,name,wardrobe_item_ids}` |
| POST | `/outfits` | JSON `name,wardrobe_item_ids`; must include owned top and bottom; 201 outfit |
| DELETE | `/outfits/{id}` | 204 |
| GET | `/recommendations?style=casual` | Backend-owned rules: matching style, top/bottom pairs; prioritize same/neutral colors, max 5. Empty list + meta message when insufficient |
| POST | `/try-on/generate` | Multipart `person_image` and exactly one of `wardrobe_item_id` / `catalog_item_id` / `outfit_id`. Single items support `top` and `bottom`; an outfit must contain exactly one of each and runs two AI stages. |
| POST | `/try-on/save` | JSON `generation_id`; idempotently marks owned preview saved |
| GET | `/try-on/history` | Saved results only |
| GET | `/try-on/{id}/image` | Owned result/preview bytes |
| DELETE | `/try-on/{id}` | 204; deletes saved result or active preview |

Wardrobe item: `{id,name,category,color,style,image_url}`. Catalog additionally has `brand,product_url`. Try-on result: `{id,generation_id,saved,image_url,wardrobe_item_id,catalog_item_id,outfit_id}`; exactly one source ID is non-null.

## Laravel implementation notes

1. Use `FASTAPI_BASE_URL` including `/api`, then call paths such as `/auth/login`. Keep one reusable FastApiClient; no Eloquent business model.
2. Login: regenerate session, store `data.access_token` server-side, render only user-safe values. Logout: invalidate session and regenerate CSRF token.
3. Use Laravel multipart attachment for images. Browser form uses CSRF and method spoofing; Laravel HTTP Client sends an actual PATCH to FastAPI.
4. For private images, provide a Laravel route such as `/wardrobe/{id}/image` which fetches the matching fixed FastAPI route using the session token and streams bytes. Do not proxy arbitrary URLs; do not put JWT in an image query string or HTML.
5. Regular request timeout: suggested 10 seconds, connect 5. AI timeout: exceed `AI_TIMEOUT_SECONDS` (default 300) plus overhead and coordinate web-server limits. Do not automatically retry generation after a timeout; it may have completed upstream.
6. Map field errors back to Blade; clear session on upstream 401. Maintain loading/error/empty states.

## AI and privacy lifecycle

FastAPI calls private AI `POST /generate` with `person_image`, `garment_image`, and `category=upper-body|lower-body`. Outfit generation applies the bottom first, then applies the top to that preview. Optional `AI_SERVICE_TOKEN` adds a bearer header. Successful AI requests return JPEG or PNG bytes.

Person photos are held in request memory and never persisted by this backend. AI service must apply its own temporary-input cleanup policy. Generated output is temporarily stored for preview; `saved=false`, maximum one hour access. User explicitly saves it using its server-issued generation ID; clients cannot supply arbitrary image paths/URLs. Saved output persists until deleted. Run `python -m app.maintenance cleanup-previews` every 15 minutes to remove expired unsaved rows/files; the operator must configure this schedule.

## Known MVP limits

No pagination, password reset, refresh token, public deployment rate limiting or asynchronous inference queue yet. Catalog seed images are placeholders. SQLite tests verify application flows, but PostgreSQL runtime, real AI output and Blade integration require environment-specific verification.
