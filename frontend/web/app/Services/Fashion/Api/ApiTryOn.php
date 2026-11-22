<?php

namespace App\Services\Fashion\Api;

use App\Services\FastApiClient;
use App\Services\Fashion\FashionException;
use App\Services\Fashion\TryOnResults;
use Illuminate\Http\UploadedFile;

/**
 * Virtual Try-On versi API ASLI. INI FILE UTAMA UNTUK INTEGRASI AI.
 * Laravel (server) memanggil FastAPI (PRD 9.3); FastAPI meneruskan ke AI Service anggota 1.
 * Browser tidak pernah berbicara langsung ke FastAPI/AI.
 */
class ApiTryOn
{
    /**
     * ASUMSI request (multipart): person_image (file) + (wardrobe_item_id ATAU catalog_item_id) + category.
     * ASUMSI respons: JSON { id, image_path } ATAU langsung gambar (image/png|jpeg).
     * PRD belum mendefinisikannya -> sepakati dengan anggota 3, lalu ubah HANYA method ini.
     *
     * @param array $garment ['source','id','name','category','imageUrl']
     * @return array{id: mixed, imageUrl: string}
     */
    public function generate(UploadedFile $person, array $garment): array
    {
        $idField = $garment['source'] === 'wardrobe' ? 'wardrobe_item_id' : 'catalog_item_id';

        $response = FastApiClient::send(function () use ($person, $garment, $idField) {
            return FastApiClient::http((int) config('fashion.tryon_timeout'))
                ->asMultipart()
                ->attach('person_image', file_get_contents($person->getRealPath()), $person->getClientOriginalName())
                ->post('/api/try-on/generate', [
                    $idField   => (string) $garment['id'],
                    'category' => $garment['category'],
                ]);
        });

        $contentType = (string) $response->header('Content-Type');

        // Kasus 1: backend membalas langsung gambar -> tidak ada id (Save akan gagal sampai backend memberi id).
        if (str_starts_with($contentType, 'image/')) {
            $stored = TryOnResults::put($response->body(), str_contains($contentType, 'png') ? 'png' : 'jpg');
            return ['id' => null, 'imageUrl' => $stored['url']];
        }

        // Kasus 2: JSON berisi path gambar privat + id. Laravel menayangkannya lewat proxy /media/api/...
        // (token ditambahkan di server), jadi TIDAK ada salinan gambar yang tersimpan di Laravel.
        $data = $response->json();
        $path = $data['image_path'] ?? $data['image_url'] ?? $data['result_image_path'] ?? null;
        if (!$path) {
            throw new FashionException('The server did not return a result image.', 502);
        }

        return ['id' => $data['id'] ?? $data['result_id'] ?? null, 'imageUrl' => FastApiClient::imageUrl($path)];
    }

    // PRD 11.5: POST /api/try-on/save. Asumsi body: { result_id }.
    public function saveResult($id, array $garment): void
    {
        if ($id === null || $id === '') {
            throw new FashionException('The server did not return a result id, so this result cannot be saved. Ask the backend to return { id, image_path }.', 422);
        }
        FastApiClient::send(fn () => FastApiClient::http()->post('/api/try-on/save', ['result_id' => $id]));
    }

    // PRD 11.5: GET /api/try-on/history
    public function listHistory(): array
    {
        $response = FastApiClient::send(fn () => FastApiClient::http()->get('/api/try-on/history'));
        $list = [];
        foreach (FastApiClient::listFrom($response->json()) as $r) {
            $list[] = [
                'id'          => $r['id'],
                'imageUrl'    => FastApiClient::imageUrl($r['result_image_path'] ?? $r['image_path'] ?? null),
                'createdAt'   => $r['created_at'] ?? null,
                'garmentName' => '',
            ];
        }
        return $list;
    }
}
