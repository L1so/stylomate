<?php

namespace App\Services;

use App\Exceptions\FastApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
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
        try {
            $response = $this->client()->{$method}($uri, $data);
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
            422 => 'Data yang dikirim belum valid.',
            429 => 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
            default => 'Terjadi kesalahan di server. Coba lagi nanti.',
        };
    }

    private function listOf(array $data): array
    {
        return array_values(array_filter($data, 'is_array'));
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
