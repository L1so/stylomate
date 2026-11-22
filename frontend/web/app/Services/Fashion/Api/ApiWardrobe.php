<?php

namespace App\Services\Fashion\Api;

use App\Services\FastApiClient;
use Illuminate\Http\UploadedFile;

/** Wardrobe versi API ASLI (PRD 11.3). Method & bentuk hasil sama dengan Mock/MockWardrobe. */
class ApiWardrobe
{
    /**
     * Satu-satunya tempat yang tahu nama field backend (image_path, created_at).
     * Jika backend memakai nama lain, ubah di sini.
     */
    public static function normalize(array $raw): array
    {
        return [
            'id'        => $raw['id'],
            'name'      => $raw['name'] ?? '',
            'category'  => $raw['category'] ?? '',
            'color'     => $raw['color'] ?? '',
            'style'     => $raw['style'] ?? '',
            'imageUrl'  => FastApiClient::imageUrl($raw['image_path'] ?? $raw['image_url'] ?? null),
            'createdAt' => $raw['created_at'] ?? null,
        ];
    }

    private static function fields(array $v): array
    {
        return ['name' => $v['name'], 'category' => $v['category'], 'color' => $v['color'], 'style' => $v['style']];
    }

    public function listItems(): array
    {
        $response = FastApiClient::send(fn () => FastApiClient::http()->get('/api/wardrobe'));
        return array_map([self::class, 'normalize'], FastApiClient::listFrom($response->json()));
    }

    public function getItem($id): array
    {
        $response = FastApiClient::send(fn () => FastApiClient::http()->get('/api/wardrobe/' . $id));
        return self::normalize($response->json());
    }

    public function createItem(array $v, ?UploadedFile $image): array
    {
        $response = FastApiClient::send(function () use ($v, $image) {
            $request = FastApiClient::http(60)->asMultipart();
            if ($image !== null) {
                // nama field "image": sepakati dengan anggota 3
                $request = $request->attach('image', file_get_contents($image->getRealPath()), $image->getClientOriginalName());
            }
            return $request->post('/api/wardrobe', self::fields($v));
        });
        return self::normalize($response->json());
    }

    public function updateItem($id, array $v, ?UploadedFile $image): array
    {
        $response = FastApiClient::send(function () use ($id, $v, $image) {
            $request = FastApiClient::http(60)->asMultipart();
            if ($image !== null) {
                $request = $request->attach('image', file_get_contents($image->getRealPath()), $image->getClientOriginalName());
            }
            return $request->patch('/api/wardrobe/' . $id, self::fields($v));
        });
        return self::normalize($response->json());
    }

    public function deleteItem($id): void
    {
        FastApiClient::send(fn () => FastApiClient::http()->delete('/api/wardrobe/' . $id));
    }
}
