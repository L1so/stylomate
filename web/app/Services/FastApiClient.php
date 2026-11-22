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
    // Kategori backend FastAPI (kontrak final) beserta label UI
    public const CATEGORIES = [
        'top' => 'Atasan',
        'bottom' => 'Bawahan',
        'dress' => 'Dress',
        'outerwear' => 'Outer',
        'shoes' => 'Sepatu',
        'accessory' => 'Aksesori',
    ];

    // Style yang diterima FastAPI untuk Wardrobe dan Recommendations (kontrak final)
    public const STYLES = [
        'casual' => 'Casual',
        'formal' => 'Formal',
        'semi-formal' => 'Semi-formal',
        'sporty' => 'Sporty',
    ];

    // Hanya gambar JPEG/PNG/WebP yang diteruskan ke browser (PRD: upload JPEG/PNG)
    public const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function register(string $name, string $email, string $password): array
    {
        return $this->send('post', '/auth/register', compact('name', 'email', 'password'));
    }

    public function login(string $email, string $password): string
    {
        // Respons: {"data": {"access_token", "token_type", "expires_in", "user"}}
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

    public function catalog(string $search = '', string $category = ''): array
    {
        // UI memakai ?search=, backend FastAPI memakai ?q=
        $query = array_filter(['q' => $search, 'category' => $category], fn (string $value) => $value !== '');

        return array_map(fn (array $item) => $this->withCatalogUrls($item), $this->listOf($this->send('get', '/catalog', $query)));
    }

    public function catalogItem(int $id): array
    {
        return $this->withCatalogUrls($this->send('get', "/catalog/{$id}"));
    }

    public function outfits(): array
    {
        return array_map(function (array $outfit) {
            // Outfit hanya membawa wardrobe_item_ids; gambar tiap item diambil lewat proxy Laravel
            $ids = array_filter(is_array($outfit['wardrobe_item_ids'] ?? null) ? $outfit['wardrobe_item_ids'] : [], 'is_int');
            $outfit['wardrobe_images'] = array_values(array_map(fn (int $id) => route('media.wardrobe', ['id' => $id]), $ids));

            return $outfit;
        }, $this->listOf($this->send('get', '/outfits')));
    }

    public function deleteOutfit(int $id): void
    {
        $this->send('delete', "/outfits/{$id}");
    }

    public function tryOnHistory(): array
    {
        return array_map(function (array $result) {
            // image_url dari backend (/api/try-on/{id}/image) butuh JWT, jadi browser memakai proxy Laravel ber-id
            $id = $result['id'] ?? null;
            $result['image_url'] = is_int($id) ? route('media.try-on', ['id' => $id]) : null;
            $result['download_url'] = is_int($id) ? route('media.try-on', ['id' => $id, 'download' => 1]) : null;

            return $result;
        }, $this->listOf($this->send('get', '/try-on/history')));
    }

    public function deleteTryOn(int $id): void
    {
        $this->send('delete', "/try-on/{$id}");
    }

    // ---- Wardrobe (Anggota 4) ----

    public function wardrobeItems(): array
    {
        return array_map(fn (array $item) => $this->withWardrobeImage($item), $this->listOf($this->send('get', '/wardrobe')));
    }

    public function wardrobeItem(int $id): array
    {
        return $this->withWardrobeImage($this->send('get', "/wardrobe/{$id}"));
    }

    /** POST /wardrobe multipart: name, category, color, style, image */
    public function createWardrobeItem(array $fields, UploadedFile $image): array
    {
        return $this->withWardrobeImage($this->upload('post', '/wardrobe', $fields, ['image' => $image]));
    }

    /** PATCH /wardrobe/{id} multipart: name, category, color, style (opsional), image (opsional) */
    public function updateWardrobeItem(int $id, array $fields, ?UploadedFile $image = null): array
    {
        return $this->withWardrobeImage($this->upload('patch', "/wardrobe/{$id}", $fields, $image === null ? [] : ['image' => $image]));
    }

    /** DELETE /wardrobe/{id}; FastAPI membalas 409 bila item masih dipakai outfit atau hasil Try-On */
    public function deleteWardrobeItem(int $id): void
    {
        $this->send('delete', "/wardrobe/{$id}");
    }

    // ---- Mix & Match dan Smart Outfit Curation (Anggota 4) ----

    /** POST /outfits JSON {name, wardrobe_item_ids}; FastAPI menolak outfit tanpa top dan bottom */
    public function createOutfit(string $name, array $wardrobeItemIds): array
    {
        return $this->send('post', '/outfits', [
            'name' => $name,
            'wardrobe_item_ids' => array_values(array_map('intval', $wardrobeItemIds)),
        ]);
    }

    /** GET /recommendations?style=... : aturan rekomendasi hanya ada di FastAPI. Tiap hasil: {wardrobe_item_ids, items[]} */
    public function recommendations(string $style): array
    {
        return array_map(function (array $outfit) {
            $outfit['items'] = array_values(array_map(
                fn ($item) => $this->withWardrobeImage(is_array($item) ? $item : []),
                array_filter(is_array($outfit['items'] ?? null) ? $outfit['items'] : [], 'is_array')
            ));

            return $outfit;
        }, $this->listOf($this->send('get', '/recommendations', ['style' => $style])));
    }

    // ---- Virtual Try-On (Anggota 4) ----

    /**
     * POST /try-on/generate multipart: person_image + tepat satu dari wardrobe_item_id / catalog_item_id.
     * Memakai timeout AI yang terpisah. Tidak ada retry otomatis: generate bisa saja selesai di server walau timeout.
     * Mengembalikan hasil FastAPI dengan image_url berupa proxy Laravel /media/try-on/{id}.
     */
    public function generateTryOn(UploadedFile $personImage, ?int $wardrobeItemId, ?int $catalogItemId): array
    {
        $fields = array_filter(
            ['wardrobe_item_id' => $wardrobeItemId, 'catalog_item_id' => $catalogItemId],
            fn ($value) => $value !== null
        );

        $result = $this->upload('post', '/try-on/generate', $fields, ['person_image' => $personImage], $this->aiTimeout());
        $id = $result['id'] ?? $result['generation_id'] ?? null;

        if (! is_int($id)) {
            throw new FastApiException('Terjadi kesalahan di server. Coba lagi nanti.', 502);
        }

        $result['id'] = $id;
        $result['generation_id'] = is_int($result['generation_id'] ?? null) ? $result['generation_id'] : $id;
        $result['image_url'] = route('media.try-on', ['id' => $id]);

        return $result;
    }

    public function warmTryOn(): void
    {
        try {
            $this->client()->connectTimeout(2)->timeout(5)->post('/try-on/warmup');
        } catch (ConnectionException) {
            // Warmup is best effort. Generate checks readiness before sending its images.
        }
    }

    /** POST /try-on/save JSON {generation_id} */
    public function saveTryOn(int $generationId): array
    {
        return $this->send('post', '/try-on/save', ['generation_id' => $generationId]);
    }

    public function tryOnImage(int $id): array
    {
        return $this->image("/try-on/{$id}/image");
    }

    public function wardrobeImage(int $id): array
    {
        return $this->image("/wardrobe/{$id}/image");
    }

    protected function image(string $uri): array
    {
        $response = $this->request('get', $uri);
        $type = strtolower(trim(strtok((string) $response->header('Content-Type'), ';') ?: ''));

        if (! array_key_exists($type, self::IMAGE_TYPES)) {
            throw new FastApiException('File ini tidak bisa ditampilkan.', 502);
        }

        return ['content' => $response->body(), 'type' => $type];
    }

    protected function client(): PendingRequest
    {
        // FASTAPI_BASE_URL sudah termasuk /api, jadi endpoint ditulis relatif (/auth/login), bukan /api/auth/login
        $request = Http::baseUrl(config('services.fastapi.base_url'))
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

    // Kirim multipart (upload foto). $files: nama field => UploadedFile. Mengembalikan isi "data". $timeout dipakai untuk request AI
    protected function upload(string $method, string $uri, array $fields, array $files, ?int $timeout = null): array
    {
        $response = $this->handle(function () use ($method, $uri, $fields, $files, $timeout) {
            $pending = $this->client()->asMultipart();

            if ($timeout !== null) {
                $pending = $pending->timeout($timeout);
            }

            foreach ($files as $name => $file) {
                $pending = $pending->attach($name, $file->get(), $file->getClientOriginalName(), ['Content-Type' => (string) $file->getMimeType()]);
            }

            return $pending->{$method}($uri, $fields);
        });

        $data = $response->json('data');

        return is_array($data) ? $data : [];
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

    // Timeout khusus AI (detik); harus lebih besar dari AI_TIMEOUT_SECONDS di FastAPI (default 300)
    protected function aiTimeout(): int
    {
        return (int) config('services.fastapi.ai_timeout', 330);
    }

    protected function send(string $method, string $uri, array $data = []): array
    {
        // Semua respons sukses FastAPI dibungkus {"data": ...}; respons tanpa body (204) menjadi []
        $data = $this->request($method, $uri, $data)->json('data');

        return is_array($data) ? $data : [];
    }

    private function fieldErrors(Response $response): array
    {
        // Format 422: {"error": {"message": "...", "fields": [{"field": "email", "message": "..."}]}}
        $fields = $response->json('error.fields');
        if (! is_array($fields)) {
            return [];
        }

        $errors = [];
        foreach ($fields as $field) {
            $name = $field['field'] ?? null;
            if (is_string($name) && $name !== '') {
                $message = $field['message'] ?? null;
                $errors[$name][] = is_string($message) && $message !== '' ? $message : 'Isian ini ditolak server. Periksa lagi.';
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
            410 => 'Preview ini sudah kedaluwarsa. Buat preview baru.',
            413 => 'Ukuran foto terlalu besar. Maksimal 10 MB.',
            422 => 'Data yang dikirim belum valid.',
            429 => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
            503 => 'Layanan AI sedang tidak tersedia. Coba lagi nanti.',
            504 => 'AI terlalu lama merespons. Coba lagi sebentar lagi.',
            default => 'Terjadi kesalahan di server. Coba lagi nanti.',
        };
    }

    private function listOf(array $data): array
    {
        return array_values(array_filter($data, 'is_array'));
    }

    private function withWardrobeImage(array $item): array
    {
        // image_url dari backend (/api/wardrobe/{id}/image) butuh JWT, jadi browser memakai proxy Laravel ber-id
        $id = $item['id'] ?? null;
        $item['image_url'] = is_int($id) ? route('media.wardrobe', ['id' => $id]) : null;
        $category = $item['category'] ?? null;
        $item['category_label'] = self::CATEGORIES[$category] ?? (is_string($category) ? ucfirst($category) : null);

        return $item;
    }

    private function withCatalogUrls(array $item): array
    {
        // Gambar Catalog publik: browser memuatnya langsung dari FastAPI lewat image_url
        $item['image_url'] = $this->absoluteUrl($item['image_url'] ?? null);

        // Hanya link http/https yang boleh tampil (cegah href "javascript:")
        $url = $item['product_url'] ?? null;
        $item['product_url'] = is_string($url) && Str::startsWith($url, ['http://', 'https://']) ? $url : null;

        $item['category_label'] = self::CATEGORIES[$item['category'] ?? ''] ?? (is_string($item['category'] ?? null) ? ucfirst($item['category']) : null);

        return $item;
    }

    private function absoluteUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        $base = config('services.fastapi.base_url');
        if (! str_starts_with($url, '/')) {
            return $base.'/'.$url;
        }

        // Path absolut dari backend (/api/catalog/1/image) ditempel ke origin FastAPI, bukan ke base URL, agar tidak jadi /api/api
        $parts = parse_url($base);

        return ($parts['scheme'] ?? 'http').'://'.($parts['host'] ?? '').(isset($parts['port']) ? ':'.$parts['port'] : '').$url;
    }
}
