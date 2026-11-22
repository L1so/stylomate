<?php

namespace App\Services;

use App\Exceptions\FastApiException;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Di luar materi PAWL: FastApiClient (PRD v1.1 §14) mengumpulkan semua panggilan HTTP ke FastAPI di satu tempat.
// Di materi PAWL controller mengambil data lewat Eloquent; di project ini data ada di backend FastAPI
// (Anggota 3), jadi controller memanggil class ini dan URL/format API cukup diubah di sini.
class FastApiClient
{
    // Kategori yang dipakai filter Catalog. Sepakati nilai kategori dengan Anggota 3
    public const CATEGORIES = ['top' => 'Atasan', 'bottom' => 'Bawahan'];

    // Kontrak fashion PRD v1.1 (Anggota 4): kategori dan style yang diterima FastAPI untuk Wardrobe
    public const WARDROBE_CATEGORIES = [
        'top' => 'Atasan',
        'bottom' => 'Bawahan',
        'dress' => 'Dress',
        'outerwear' => 'Outerwear',
        'shoes' => 'Sepatu',
        'accessory' => 'Aksesori',
    ];

    public const STYLES = [
        'casual' => 'Casual',
        'formal' => 'Formal',
        'semi-formal' => 'Semi-formal',
        'sporty' => 'Sporty',
    ];

    public function register(string $name, string $email, string $password): array
    {
        return $this->send('post', '/auth/register', compact('name', 'email', 'password'));
    }

    public function login(string $email, string $password): string
    {
        // Sepakati dengan Anggota 3: body JSON {email, password}, respons {access_token, token_type}
        $data = $this->send('post', '/auth/login', compact('email', 'password'));
        $token = $data['access_token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new FastApiException('Terjadi kesalahan di server. Coba lagi nanti.', 502);
        }

        return $token;
    }

    public function me(): array
    {
        return $this->send('get', '/auth/me');
    }

    public function catalog(array $filters = []): array
    {
        // Sepakati dengan Anggota 3: query ?search= (nama/brand) dan ?category=
        return array_map(fn (array $item) => $this->withCatalogUrls($item), $this->listFrom($this->send('get', '/catalog', $filters)));
    }

    public function catalogItem(int $id): array
    {
        return $this->withCatalogUrls($this->send('get', "/catalog/{$id}"));
    }

    public function outfits(): array
    {
        return array_map(function (array $outfit) {
            $outfit['items'] = array_map(
                fn ($item) => $this->withPrivateImage(is_array($item) ? $item : [], 'image_path'),
                is_array($outfit['items'] ?? null) ? $outfit['items'] : []
            );

            return $outfit;
        }, $this->listFrom($this->send('get', '/outfits')));
    }

    public function deleteOutfit(int $id): void
    {
        $this->send('delete', "/outfits/{$id}");
    }

    public function tryOnHistory(): array
    {
        return array_map(fn (array $result) => $this->withPrivateImage($result, 'result_image_path'), $this->listFrom($this->send('get', '/try-on/history')));
    }

    public function deleteTryOn(int $id): void
    {
        $this->send('delete', "/try-on/{$id}");
    }

    // ---- Wardrobe (Anggota 4) ----

    public function wardrobeItems(): array
    {
        return array_map(fn (array $item) => $this->withWardrobeImage($item), $this->listFrom($this->send('get', '/wardrobe')));
    }

    public function wardrobeItem(int $id): array
    {
        return $this->withWardrobeImage($this->dataFrom($this->send('get', "/wardrobe/{$id}")));
    }

    /** POST /wardrobe multipart: name, category, color, style, image */
    public function createWardrobeItem(array $fields, UploadedFile $image): array
    {
        return $this->withWardrobeImage($this->dataFrom($this->upload('/wardrobe', $fields, ['image' => $image])));
    }

    /** PATCH /wardrobe/{id} JSON: name, category, color, style */
    public function updateWardrobeItem(int $id, array $fields): array
    {
        return $this->withWardrobeImage($this->dataFrom($this->send('patch', "/wardrobe/{$id}", $fields)));
    }

    public function deleteWardrobeItem(int $id): void
    {
        $this->send('delete', "/wardrobe/{$id}");
    }

    // ---- Mix & Match dan Smart Outfit Curation (Anggota 4) ----

    /** POST /outfits JSON {name, wardrobe_item_ids}; FastAPI menolak outfit tanpa top dan bottom */
    public function createOutfit(string $name, array $wardrobeItemIds): array
    {
        return $this->dataFrom($this->send('post', '/outfits', [
            'name' => $name,
            'wardrobe_item_ids' => array_values(array_map('intval', $wardrobeItemIds)),
        ]));
    }

    /** GET /recommendations?style=... : aturan rekomendasi hanya ada di FastAPI, Laravel cuma menampilkan */
    public function recommendations(string $style): array
    {
        return array_map(function (array $outfit) {
            $outfit['items'] = array_map(
                fn ($item) => $this->withWardrobeImage(is_array($item) ? $item : []),
                is_array($outfit['items'] ?? null) ? $outfit['items'] : []
            );

            return $outfit;
        }, $this->listFrom($this->send('get', '/recommendations', ['style' => $style])));
    }

    // ---- Virtual Try-On (Anggota 4) ----

    /**
     * POST /try-on/generate multipart: person_image + tepat satu dari wardrobe_item_id / catalog_item_id.
     * Memakai timeout AI yang terpisah karena pembuatan preview lebih lama dari request biasa.
     * Mengembalikan id generasi (untuk disimpan) dan image_url lewat proxy /media/try-on/{id}.
     */
    public function generateTryOn(UploadedFile $personImage, ?int $wardrobeItemId, ?int $catalogItemId): array
    {
        $fields = array_filter(
            ['wardrobe_item_id' => $wardrobeItemId, 'catalog_item_id' => $catalogItemId],
            fn ($value) => $value !== null
        );

        $result = $this->dataFrom($this->upload('/try-on/generate', $fields, ['person_image' => $personImage], $this->aiTimeout()));
        $id = $result['generation_id'] ?? $result['id'] ?? null;

        if (! is_numeric($id)) {
            throw new FastApiException('Terjadi kesalahan di server. Coba lagi nanti.', 502);
        }

        $result['id'] = (int) $id;
        $result['image_url'] = route('media.try-on', ['id' => (int) $id]);

        return $result;
    }

    /** POST /try-on/save JSON {generation_id} */
    public function saveTryOn(int $generationId): array
    {
        return $this->dataFrom($this->send('post', '/try-on/save', ['generation_id' => $generationId]));
    }

    // ---- Gambar privat lewat endpoint spesifik (bukan /files/{path}) ----

    public function wardrobeImage(int $id): array
    {
        return $this->imageFrom($this->request('get', "/wardrobe/{$id}/image"));
    }

    public function tryOnImage(int $id): array
    {
        return $this->imageFrom($this->request('get', "/try-on/{$id}/image"));
    }

    public function file(string $path): array
    {
        // Sepakati dengan Anggota 3: file privat diambil dari GET /api/files/{image_path} dengan JWT pemilik
        return $this->imageFrom($this->request('get', '/files/'.$path));
    }

    protected function client(): PendingRequest
    {
        $request = Http::baseUrl(config('services.fastapi.base_url').'/api')
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(config('services.fastapi.timeout'));

        // JWT dari session dikirim sebagai header Authorization: Bearer <token>
        $token = session('api_token');

        return is_string($token) ? $request->withToken($token) : $request;
    }

    protected function request(string $method, string $uri, array $data = []): Response
    {
        return $this->handle(fn () => $this->client()->{$method}($uri, $data));
    }

    // Kirim multipart (upload foto). $files: nama field => UploadedFile. $timeout dipakai untuk request AI
    protected function upload(string $uri, array $fields, array $files, ?int $timeout = null): array
    {
        $response = $this->handle(function () use ($uri, $fields, $files, $timeout) {
            $pending = $this->client()->asMultipart();

            if ($timeout !== null) {
                $pending = $pending->timeout($timeout);
            }

            foreach ($files as $name => $file) {
                $pending = $pending->attach($name, $file->get(), $file->getClientOriginalName(), ['Content-Type' => (string) $file->getMimeType()]);
            }

            return $pending->post($uri, $fields);
        });

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    // Penanganan error yang sama untuk request biasa dan multipart
    protected function handle(Closure $call): Response
    {
        try {
            $response = $call();
        } catch (ConnectionException) {
            // FastAPI mati atau timeout
            throw new FastApiException('Server StyloMate belum bisa dihubungi. Coba lagi sebentar lagi.', 0);
        }

        if ($response->status() === 422 && ($fieldErrors = $this->fieldErrors($response)) !== []) {
            // Error validasi FastAPI ditampilkan di form seperti error $request->validate()
            throw ValidationException::withMessages($fieldErrors);
        }

        if ($response->failed()) {
            throw new FastApiException($this->messageFor($response->status()), $response->status());
        }

        return $response;
    }

    // Timeout khusus AI (detik); kunci config bisa disesuaikan dengan Anggota 2
    protected function aiTimeout(): int
    {
        return (int) config('services.fastapi.ai_timeout', 90);
    }

    // Hanya teruskan gambar JPEG/PNG/WebP (PRD: upload JPEG/PNG)
    protected function imageFrom(Response $response): array
    {
        $type = strtolower(trim(strtok((string) $response->header('Content-Type'), ';') ?: ''));

        if (! in_array($type, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new FastApiException('File ini tidak bisa ditampilkan.', 502);
        }

        return ['content' => $response->body(), 'type' => $type];
    }

    protected function send(string $method, string $uri, array $data = []): array
    {
        $json = $this->request($method, $uri, $data)->json();

        return is_array($json) ? $json : [];
    }

    private function fieldErrors(Response $response): array
    {
        // Format 422 kontrak PRD v1.1: {"error": {"message": "...", "fields": [{"field": "name", "message": "..."}]}}
        $fields = $response->json('error.fields');
        if (is_array($fields)) {
            $errors = [];
            foreach ($fields as $item) {
                if (is_array($item) && is_string($item['field'] ?? null) && $item['field'] !== '') {
                    $message = is_string($item['message'] ?? null) && $item['message'] !== '' ? $item['message'] : 'Isian ini ditolak server. Periksa lagi.';
                    $errors[$item['field']][] = $message;
                }
            }
            if ($errors !== []) {
                return $errors;
            }
        }

        // Format lama FastAPI: {"detail": [{"loc": ["body", "email"], "msg": "..."}]}
        $detail = $response->json('detail');
        if (! is_array($detail)) {
            return [];
        }

        $errors = [];
        foreach ($detail as $item) {
            $field = is_array($item['loc'] ?? null) ? end($item['loc']) : null;
            if (is_string($field) && $field !== 'body') {
                $errors[$field] = ['Isian ini ditolak server. Periksa lagi.'];
            }
        }

        return $errors;
    }

    protected function messageFor(int $status): string
    {
        return match ($status) {
            401 => 'Sesi kamu sudah berakhir. Silakan masuk lagi.',
            403 => 'Kamu tidak punya akses ke data ini.',
            404 => 'Data yang kamu cari tidak ditemukan.',
            409 => 'Data ini bentrok dengan data yang sudah ada.',
            422 => 'Data yang dikirim belum valid.',
            429 => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
            default => 'Terjadi kesalahan di server. Coba lagi nanti.',
        };
    }

    private function listFrom(array $json): array
    {
        // Sepakati dengan Anggota 3: list berupa array langsung atau dibungkus {"data": [...]}
        $items = $json['data'] ?? $json['items'] ?? $json;

        return array_values(array_filter(is_array($items) ? $items : [], 'is_array'));
    }

    // Respons sukses PRD v1.1 berbentuk {"data": {...}}; bentuk tanpa pembungkus tetap diterima
    private function dataFrom(array $json): array
    {
        return is_array($json['data'] ?? null) ? $json['data'] : $json;
    }

    private function withWardrobeImage(array $item): array
    {
        // Foto pakaian lewat proxy /media/wardrobe/{id} (JWT tetap di server); label kategori berbahasa Indonesia
        $id = $item['id'] ?? null;
        $item['image_url'] = is_numeric($id) ? route('media.wardrobe', ['id' => (int) $id]) : null;
        $category = $item['category'] ?? null;
        $item['category_label'] = self::WARDROBE_CATEGORIES[$category] ?? (is_string($category) ? ucfirst($category) : null);

        return $item;
    }

    private function withCatalogUrls(array $item): array
    {
        // Sepakati dengan Anggota 3: image_path katalog berupa URL penuh atau path relatif dari FASTAPI_BASE_URL
        $path = $item['image_path'] ?? null;
        $item['image_url'] = match (true) {
            ! is_string($path) || $path === '' => null,
            Str::startsWith($path, ['http://', 'https://']) => $path,
            default => config('services.fastapi.base_url').'/'.ltrim($path, '/'),
        };

        // Hanya link http/https yang boleh tampil (cegah href "javascript:")
        $url = $item['product_url'] ?? null;
        $item['product_url'] = is_string($url) && Str::startsWith($url, ['http://', 'https://']) ? $url : null;

        $item['category_label'] = self::CATEGORIES[$item['category'] ?? ''] ?? (is_string($item['category'] ?? null) ? ucfirst($item['category']) : null);

        return $item;
    }

    private function withPrivateImage(array $item, string $key): array
    {
        // Gambar privat lewat route /media milik Laravel (JWT tetap di server), tidak langsung dari FastAPI
        $path = $item[$key] ?? null;
        $item['image_url'] = is_string($path) && $path !== '' ? route('media.show', ['path' => ltrim($path, '/')]) : null;

        // Link unduh memakai route media yang sama dengan ?download=1 (F-10)
        $item['download_url'] = $item['image_url'] !== null ? route('media.show', ['path' => ltrim($path, '/'), 'download' => 1]) : null;

        return $item;
    }
}
