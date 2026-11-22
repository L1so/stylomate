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
    // Kategori yang dipakai filter Catalog. Sepakati nilai kategori dengan Anggota 3
    public const CATEGORIES = ['top' => 'Atasan', 'bottom' => 'Bawahan'];

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

    public function file(string $path): array
    {
        // Sepakati dengan Anggota 3: file privat diambil dari GET /api/files/{image_path} dengan JWT pemilik
        $response = $this->request('get', '/files/'.$path);
        $type = strtolower(trim(strtok((string) $response->header('Content-Type'), ';') ?: ''));

        // Hanya teruskan gambar JPEG/PNG/WebP (PRD: upload JPEG/PNG)
        if (! in_array($type, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new FastApiException('File ini tidak bisa ditampilkan.', 502);
        }

        return ['content' => $response->body(), 'type' => $type];
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
        $json = $this->request($method, $uri, $data)->json();

        return is_array($json) ? $json : [];
    }

    private function fieldErrors(Response $response): array
    {
        // Format 422 FastAPI: {"detail": [{"loc": ["body", "email"], "msg": "..."}]}
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
