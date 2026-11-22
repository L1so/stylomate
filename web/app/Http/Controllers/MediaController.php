<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

class MediaController extends Controller
{
    public function show(Request $request, FastApiClient $api, string $path) // GET /media/{path}
    {
        // Tolak ".." supaya path tidak bisa keluar folder (path traversal)
        abort_if(str_contains($path, '..'), 404);

        return $this->respond($request, $api->file($path), basename($path));
    }

    public function wardrobe(Request $request, FastApiClient $api, int $id) // GET /media/wardrobe/{id} -> FastAPI GET /wardrobe/{id}/image
    {
        return $this->respond($request, $api->wardrobeImage($id), "wardrobe-{$id}");
    }

    public function tryOn(Request $request, FastApiClient $api, int $id) // GET /media/try-on/{id} -> FastAPI GET /try-on/{id}/image
    {
        return $this->respond($request, $api->tryOnImage($id), "try-on-{$id}");
    }

    private function respond(Request $request, array $file, string $name): Response
    {
        $headers = [
            'Content-Type' => $file['type'],
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=300',
        ];

        if ($request->boolean('download')) {
            // attachment = browser mengunduh file, bukan menampilkannya
            $extension = $file['type'] === 'image/jpeg' ? 'jpg' : substr($file['type'], 6);
            $headers['Content-Disposition'] = HeaderUtils::makeDisposition('attachment', 'stylomate-'.(str_contains($name, '.') ? $name : "{$name}.{$extension}"));
        }

        return response($file['content'], 200, $headers);
    }
}
