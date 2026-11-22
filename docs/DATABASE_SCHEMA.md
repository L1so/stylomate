# Database schema — PRD 1.1

Source: `backend/app/models/__init__.py`; initial Alembic migration in `backend/migrations/versions/`.

| Table | Purpose / constraints |
|---|---|
| users | Unique normalized email, Argon2 password hash, creation time |
| wardrobe_items | Owner FK, name/category/color/style, private image filename |
| catalog_items | Public catalog metadata and image filename |
| outfits | Owner FK and name |
| outfit_items | Outfit + wardrobe FKs; unique pair |
| try_on_results | Owner FK, exactly one source FK (wardrobe/catalog), image filename, saved flag, creation time |

Only FastAPI accesses these tables. Laravel uses file sessions. API ownership checks apply to item reads, updates, deletes, image reads, outfit composition, generation and save.

Deleting a wardrobe item referenced by an outfit or try-on returns 409. Delete those references first. Deleting an outfit deletes its membership rows. Unsaved try-on rows are temporary previews; cleanup removes previews older than one hour, saved results remain until explicitly deleted. Run migrations with `cd backend && alembic upgrade head`; schema is not created automatically at application startup.
