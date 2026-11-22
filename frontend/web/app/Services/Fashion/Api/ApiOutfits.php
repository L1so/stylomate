<?php

namespace App\Services\Fashion\Api;

use App\Services\FastApiClient;

/** Outfit versi API ASLI (PRD 11.4). */
class ApiOutfits
{
    // Asumsi respons: { id, name, created_at, items: [ ...item wardrobe ] }
    public static function normalize(array $raw): array
    {
        return [
            'id'        => $raw['id'],
            'name'      => $raw['name'] ?? '',
            'createdAt' => $raw['created_at'] ?? null,
            'items'     => array_map([ApiWardrobe::class, 'normalize'], $raw['items'] ?? []),
        ];
    }

    public function listOutfits(): array
    {
        $response = FastApiClient::send(fn () => FastApiClient::http()->get('/api/outfits'));
        return array_map([self::class, 'normalize'], FastApiClient::listFrom($response->json()));
    }

    // Asumsi body: { name, wardrobe_item_ids: [id, id] }
    public function createOutfit(string $name, array $itemIds): array
    {
        $response = FastApiClient::send(function () use ($name, $itemIds) {
            return FastApiClient::http()->post('/api/outfits', ['name' => $name, 'wardrobe_item_ids' => array_values($itemIds)]);
        });
        return self::normalize($response->json());
    }

    public function deleteOutfit($id): void
    {
        FastApiClient::send(fn () => FastApiClient::http()->delete('/api/outfits/' . $id));
    }
}
