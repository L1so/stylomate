<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\HeaderUtils;

class MediaController extends Controller
{
    public function show(Request $request, FastApiClient $api, string $path) // GET /media/{path}
    {
        // Tolak ".." supaya path tidak bisa keluar folder (path traversal)
        abort_if(str_contains($path, '..'), 404);

        $file = $api->file($path);

        $headers = [
            'Content-Type' => $file['type'],
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=300',
        ];

        if ($request->boolean('download')) {
            // attachment = browser mengunduh file, bukan menampilkannya
            $headers['Content-Disposition'] = HeaderUtils::makeDisposition('attachment', 'stylomate-'.basename($path));
        }

        return response($file['content'], 200, $headers);
    }
}
