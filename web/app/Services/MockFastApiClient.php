<?php

namespace App\Services;

use App\Exceptions\FastApiException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// Di luar materi PAWL: data tiruan (mode mock) supaya halaman bisa dicoba sebelum backend FastAPI siap.
// Aktif bila FASTAPI_USE_MOCK=true dan tidak pernah dipakai di production. Bentuk data mengikuti PRD bagian 10.
class MockFastApiClient extends FastApiClient
{
    public const DEMO_EMAIL = 'demo@stylomate.test';

    public const DEMO_PASSWORD = 'password123';

    private const TOKEN_PREFIX = 'mock-token:';

    private const CATALOG = [
        ['id' => 1, 'name' => 'Kemeja Linen Oversize', 'brand' => 'Brand Contoh A', 'category' => 'top', 'color' => 'putih', 'style' => 'semi-formal', 'illustration' => 'kemeja', 'image_path' => null, 'product_url' => 'https://example.com/products/1'],
        ['id' => 2, 'name' => 'Kaos Katun Organik', 'brand' => 'Brand Contoh B', 'category' => 'top', 'color' => 'krem', 'style' => 'casual', 'illustration' => 'kaos', 'image_path' => null, 'product_url' => null],
        ['id' => 3, 'name' => 'Sweater Rajut Daur Ulang', 'brand' => 'Brand Contoh A', 'category' => 'top', 'color' => 'cokelat', 'style' => 'casual', 'illustration' => 'sweater', 'image_path' => null, 'product_url' => 'https://example.com/products/3'],
        ['id' => 4, 'name' => 'Kemeja Tencel Kerah', 'brand' => 'Brand Contoh C', 'category' => 'top', 'color' => 'putih', 'style' => 'formal', 'illustration' => 'kemeja', 'image_path' => null, 'product_url' => null],
        ['id' => 5, 'name' => 'Kulot Katun Organik', 'brand' => 'Brand Contoh B', 'category' => 'bottom', 'color' => 'krem', 'style' => 'semi-formal', 'illustration' => 'kulot', 'image_path' => null, 'product_url' => 'https://example.com/products/5'],
        ['id' => 6, 'name' => 'Jeans Lurus Denim Daur Ulang', 'brand' => 'Brand Contoh A', 'category' => 'bottom', 'color' => 'biru', 'style' => 'casual', 'illustration' => 'jeans', 'image_path' => null, 'product_url' => null],
        ['id' => 7, 'name' => 'Rok Plisket Midi', 'brand' => 'Brand Contoh C', 'category' => 'bottom', 'color' => 'terracotta', 'style' => 'formal', 'illustration' => 'rok', 'image_path' => null, 'product_url' => 'https://example.com/products/7'],
        ['id' => 8, 'name' => 'Celana Jogger Katun', 'brand' => 'Brand Contoh B', 'category' => 'bottom', 'color' => 'hitam', 'style' => 'sporty', 'illustration' => 'jeans', 'image_path' => null, 'product_url' => null],
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

    public function file(string $path): array
    {
        if (! Str::startsWith($path, ['wardrobe/', 'try-on/'])) {
            throw new FastApiException($this->messageFor(404), 404);
        }

        return ['content' => base64_decode(self::PLACEHOLDER_PNG), 'type' => 'image/png'];
    }

    // Gambar privat Anggota 4 (GET /wardrobe/{id}/image dan /try-on/{id}/image): selalu PNG placeholder
    public function wardrobeImage(int $id): array
    {
        $this->findById($this->userData('wardrobe'), $id);

        return ['content' => base64_decode(self::PLACEHOLDER_PNG), 'type' => 'image/png'];
    }

    public function tryOnImage(int $id): array
    {
        $known = array_merge($this->userData('try_ons'), $this->generations());
        $this->findById($known, $id);

        return ['content' => base64_decode(self::PLACEHOLDER_PNG), 'type' => 'image/png'];
    }

    // Meniru multipart FastAPI: tambah pakaian dan generate try-on (tanpa AI sungguhan)
    protected function upload(string $uri, array $fields, array $files, ?int $timeout = null): array
    {
        if ($uri === '/wardrobe') {
            $this->requireImage($files, 'image');
            $item = $this->validatedWardrobeFields($fields) + [
                'id' => $this->nextId($this->userData('wardrobe')),
                'created_at' => now()->toIso8601ZuluString(),
            ];
            $item['image_path'] = "wardrobe/{$item['id']}.png";
            $this->storeUserData('wardrobe', [...$this->userData('wardrobe'), $item]);

            return ['data' => $item];
        }

        if ($uri === '/try-on/generate') {
            $this->requireImage($files, 'person_image');
            $wardrobeId = $fields['wardrobe_item_id'] ?? null;
            $catalogId = $fields['catalog_item_id'] ?? null;

            if (($wardrobeId === null) === ($catalogId === null)) {
                throw ValidationException::withMessages(['wardrobe_item_id' => ['Pilih tepat satu pakaian.']]);
            }

            $source = $wardrobeId !== null
                ? $this->findById($this->userData('wardrobe'), (int) $wardrobeId)
                : $this->findById(self::CATALOG, (int) $catalogId);

            if ($source['category'] !== 'top') {
                throw ValidationException::withMessages([$wardrobeId !== null ? 'wardrobe_item_id' : 'catalog_item_id' => ['Try On saat ini baru mendukung atasan.']]);
            }

            $generations = $this->generations();
            $generation = [
                'id' => $this->nextId($generations, 1000),
                'wardrobe_item_id' => $wardrobeId !== null ? (int) $wardrobeId : null,
                'catalog_item_id' => $catalogId !== null ? (int) $catalogId : null,
            ];
            Cache::forever('mock_generations:'.$this->me()['email'], [...$generations, $generation]);

            return ['data' => $generation + ['status' => 'completed']];
        }

        throw new FastApiException($this->messageFor(404), 404);
    }

    // Meniru endpoint FastAPI untuk katalog, outfit, dan riwayat try-on
    protected function send(string $method, string $uri, array $data = []): array
    {
        if ($method === 'get' && $uri === '/catalog') {
            // Meniru pencarian (nama/brand) dan filter kategori FastAPI
            $search = Str::lower($data['search'] ?? '');
            $items = array_filter(self::CATALOG, fn (array $item) => ($search === '' || Str::contains(Str::lower($item['name'].' '.$item['brand']), $search))
                && (empty($data['category']) || $item['category'] === $data['category']));

            return ['data' => array_map(fn (array $item) => $this->withIllustration($item), array_values($items))];
        }

        if ($method === 'get' && preg_match('#^/catalog/(\d+)$#', $uri, $match) === 1) {
            return $this->withIllustration($this->findById(self::CATALOG, (int) $match[1]));
        }

        if ($method === 'get' && $uri === '/outfits') {
            return ['data' => $this->userData('outfits')];
        }

        if ($method === 'get' && $uri === '/try-on/history') {
            return ['data' => $this->userData('try_ons')];
        }

        if ($method === 'delete' && preg_match('#^/(outfits|try-on)/(\d+)$#', $uri, $match) === 1) {
            $this->deleteUserData($match[1] === 'outfits' ? 'outfits' : 'try_ons', (int) $match[2]);

            return [];
        }

        // ---- Wardrobe, outfit baru, rekomendasi, dan simpan try-on (Anggota 4) ----
        if ($method === 'get' && $uri === '/wardrobe') {
            return ['data' => $this->userData('wardrobe')];
        }

        if (preg_match('#^/wardrobe/(\d+)$#', $uri, $match) === 1) {
            $id = (int) $match[1];
            $item = $this->findById($this->userData('wardrobe'), $id);

            if ($method === 'get') {
                return ['data' => $item];
            }

            if ($method === 'patch') {
                $item = $this->validatedWardrobeFields($data) + $item;
                $this->storeUserData('wardrobe', array_map(fn (array $row) => $row['id'] === $id ? $item : $row, $this->userData('wardrobe')));

                return ['data' => $item];
            }

            if ($method === 'delete') {
                $this->deleteUserData('wardrobe', $id);

                return [];
            }
        }

        if ($method === 'post' && $uri === '/outfits') {
            $wardrobe = collect($this->userData('wardrobe'))->keyBy('id');
            $items = collect($data['wardrobe_item_ids'] ?? [])->map(fn ($id) => $wardrobe[(int) $id] ?? null);

            if ($items->contains(fn ($item) => $item === null) || ! $items->contains('category', 'top') || ! $items->contains('category', 'bottom')) {
                throw ValidationException::withMessages(['wardrobe_item_ids' => ['Outfit harus punya minimal satu atasan dan satu bawahan.']]);
            }

            $outfits = $this->userData('outfits');
            $outfit = [
                'id' => $this->nextId($outfits),
                'name' => (string) ($data['name'] ?? ''),
                'created_at' => now()->toIso8601ZuluString(),
                'items' => $items->map(fn (array $item) => Arr::only($item, ['id', 'name', 'category', 'image_path']))->values()->all(),
            ];
            $this->storeUserData('outfits', [...$outfits, $outfit]);

            return ['data' => $outfit];
        }

        if ($method === 'get' && $uri === '/recommendations') {
            // Aturan sederhana khusus mock: padankan atasan dan bawahan Wardrobe dengan style sama (aturan asli ada di FastAPI)
            $style = (string) ($data['style'] ?? '');
            $wardrobe = collect($this->userData('wardrobe'));
            $tops = $wardrobe->where('category', 'top')->where('style', $style)->values();
            $bottoms = $wardrobe->where('category', 'bottom')->where('style', $style)->values();
            $outfits = [];

            foreach ($tops as $top) {
                foreach ($bottoms as $bottom) {
                    $outfits[] = [
                        'name' => 'Rekomendasi '.$style.' '.(count($outfits) + 1),
                        'reason' => "{$top['name']} dan {$bottom['name']} sama-sama bergaya {$style}.",
                        'items' => [$top, $bottom],
                    ];
                }
            }

            return ['data' => array_slice($outfits, 0, 3)];
        }

        if ($method === 'post' && $uri === '/try-on/save') {
            $generation = $this->findById($this->generations(), (int) ($data['generation_id'] ?? 0));
            $tryOns = $this->userData('try_ons');
            $saved = [
                'id' => $this->nextId($tryOns),
                'wardrobe_item_id' => $generation['wardrobe_item_id'],
                'catalog_item_id' => $generation['catalog_item_id'],
                'result_image_path' => 'try-on/'.$generation['id'].'.png',
                'created_at' => now()->toIso8601ZuluString(),
            ];
            $this->storeUserData('try_ons', [...$tryOns, $saved]);

            return ['data' => $saved];
        }

        throw new FastApiException($this->messageFor(404), 404);
    }

    private function generations(): array
    {
        return Cache::get('mock_generations:'.$this->me()['email'], []);
    }

    private function nextId(array $rows, int $start = 1): int
    {
        return $rows === [] ? $start : max(array_column($rows, 'id')) + 1;
    }

    private function requireImage(array $files, string $field): void
    {
        if (! ($files[$field] ?? null) instanceof UploadedFile) {
            throw ValidationException::withMessages([$field => ['Foto wajib diunggah.']]);
        }
    }

    private function validatedWardrobeFields(array $fields): array
    {
        $errors = [];
        foreach (['name', 'color'] as $key) {
            if (! is_string($fields[$key] ?? null) || trim($fields[$key]) === '') {
                $errors[$key] = ['Isian ini wajib diisi.'];
            }
        }
        if (! array_key_exists($fields['category'] ?? '', self::WARDROBE_CATEGORIES)) {
            $errors['category'] = ['Kategori tidak valid.'];
        }
        if (! array_key_exists($fields['style'] ?? '', self::STYLES)) {
            $errors['style'] = ['Style tidak valid.'];
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return Arr::only($fields, ['name', 'category', 'color', 'style']);
    }

    private function storeUserData(string $type, array $rows): void
    {
        Cache::forever("mock_{$type}:".$this->me()['email'], array_values($rows));
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
        $row['image_path'] = asset('images/illustrations/'.$row['illustration'].'.svg');
        unset($row['illustration']);

        return $row;
    }

    private function userData(string $type): array
    {
        // Data per pengguna disimpan di cache supaya tambah/ubah/hapus bisa dicoba
        return Cache::rememberForever("mock_{$type}:".$this->me()['email'], fn () => match ($type) {
            'wardrobe' => [
                ['id' => 11, 'name' => 'Kaos Garis', 'category' => 'top', 'color' => 'putih', 'style' => 'casual', 'image_path' => 'wardrobe/11.png', 'created_at' => '2026-11-18T09:00:00Z'],
                ['id' => 12, 'name' => 'Jeans Denim', 'category' => 'bottom', 'color' => 'biru', 'style' => 'casual', 'image_path' => 'wardrobe/12.png', 'created_at' => '2026-11-18T09:05:00Z'],
                ['id' => 13, 'name' => 'Kemeja Putih', 'category' => 'top', 'color' => 'putih', 'style' => 'formal', 'image_path' => 'wardrobe/13.png', 'created_at' => '2026-11-19T08:00:00Z'],
                ['id' => 14, 'name' => 'Celana Bahan', 'category' => 'bottom', 'color' => 'hitam', 'style' => 'formal', 'image_path' => 'wardrobe/14.png', 'created_at' => '2026-11-19T08:10:00Z'],
                ['id' => 15, 'name' => 'Sepatu Putih', 'category' => 'shoes', 'color' => 'putih', 'style' => 'casual', 'image_path' => 'wardrobe/15.png', 'created_at' => '2026-11-19T08:20:00Z'],
            ],
            'outfits' => [
                ['id' => 1, 'name' => 'Santai ke Kampus', 'created_at' => '2026-11-20T09:00:00Z', 'items' => [
                    ['id' => 11, 'name' => 'Kaos Garis', 'category' => 'top', 'image_path' => 'wardrobe/11.png'],
                    ['id' => 12, 'name' => 'Jeans Denim', 'category' => 'bottom', 'image_path' => 'wardrobe/12.png'],
                ]],
                ['id' => 2, 'name' => 'Kuliah hari Senin', 'created_at' => '2026-11-21T08:30:00Z', 'items' => [
                    ['id' => 13, 'name' => 'Kemeja Putih', 'category' => 'top', 'image_path' => 'wardrobe/13.png'],
                    ['id' => 14, 'name' => 'Celana Bahan', 'category' => 'bottom', 'image_path' => 'wardrobe/14.png'],
                ]],
            ],
            default => [
                ['id' => 1, 'wardrobe_item_id' => null, 'catalog_item_id' => 1, 'result_image_path' => 'try-on/1.png', 'created_at' => '2026-11-21T10:00:00Z'],
                ['id' => 2, 'wardrobe_item_id' => 11, 'catalog_item_id' => null, 'result_image_path' => 'try-on/2.png', 'created_at' => '2026-11-22T07:15:00Z'],
            ],
        });
    }

    private function deleteUserData(string $type, int $id): void
    {
        $rows = $this->userData($type);
        $this->findById($rows, $id);
        Cache::forever("mock_{$type}:".$this->me()['email'], array_values(array_filter($rows, fn (array $row) => $row['id'] !== $id)));
    }
}
