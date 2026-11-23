<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;

// Proxy gambar privat: Laravel mengambil gambar dari endpoint FastAPI yang tetap (per id) memakai JWT di session,
// lalu meneruskannya ke browser. Tidak ada proxy path bebas, dan token tidak pernah masuk ke HTML/URL.
class MediaController extends Controller
{
    public function tryOn(Request $request, FastApiClient $api, int $id) // GET /media/try-on/{id}
    {
        return $this->imageResponse($api->tryOnImage($id), $request->boolean('download') ? "stylomate-try-on-{$id}" : null);
    }

    public function wardrobe(Request $request, FastApiClient $api, int $id) // GET /media/wardrobe/{id}
    {
        return $this->imageResponse($api->wardrobeImage($id, $request->query('size') === 'thumb'), null);
    }

    private function imageResponse(array $file, ?string $downloadName)
    {
        $headers = [
            'Content-Type' => $file['type'],
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=300',
        ];

        if ($downloadName !== null) {
            // attachment = browser mengunduh file, bukan menampilkannya (F-10)
            $headers['Content-Disposition'] = HeaderUtils::makeDisposition('attachment', $downloadName.'.'.FastApiClient::IMAGE_TYPES[$file['type']]);
        }

        return response($file['content'], 200, $headers);
    }
}
