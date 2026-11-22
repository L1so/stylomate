<?php

namespace App\Services\Fashion\Api;

use App\Services\FastApiClient;

/** Katalog versi API ASLI (milik anggota 2/3; dipakai di sini untuk memilih pakaian Try-On). */
class ApiCatalog
{
    public static function normalize(array $raw): array
    {
        return [
            'id'         => $raw['id'],
            'name'       => $raw['name'] ?? '',
            'brand'      => $raw['brand'] ?? null,
            'category'   => $raw['category'] ?? '',
            'color'      => $raw['color'] ?? '',
            'style'      => $raw['style'] ?? '',
            'imageUrl'   => FastApiClient::imageUrl($raw['image_path'] ?? $raw['image_url'] ?? null),
            'productUrl' => $raw['product_url'] ?? null,
        ];
    }

    public function listItems(): array
    {
        $response = FastApiClient::send(fn () => FastApiClient::http()->get('/api/catalog'));
        return array_map([self::class, 'normalize'], FastApiClient::listFrom($response->json()));
    }

    public function getItem($id): array
    {
        $response = FastApiClient::send(fn () => FastApiClient::http()->get('/api/catalog/' . $id));
        return self::normalize($response->json());
    }
}
