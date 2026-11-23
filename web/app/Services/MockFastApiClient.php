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
    public const DEMO_EMAIL = 'demo@stylomate.id';

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
    protected function image(string $uri, array $query = []): array
    {
        if (preg_match('#^/(try-on|wardrobe)/\d+/image$#', $uri) !== 1) {
            throw new FastApiException($this->messageFor(404), 404);
        }

        return ['content' => base64_decode(self::PLACEHOLDER_PNG), 'type' => 'image/png'];
    }

    // Meniru multipart FastAPI (tambah/ubah pakaian, generate try-on) tanpa AI sungguhan; mengembalikan isi "data"
    protected function upload(string $method, string $uri, array $fields, array $files, ?int $timeout = null): array
    {
        if ($method === 'post' && $uri === '/wardrobe') {
            $this->requireImage($files, 'image');
            $wardrobe = $this->userData('wardrobe');
            $item = $this->validatedWardrobeFields($fields, true) + ['id' => $this->nextId($wardrobe)];
            $item['image_url'] = '/api/wardrobe/'.$item['id'].'/image';
            $this->storeUserData('wardrobe', [...$wardrobe, $item]);

            return $item;
        }

        if ($method === 'patch' && preg_match('#^/wardrobe/(\d+)$#', $uri, $match) === 1) {
            $id = (int) $match[1];
            $item = $this->validatedWardrobeFields($fields, false) + $this->findById($this->userData('wardrobe'), $id);
            $this->storeUserData('wardrobe', array_map(fn (array $row) => $row['id'] === $id ? $item : $row, $this->userData('wardrobe')));

            return $item;
        }

        if ($method === 'post' && $uri === '/try-on/generate') {
            $this->requireImage($files, 'person_image');
            $wardrobeId = $fields['wardrobe_item_id'] ?? null;
            $catalogId = $fields['catalog_item_id'] ?? null;
            $outfitId = $fields['outfit_id'] ?? null;

            if (count(array_filter([$wardrobeId, $catalogId, $outfitId], fn ($value) => $value !== null)) !== 1) {
                throw ValidationException::withMessages(['item' => ['Pilih tepat satu pakaian atau outfit.']]);
            }

            if ($outfitId !== null) {
                $outfit = $this->findById($this->userData('outfits'), (int) $outfitId);
                $wardrobe = collect($this->userData('wardrobe'))->keyBy('id');
                $items = collect($outfit['wardrobe_item_ids'])->map(fn ($id) => $wardrobe[(int) $id] ?? null)->filter();
                if ($items->where('category', 'top')->count() !== 1 || $items->where('category', 'bottom')->count() !== 1) {
                    throw ValidationException::withMessages(['outfit_id' => ['Outfit harus berisi tepat satu atasan dan satu bawahan.']]);
                }
            } else {
                $source = $wardrobeId !== null
                    ? $this->findById($this->userData('wardrobe'), (int) $wardrobeId)
                    : $this->findById(self::CATALOG, (int) $catalogId);

                if (! in_array($source['category'], ['top', 'bottom'], true)) {
                    throw ValidationException::withMessages([$wardrobeId !== null ? 'wardrobe_item_id' : 'catalog_item_id' => ['Try On hanya mendukung atasan dan bawahan.']]);
                }
            }

            // Seperti backend asli: hasil dibuat dengan saved=false (preview) dan baru masuk riwayat setelah /try-on/save
            $tryOns = $this->userData('try_ons');
            $id = $this->nextId($tryOns);
            $result = [
                'id' => $id,
                'generation_id' => $id,
                'saved' => false,
                'image_url' => '/api/try-on/'.$id.'/image',
                'wardrobe_item_id' => $wardrobeId !== null ? (int) $wardrobeId : null,
                'catalog_item_id' => $catalogId !== null ? (int) $catalogId : null,
                'outfit_id' => $outfitId !== null ? (int) $outfitId : null,
                'created_at' => now()->toIso8601ZuluString(),
            ];
            $this->storeUserData('try_ons', [...$tryOns, $result]);

            return $result;
        }

        throw new FastApiException($this->messageFor(404), 404);
    }

    // Meniru endpoint FastAPI untuk katalog, outfit, dan riwayat try-on; mengembalikan isi "data" (sudah dibuka)
    protected function send(string $method, string $uri, array $data = []): array
    {
        if ($method === 'get' && preg_match('#^/catalog/(\d+)$#', $uri, $match) === 1) {
            return $this->withIllustration($this->findById(self::CATALOG, (int) $match[1]));
        }

        if ($method === 'get' && $uri === '/outfits') {
            return $this->userData('outfits');
        }

        if ($method === 'get' && $uri === '/try-on/history') {
            return array_values(array_filter($this->userData('try_ons'), fn (array $row) => $row['saved'] ?? false));
        }

        if ($method === 'delete' && preg_match('#^/(outfits|try-on)/(\d+)$#', $uri, $match) === 1) {
            $this->deleteUserData($match[1] === 'outfits' ? 'outfits' : 'try_ons', (int) $match[2]);

            return [];
        }

        // ---- Wardrobe, outfit baru, rekomendasi, dan simpan try-on (Anggota 4) ----
        if ($method === 'get' && $uri === '/wardrobe') {
            return $this->userData('wardrobe');
        }

        if (preg_match('#^/wardrobe/(\d+)$#', $uri, $match) === 1) {
            $id = (int) $match[1];
            $item = $this->findById($this->userData('wardrobe'), $id);

            if ($method === 'get') {
                return $item;
            }

            if ($method === 'delete') {
                // Seperti backend: item yang masih dipakai outfit atau try-on tidak boleh dihapus (409)
                $used = collect($this->userData('outfits'))->contains(fn (array $outfit) => in_array($id, $outfit['wardrobe_item_ids'], true))
                    || collect($this->userData('try_ons'))->contains('wardrobe_item_id', $id);
                if ($used) {
                    throw new FastApiException($this->messageFor(409), 409);
                }

                $this->deleteUserData('wardrobe', $id);

                return [];
            }
        }

        if ($method === 'post' && $uri === '/outfits') {
            $wardrobe = collect($this->userData('wardrobe'))->keyBy('id');
            $ids = array_values(array_unique(array_map('intval', $data['wardrobe_item_ids'] ?? [])));
            $items = collect($ids)->map(fn (int $id) => $wardrobe[$id] ?? null);

            if ($items->contains(fn ($item) => $item === null)) {
                throw new FastApiException($this->messageFor(404), 404);
            }

            if (! $items->contains('category', 'top') || ! $items->contains('category', 'bottom')) {
                throw ValidationException::withMessages(['wardrobe_item_ids' => ['Outfit harus punya minimal satu atasan dan satu bawahan.']]);
            }

            $outfits = $this->userData('outfits');
            $outfit = ['id' => $this->nextId($outfits), 'name' => (string) ($data['name'] ?? ''), 'wardrobe_item_ids' => $ids];
            $this->storeUserData('outfits', [...$outfits, $outfit]);

            return $outfit;
        }

        if ($method === 'get' && $uri === '/recommendations') {
            // Aturan sederhana khusus mock: pasangkan atasan dan bawahan Wardrobe dengan style sama, maksimal 5 (aturan asli ada di FastAPI)
            $style = (string) ($data['style'] ?? 'casual');
            $wardrobe = collect($this->userData('wardrobe'))->where('style', $style);
            $outfits = [];

            foreach ($wardrobe->where('category', 'top') as $top) {
                foreach ($wardrobe->where('category', 'bottom') as $bottom) {
                    $outfits[] = ['wardrobe_item_ids' => [$top['id'], $bottom['id']], 'items' => [$top, $bottom]];
                }
            }

            return array_slice($outfits, 0, 5);
        }

        if ($method === 'post' && $uri === '/try-on/save') {
            $id = (int) ($data['generation_id'] ?? 0);
            $this->findById($this->userData('try_ons'), $id);
            $rows = array_map(fn (array $row) => $row['id'] === $id ? ['saved' => true] + $row : $row, $this->userData('try_ons'));
            $this->storeUserData('try_ons', $rows);

            return $this->findById($rows, $id);
        }

        throw new FastApiException($this->messageFor(404), 404);
    }

    protected function sendWithMeta(string $method, string $uri, array $data = []): array
    {
        $offset = (int) ($data['offset'] ?? 0);
        $limit = (int) ($data['limit'] ?? self::CATALOG_PER_PAGE);

        if ($method === 'get' && in_array($uri, ['/wardrobe', '/outfits', '/try-on/history'], true)) {
            $all = $this->send($method, $uri, $data);

            return [
                'data' => array_slice($all, $offset, $limit),
                'meta' => ['total' => count($all), 'limit' => $limit, 'offset' => $offset],
            ];
        }

        if ($method !== 'get' || $uri !== '/catalog') {
            throw new FastApiException($this->messageFor(404), 404);
        }

        // Meniru pencarian ?q= (nama/brand) dan filter kategori FastAPI
        $search = Str::lower($data['q'] ?? '');
        $items = array_values(array_filter(self::CATALOG, fn (array $item) => ($search === '' || Str::contains(Str::lower($item['name'].' '.$item['brand']), $search))
            && (empty($data['category']) || $item['category'] === $data['category'])));

        return [
            'data' => array_map(fn (array $item) => $this->withIllustration($item), array_slice($items, $offset, $limit)),
            'meta' => ['total' => count($items), 'limit' => $limit, 'offset' => $offset],
        ];
    }

    private function nextId(array $rows): int
    {
        return $rows === [] ? 1 : max(array_column($rows, 'id')) + 1;
    }

    private function requireImage(array $files, string $field): void
    {
        if (! ($files[$field] ?? null) instanceof UploadedFile) {
            throw ValidationException::withMessages([$field => ['Foto wajib diunggah.']]);
        }
    }

    // $complete = true untuk tambah (semua field wajib); false untuk PATCH (field yang tidak dikirim dipertahankan)
    private function validatedWardrobeFields(array $fields, bool $complete): array
    {
        $errors = [];
        foreach (['name', 'color'] as $key) {
            if (array_key_exists($key, $fields) || $complete) {
                if (! is_string($fields[$key] ?? null) || trim($fields[$key]) === '') {
                    $errors[$key] = ['Isian ini wajib diisi.'];
                }
            }
        }
        if ((array_key_exists('category', $fields) || $complete) && ! array_key_exists($fields['category'] ?? '', self::CATEGORIES)) {
            $errors['category'] = ['Kategori tidak valid.'];
        }
        if ((array_key_exists('style', $fields) || $complete) && ! array_key_exists($fields['style'] ?? '', self::STYLES)) {
            $errors['style'] = ['Style tidak valid.'];
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $clean = Arr::only($fields, ['name', 'category', 'color', 'style']);
        if (isset($clean['color'])) {
            $clean['color'] = Str::lower($clean['color']);   // backend menyimpan warna huruf kecil
        }

        return $clean;
    }

    private function storeUserData(string $type, array $rows): void
    {
        Cache::forever("mock_v2_{$type}:".$this->me()['email'], array_values($rows));
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
        // Data per pengguna disimpan di cache supaya tambah/ubah/hapus bisa dicoba (v2 = bentuk kontrak FastAPI final)
        return Cache::rememberForever("mock_v2_{$type}:".$this->me()['email'], fn () => match ($type) {
            'wardrobe' => [
                ['id' => 11, 'name' => 'Kaos Garis', 'category' => 'top', 'color' => 'putih', 'style' => 'casual', 'image_url' => '/api/wardrobe/11/image'],
                ['id' => 12, 'name' => 'Jeans Denim', 'category' => 'bottom', 'color' => 'biru', 'style' => 'casual', 'image_url' => '/api/wardrobe/12/image'],
                ['id' => 13, 'name' => 'Kemeja Putih', 'category' => 'top', 'color' => 'putih', 'style' => 'formal', 'image_url' => '/api/wardrobe/13/image'],
                ['id' => 14, 'name' => 'Celana Bahan', 'category' => 'bottom', 'color' => 'hitam', 'style' => 'formal', 'image_url' => '/api/wardrobe/14/image'],
                ['id' => 15, 'name' => 'Sepatu Putih', 'category' => 'shoes', 'color' => 'putih', 'style' => 'casual', 'image_url' => '/api/wardrobe/15/image'],
            ],
            'outfits' => [
                ['id' => 1, 'name' => 'Santai ke Kampus', 'wardrobe_item_ids' => [11, 12]],
                ['id' => 2, 'name' => 'Kuliah hari Senin', 'wardrobe_item_ids' => [13, 14]],
            ],
            default => [
                ['id' => 1, 'generation_id' => 1, 'saved' => true, 'wardrobe_item_id' => null, 'catalog_item_id' => 1, 'image_url' => '/api/try-on/1/image', 'created_at' => '2026-11-21T10:00:00Z'],
                ['id' => 2, 'generation_id' => 2, 'saved' => true, 'wardrobe_item_id' => 11, 'catalog_item_id' => null, 'image_url' => '/api/try-on/2/image', 'created_at' => '2026-11-22T07:15:00Z'],
            ],
        });
    }

    private function deleteUserData(string $type, int $id): void
    {
        $rows = $this->userData($type);
        $this->findById($rows, $id);
        Cache::forever("mock_v2_{$type}:".$this->me()['email'], array_values(array_filter($rows, fn (array $row) => $row['id'] !== $id)));
    }
}
