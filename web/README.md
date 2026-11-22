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

Buka http://localhost:8080. FastAPI berjalan di `FASTAPI_BASE_URL` (default http://localhost:8000).

## Konfigurasi

| Variabel | Fungsi |
| --- | --- |
| `FASTAPI_BASE_URL` | Base URL FastAPI tanpa `/api` |
| `FASTAPI_TIMEOUT` | Timeout request ke FastAPI (detik) |
| `FASTAPI_USE_MOCK` | `true` = data contoh tanpa FastAPI (akun demo `demo@stylomate.test` / `password123`); otomatis nonaktif di production |

## Test

```bash
php artisan test
vendor/bin/pint --test
npm run build
```
