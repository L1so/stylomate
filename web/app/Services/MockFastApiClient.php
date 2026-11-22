<?php

namespace App\Services;

use App\Exceptions\FastApiException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Di luar materi PAWL: data tiruan (mode mock) supaya halaman bisa dicoba sebelum backend FastAPI siap.
// Aktif bila FASTAPI_USE_MOCK=true dan tidak pernah dipakai di production. Bentuk data mengikuti PRD bagian 10.
class MockFastApiClient extends FastApiClient
{
    public const DEMO_EMAIL = 'demo@stylomate.test';

    public const DEMO_PASSWORD = 'password123';

    private const TOKEN_PREFIX = 'mock-token:';

    private const CATALOG = [
        ['id' => 1, 'name' => 'Kemeja Linen Oversize', 'brand' => 'Brand Contoh A', 'category' => 'top', 'color' => 'putih', 'style' => 'semi-formal', 'illustration' => 'kemeja', 'product_url' => 'https://example.com/products/1'],
        ['id' => 2, 'name' => 'Kaos Katun Organik', 'brand' => 'Brand Contoh B', 'category' => 'top', 'color' => 'krem', 'style' => 'casual', 'illustration' => 'kaos', 'product_url' => null],
        ['id' => 3, 'name' => 'Sweater Rajut Daur Ulang', 'brand' => 'Brand Contoh A', 'category' => 'top', 'color' => 'cokelat', 'style' => 'casual', 'illustration' => 'sweater', 'product_url' => 'https://example.com/products/3'],
        ['id' => 4, 'name' => 'Kulot Katun Organik', 'brand' => 'Brand Contoh B', 'category' => 'bottom', 'color' => 'krem', 'style' => 'semi-formal', 'illustration' => 'kulot', 'product_url' => 'https://example.com/products/4'],
        ['id' => 5, 'name' => 'Jeans Lurus Denim Daur Ulang', 'brand' => 'Brand Contoh A', 'category' => 'bottom', 'color' => 'biru', 'style' => 'casual', 'illustration' => 'jeans', 'product_url' => null],
        ['id' => 6, 'name' => 'Rok Plisket Midi', 'brand' => 'Brand Contoh C', 'category' => 'bottom', 'color' => 'terracotta', 'style' => 'formal', 'illustration' => 'rok', 'product_url' => 'https://example.com/products/6'],
        ['id' => 7, 'name' => 'Dress Linen Tali', 'brand' => 'Brand Contoh C', 'category' => 'dress', 'color' => 'krem', 'style' => 'casual', 'illustration' => 'dress', 'product_url' => 'https://example.com/products/7'],
        ['id' => 8, 'name' => 'Blazer Wol Daur Ulang', 'brand' => 'Brand Contoh A', 'category' => 'outerwear', 'color' => 'terracotta', 'style' => 'formal', 'illustration' => 'blazer', 'product_url' => null],
        ['id' => 9, 'name' => 'Sneakers Kanvas', 'brand' => 'Brand Contoh B', 'category' => 'shoes', 'color' => 'putih', 'style' => 'sporty', 'illustration' => 'sepatu', 'product_url' => 'https://example.com/products/9'],
        ['id' => 10, 'name' => 'Tote Bag Kanvas', 'brand' => 'Brand Contoh C', 'category' => 'accessory', 'color' => 'krem', 'style' => 'casual', 'illustration' => 'tas', 'product_url' => null],
    ];

    // Gambar PNG 1x1 sebagai pengganti hasil try-on/wardrobe di mode mock
    private const PLACEHOLDER_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    public function register(string $name, string $email, string $password): array
    {
        $email = Str::lower($email);
        $users = $this->users();

        if (isset($users[$email])) {
            throw new FastApiException('Email sudah terdaftar.', 409);
        }

        $user = ['id' => count($users) + 1, 'name' => $name, 'email' => $email, 'created_at' => now()->toIso8601ZuluString()];
        // Password tetap di-hash walau hanya data tiruan
        $users[$email] = $user + ['password_hash' => Hash::make($password)];
        Cache::forever('mock_users', $users);

        return $user;
    }

    public function login(string $email, string $password): string
    {
        $user = $this->users()[Str::lower($email)] ?? null;

        if ($user === null || ! Hash::check($password, $user['password_hash'])) {
            throw new FastApiException('Email atau kata sandi belum cocok. Periksa lagi.', 401);
        }

        return self::TOKEN_PREFIX.$user['email'];
    }

    public function me(): array
    {
        $email = Str::after((string) session('api_token'), self::TOKEN_PREFIX);
        $user = $this->users()[$email] ?? null;

        if ($user === null) {
            throw new FastApiException('Sesi kamu sudah berakhir. Silakan masuk lagi.', 401);
        }

        return Arr::except($user, 'password_hash');
    }

    private function users(): array
    {
        $users = Cache::get('mock_users', []);

        if (! isset($users[self::DEMO_EMAIL])) {
            $users[self::DEMO_EMAIL] = [
                'id' => 1,
                'name' => 'Pengguna Demo',
                'email' => self::DEMO_EMAIL,
                'created_at' => '2026-11-20T08:00:00Z',
                'password_hash' => Hash::make(self::DEMO_PASSWORD),
            ];
            Cache::forever('mock_users', $users);
        }

        return $users;
    }

    // Gambar privat mock (try-on/wardrobe) dari endpoint tetap per id
    protected function image(string $uri): array
    {
        if (preg_match('#^/(try-on|wardrobe)/\d+/image$#', $uri) !== 1) {
            throw new FastApiException($this->messageFor(404), 404);
        }

        return ['content' => base64_decode(self::PLACEHOLDER_PNG), 'type' => 'image/png'];
    }

    // Meniru endpoint FastAPI untuk katalog, outfit, dan riwayat try-on; mengembalikan isi "data" (sudah dibuka)
    protected function send(string $method, string $uri, array $data = []): array
    {
        if ($method === 'get' && $uri === '/catalog') {
            // Meniru pencarian ?q= (nama/brand) dan filter kategori FastAPI
            $search = Str::lower($data['q'] ?? '');
            $items = array_filter(self::CATALOG, fn (array $item) => ($search === '' || Str::contains(Str::lower($item['name'].' '.$item['brand']), $search))
                && (empty($data['category']) || $item['category'] === $data['category']));

            return array_map(fn (array $item) => $this->withIllustration($item), array_values($items));
        }

        if ($method === 'get' && preg_match('#^/catalog/(\d+)$#', $uri, $match) === 1) {
            return $this->withIllustration($this->findById(self::CATALOG, (int) $match[1]));
        }

        if ($method === 'get' && $uri === '/outfits') {
            return $this->userData('outfits');
        }

        if ($method === 'get' && $uri === '/try-on/history') {
            return $this->userData('try_ons');
        }

        if ($method === 'delete' && preg_match('#^/(outfits|try-on)/(\d+)$#', $uri, $match) === 1) {
            $this->deleteUserData($match[1] === 'outfits' ? 'outfits' : 'try_ons', (int) $match[2]);

            return [];
        }

        throw new FastApiException($this->messageFor(404), 404);
    }

    private function findById(array $rows, int $id): array
    {
        foreach ($rows as $row) {
            if ($row['id'] === $id) {
                return $row;
            }
        }

        throw new FastApiException($this->messageFor(404), 404);
    }

    // Mode mock memakai ilustrasi lokal sebagai gambar produk
    private function withIllustration(array $row): array
    {
        $row['image_url'] = asset('images/illustrations/'.$row['illustration'].'.svg');
        unset($row['illustration']);

        return $row;
    }

    private function userData(string $type): array
    {
        // Data per pengguna disimpan di cache supaya hapus outfit/try-on bisa dicoba (v2 = bentuk kontrak FastAPI final)
        return Cache::rememberForever("mock_v2_{$type}:".$this->me()['email'], fn () => $type === 'outfits'
            ? [
                ['id' => 1, 'name' => 'Santai ke Kampus', 'wardrobe_item_ids' => [11, 12]],
                ['id' => 2, 'name' => 'Kuliah hari Senin', 'wardrobe_item_ids' => [13, 14]],
            ]
            : [
                ['id' => 1, 'wardrobe_item_id' => null, 'catalog_item_id' => 1, 'image_url' => '/api/try-on/1/image', 'created_at' => '2026-11-21T10:00:00Z'],
                ['id' => 2, 'wardrobe_item_id' => 11, 'catalog_item_id' => null, 'image_url' => '/api/try-on/2/image', 'created_at' => '2026-11-22T07:15:00Z'],
            ]);
    }

    private function deleteUserData(string $type, int $id): void
    {
        $rows = $this->userData($type);
        $this->findById($rows, $id);
        Cache::forever("mock_v2_{$type}:".$this->me()['email'], array_values(array_filter($rows, fn (array $row) => $row['id'] !== $id)));
    }
}
