# TODO
## Akan Dilakukan
- [ ] Sepakati kontrak API dengan Anggota 3 (auth, list/pagination, image_path, endpoint file privat, query search/category, kategori) lalu uji web dengan FastAPI asli (FASTAPI_USE_MOCK=false); daftar lengkap di catatan tim https://claude.ai/artifact/2K11mAzFaYqknJS1NKv3n5
- [ ] Bahas dengan tim elemen artboard di luar PRD yang sengaja dilewati: "Lupa kata sandi?", "Edit Profil", filter Warna/Style, kategori Outer/Dress/Sepatu/Aksesori, dialog hapus bergaya
- [ ] Minta route DELETE /outfits/{id}, DELETE /try-on/{id}, GET /media/{path} dicatat di tabel PRD v1.1 §11.1
- [ ] Cek visual semua halaman di browser (desktop 1440 + mobile 390) terhadap artboard
- [ ] Rebase/merge origin/main (berisi docs/StyloMate_PRD_v1.1_Laravel_Blade.pdf) ke feature/frontend-core sebelum PR
- [ ] Commit ke feature/frontend-core dan PR ke develop (menunggu perintah user)
## Sedang Dilakukan
## Sudah Dilakukan
- [x] 2026-11-22: Tahap 1–3 — Laravel 12.73 (PHP 8.2) + Tailwind v4 via Vite tanpa database, session/cache file, port 8080; layout + komponen reusable; middleware EnsureApiToken/RedirectIfApiToken, logout POST, route bernama + placeholder halaman Anggota 4
- [x] 2026-11-22: Tahap 4–5 — service HTTP ke FastAPI + mode mock, handler error global (401/422/5xx/timeout), Masuk/Daftar (validasi, throttle 10/menit), Homepage, Catalog (index/detail, search + filter kategori F-09), Profil (akun, outfit, Try On, hapus, Unduh F-10), proxy gambar privat /media/{path}
- [x] 2026-11-22: Tahap 6 — design system tim (token warna, font Fraunces + Plus Jakarta Sans, navbar 3 kolom, footer gelap) dan copy Bahasa Indonesia sesuai design system; 5 halaman Anggota 2 mengikuti artboard UI/UX; ilustrasi placeholder; bahasa UI sempat Inggris lalu kembali ke Indonesia atas keputusan user
- [x] 2026-11-22: Penyesuaian PRD v1.1 — Laravel dipindah ke web/ dan frontend/ React dikembalikan sebagai legacy (§16, §16.1); StylomateApi → FastApiClient, MockFastApiClient, FastApiException; env FASTAPI_BASE_URL/FASTAPI_TIMEOUT/FASTAPI_USE_MOCK; ProfileController@index; controller placeholder Anggota 4 sesuai §11.1 (WardrobeController, OutfitController@builder, TryOnController@index, RecommendationController@index); partial diganti komponen Blade di resources/views/components (§12) sehingga debt variabel bocor @include selesai; README root + web/README.md + docs/FIGMA.md + docs/DEVELOPMENT_GUIDE.md diperbarui; 109 test lolos + smoke test 13 halaman
## Debt
- web/app/Services/FastApiClient.php → login/register: format request/response (JSON {email,password}, {access_token}, email duplikat 409/400, 422 detail[].loc) masih asumsi; sepakati dengan Anggota 3
- web/app/Services/FastApiClient.php → file/withCatalogUrls/withPrivateImage: format image_path, endpoint file privat (GET /api/files/{path}), gambar katalog relatif ke FASTAPI_BASE_URL masih asumsi; sepakati dengan Anggota 3
- web/app/Services/FastApiClient.php → listFrom: list diterima sebagai array langsung atau {data}/{items}; pagination belum ditangani di UI karena kontrak pagination FastAPI belum ada
- web/app/Services/FastApiClient.php → CATEGORIES: whitelist kategori (top=Atasan, bottom=Bawahan) masih asumsi; samakan dengan kategori wardrobe dan AI
- web/app/Services/FastApiClient.php → fieldErrors: pesan 422 FastAPI diganti pesan generik; field yang tidak ada di form hanya terlihat sebagai ringkasan "Periksa lagi isian yang ditandai."
- web/app/Services/MockFastApiClient.php: token mock "mock-token:<email>" bisa ditebak; aman karena mock mati di production, jangan aktifkan FASTAPI_USE_MOCK di server publik
- web/app/Services/MockFastApiClient.php → file: gambar wardrobe/Try On mock masih PNG 1x1, kartu outfit/Try On di mode mock tampil kosong
- web/app/Http/Controllers/HomeController.php → index: memanggil Catalog dengan rescue; bila host FastAPI tidak merespons, Homepage menunggu sampai connect timeout 5 detik
- web/app/Http/Controllers/ProfileController.php → index: bila salah satu dari me/outfits/try-on gagal, seluruh halaman tampil error (belum degradasi per bagian)
- web/resources/views/components/confirm-delete.blade.php: konfirmasi memakai window.confirm() bawaan browser; ganti ke dialog bergaya bila diminta
- web/resources/views/layouts/app.blade.php: font dimuat dari Google Fonts (IP pengunjung terkirim ke Google); pertimbangkan self-host bila ada CSP/privasi ketat
- web/public/images/illustrations/*.svg: ilustrasi placeholder dari canvas UI/UX; ganti dengan foto asli saat tersedia
- web/lang/id/validation.php: dipakai (APP_LOCALE=id); teks view ditulis langsung, bukan __(), jadi ganti bahasa berarti terjemah ulang view
- web/config/auth.php → providers.users: masih menunjuk App\Models\User yang sudah dihapus; aman selama Auth facade tidak dipakai
- web/composer.json → autoload: namespace Database\Factories dan Database\Seeders menunjuk folder yang sudah dihapus; tidak error, bisa dibersihkan
- Versi Laravel 12 (bukan 13 seperti rujukan PRD v1.1 Lampiran B) karena PHP lokal 8.2; naik ke 13 bila semua anggota sudah PHP 8.3+
- Anggota 4: mockup fashion di origin/feature/fashion-features masih React, perlu di-port ke Blade; tambahkan method di OutfitController/TryOnController/WardrobeController/RecommendationController yang sudah ada
