<?php

namespace App\Services\Fashion\Api;

use App\Services\FastApiClient;

/**
 * Smart Outfit Curation versi API ASLI (GET /api/recommendations?style=casual).
 * Asumsi respons: { outfits: [ { items:[top,bottom], score, reason } ], message: string|null }
 * (cocok dengan backend-fashion/recommendation.py -> recommend_outfits)
 */
class ApiRecommendations
{
    public function getRecommendations(string $style): array
    {
        $response = FastApiClient::send(fn () => FastApiClient::http()->get('/api/recommendations', ['style' => $style]));
        $data = $response->json();

        $outfits = [];
        foreach ($data['outfits'] ?? [] as $o) {
            $outfits[] = [
                'items'  => array_map([ApiWardrobe::class, 'normalize'], $o['items'] ?? []),
                'score'  => $o['score'] ?? null,
                'reason' => $o['reason'] ?? '',
            ];
        }
        return ['outfits' => $outfits, 'message' => $data['message'] ?? null];
    }
}
