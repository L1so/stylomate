# StyloMate Web (Laravel Blade)

Lapisan tampilan StyloMate sesuai PRD v1.1 Opsi A: Laravel Blade merender halaman dan memanggil FastAPI lewat `app/Services/FastApiClient.php`. Laravel tidak memakai database bisnis.

## Menjalankan

```bash
cd web
composer install
npm ci
cp -n .env.example .env
php artisan key:generate
composer run dev
```

Buka http://localhost:8080. FastAPI berjalan di `FASTAPI_BASE_URL` (default http://127.0.0.1:8000/api).

## Konfigurasi

| Variabel | Fungsi |
| --- | --- |
| `FASTAPI_BASE_URL` | Base URL FastAPI **termasuk** `/api`, misalnya `http://127.0.0.1:8000/api` |
| `FASTAPI_TIMEOUT` | Timeout request ke FastAPI (detik) |
| `FASTAPI_USE_MOCK` | `true` = data contoh tanpa FastAPI (akun demo `demo@stylomate.test` / `password123`); otomatis nonaktif di production |

## Integrasi FastAPI

Semua endpoint dipanggil relatif terhadap `FASTAPI_BASE_URL` (misalnya `/auth/login`), jadi tidak ada `/api/api`.

| Hal | Yang diharapkan Laravel |
| --- | --- |
| Respons sukses | Selalu `{"data": ...}`; `FastApiClient::send()` membuka `data` |
| Login | `POST /auth/login` `{email, password}` → `data.access_token` disimpan di session server-side |
| Register | `POST /auth/register` `{name, email, password}` → 201, lalu Laravel login otomatis |
| Profil | `GET /auth/me` → `data.{id, name, email}` |
| Catalog | `GET /catalog?q=&category=` (form UI tetap memakai `?search=`); `GET /catalog/{id}`; `image_url` publik, path `/api/...` ditempel ke origin FastAPI |
| Outfit | `GET /outfits` → `{id, name, wardrobe_item_ids}`; `DELETE /outfits/{id}` |
| Try On | `GET /try-on/history` → item dengan `id`, `image_url`; `DELETE /try-on/{id}` |
| Gambar privat | Browser memuat `/media/try-on/{id}` dan `/media/wardrobe/{id}` milik Laravel; Laravel memanggil `GET /try-on/{id}/image` dan `GET /wardrobe/{id}/image` dengan JWT dari session. Hanya JPEG/PNG/WebP yang diteruskan |
| Error 422 | `{"error": {"fields": [{"field", "message"}]}}` → tampil di field form Blade |
| Error lain | 401 menghapus session dan ke halaman Masuk; 403/404/5xx/timeout tampil sebagai pesan aman |
| Kategori | `top`, `bottom`, `dress`, `outerwear`, `shoes`, `accessory` |

## Test

```bash
php artisan test
vendor/bin/pint --test
npm run build
```
