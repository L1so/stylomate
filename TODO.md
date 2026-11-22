# TODO
## Akan Dilakukan
- [ ] Uji web dengan FastAPI asli (FASTAPI_USE_MOCK=false) begitu endpoint backend tersedia; backend di origin/feature/backend-core per 2026-11-22 baru punya /health
- [ ] Bahas dengan tim elemen artboard di luar PRD yang sengaja dilewati: "Lupa kata sandi?", "Edit Profil", filter Warna/Style, dialog hapus bergaya
- [ ] Minta route DELETE /outfits/{id}, DELETE /try-on/{id}, GET /media/try-on/{id}, GET /media/wardrobe/{id} dicatat di tabel PRD v1.1 §11.1
- [ ] Cek visual semua halaman di browser (desktop 1440 + mobile 390) terhadap artboard
- [ ] PR feature/frontend-core ke develop
## Sedang Dilakukan
## Sudah Dilakukan
- [x] 2026-11-22: Homepage — garis vertikal terracotta di bawah judul hero dihapus mengikuti artboard UI/UX terbaru; 115 test lolos + build Vite sukses
- [x] 2026-11-22: _ref/ (PRD PDF, prompt, catatan design lokal) ditambahkan ke .gitignore
- [x] 2026-11-22: Integrasi kontrak FastAPI final — FASTAPI_BASE_URL termasuk /api (endpoint relatif, tanpa /api/api), send() membuka envelope data, login dari data.access_token, Catalog ?search= dipetakan ke q + image_url publik dari origin FastAPI, proxy gambar privat per id (/media/try-on/{id}, /media/wardrobe/{id}) menggantikan proxy path bebas, outfit memakai wardrobe_item_ids, 422 error.fields ke form, 6 kategori backend; 115 test lolos + smoke test mock
- [x] 2026-11-22: Tahap 1–3 — Laravel 12.73 (PHP 8.2) + Tailwind v4 via Vite tanpa database, session/cache file, port 8080; layout + komponen reusable; middleware EnsureApiToken/RedirectIfApiToken, logout POST, route bernama + placeholder halaman Anggota 4
- [x] 2026-11-22: Tahap 4–5 — service HTTP ke FastAPI + mode mock, handler error global (401/422/5xx/timeout), Masuk/Daftar (validasi, throttle 10/menit), Homepage, Catalog (index/detail, search + filter kategori F-09), Profil (akun, outfit, Try On, hapus, Unduh F-10), proxy gambar privat /media/{path}
- [x] 2026-11-22: Tahap 6 — design system tim (token warna, font Fraunces + Plus Jakarta Sans, navbar 3 kolom, footer gelap) dan copy Bahasa Indonesia sesuai design system; 5 halaman Anggota 2 mengikuti artboard UI/UX; ilustrasi placeholder; bahasa UI sempat Inggris lalu kembali ke Indonesia atas keputusan user
- [x] 2026-11-22: Penyesuaian PRD v1.1 — Laravel dipindah ke web/ dan frontend/ React dikembalikan sebagai legacy (§16, §16.1); StylomateApi → FastApiClient, MockFastApiClient, FastApiException; env FASTAPI_BASE_URL/FASTAPI_TIMEOUT/FASTAPI_USE_MOCK; ProfileController@index; controller placeholder Anggota 4 sesuai §11.1 (WardrobeController, OutfitController@builder, TryOnController@index, RecommendationController@index); partial diganti komponen Blade di resources/views/components (§12) sehingga debt variabel bocor @include selesai; README root + web/README.md + docs/FIGMA.md + docs/DEVELOPMENT_GUIDE.md diperbarui; 109 test lolos + smoke test 13 halaman
## Debt
- web/app/Services/FastApiClient.php → login/register: email duplikat diasumsikan 409/400 (kontrak final tidak menyebut kode); expires_in dari login belum dipakai, token kedaluwarsa ditangani lewat 401
- web/app/Services/FastApiClient.php → listOf: pagination belum ditangani di UI karena kontrak FastAPI belum menyebut pagination
- web/app/Services/FastApiClient.php → fieldErrors: pesan field 422 dari FastAPI ditampilkan apa adanya (bahasa mengikuti backend); field yang tidak ada di form hanya terlihat sebagai ringkasan "Periksa lagi isian yang ditandai."
- web/app/Http/Controllers/ProfileController.php → index: outfit hanya membawa wardrobe_item_ids, jadi kartu outfit menampilkan gambar + jumlah pakaian tanpa nama item (perlu GET /wardrobe bila nama ingin ditampilkan)
- web/app/Http/Controllers/CatalogController.php, OutfitController.php, TryOnController.php: route {id} memakai whereNumber tanpa batas panjang; id sangat panjang bisa memicu error 500 (route media sudah dibatasi 1–9 digit)
- web/app/Services/MockFastApiClient.php: token mock "mock-token:<email>" bisa ditebak; aman karena mock mati di production, jangan aktifkan FASTAPI_USE_MOCK di server publik
- web/app/Services/MockFastApiClient.php → image: gambar wardrobe/Try On mock masih PNG 1x1, kartu outfit/Try On di mode mock tampil kosong
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
