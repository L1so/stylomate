<?php

namespace App\Services\Fashion\Mock;

use App\Services\Fashion\FashionException;

/**
 * Rekomendasi versi MOCK. PRD v1.1 bagian 9.1: Laravel TIDAK boleh menggandakan aturan rekomendasi
 * (aturan hanya ada di FastAPI). Karena itu mock ini TIDAK menghitung apa pun: ia hanya membaca
 * contoh hasil tetap (fixtures/recommendations.json) yang dihasilkan oleh mesin aturan Python
 * (backend-fashion/recommendation.py) untuk data contoh (seed).
 * Batasan: item yang Anda tambah sendiri tidak akan muncul di saran mock. Saran sungguhan dihitung FastAPI.
 */
class MockRecommendations
{
    public function getRecommendations(string $style): array
    {
        usleep(500000); // meniru jeda jaringan

        $fixture = json_decode(file_get_contents(__DIR__ . '/fixtures/recommendations.json'), true);
        if (!isset($fixture[$style])) {
            throw new FashionException('Unsupported style.', 422);
        }

        $db = MockDb::read();
        $outfits = [];
        foreach ($fixture[$style]['outfits'] as $sample) {
            $items = [];
            foreach ($sample['item_ids'] as $id) {
                $index = MockDb::indexOf($db['wardrobe'], $id);
                if ($index === null) {
                    continue 2; // salah satu item sudah dihapus -> lewati contoh ini
                }
                $items[] = MockWardrobe::present($db['wardrobe'][$index]);
            }
            $outfits[] = ['items' => $items, 'score' => $sample['score'], 'reason' => $sample['reason']];
        }

        if (count($outfits) === 0) {
            return [
                'outfits' => [],
                'message' => 'No sample suggestions are left for this wardrobe in mock mode. Real suggestions come from the FastAPI recommendation endpoint.',
            ];
        }
        return ['outfits' => $outfits, 'message' => null];
    }
}
