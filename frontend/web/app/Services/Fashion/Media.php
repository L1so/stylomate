<?php

namespace App\Services\Fashion;

use App\Services\FastApiClient;
use App\Services\Fashion\Mock\MockDb;

/**
 * Membaca byte gambar dari dua sumber: berkas mock (storage) atau backend (proxy dengan token).
 * Dipakai MediaController (menayangkan gambar) dan MockTryOn (membaca gambar pakaian).
 */
class Media
{
    /** @return array{body:string, mime:string} */
    public static function fetch(string $kind, string $path): array
    {
        if ($kind === 'mock') {
            if (!preg_match('/^[A-Za-z0-9._-]+$/', $path)) {
                throw new FashionException('Invalid file name.', 400);
            }
            $file = MockDb::filesDir() . '/' . $path;
            if (!is_file($file)) {
                throw new FashionException('Image not found.', 404);
            }
            return ['body' => file_get_contents($file), 'mime' => mime_content_type($file) ?: 'application/octet-stream'];
        }

        if (str_contains($path, '..')) {
            throw new FashionException('Invalid path.', 400);
        }
        $response = FastApiClient::send(function () use ($path) {
            return FastApiClient::http()->withHeaders(['Accept' => '*/*'])->get('/' . ltrim($path, '/'));
        });
        return ['body' => $response->body(), 'mime' => $response->header('Content-Type') ?: 'image/jpeg'];
    }

    /** Dari URL buatan kita ("/media/mock/x.png" atau "/media/api/uploads/x.jpg") ke byte gambar. */
    public static function fromUrl(string $url): array
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        if (!preg_match('#^/media/(mock|api)/(.+)$#', $path, $m)) {
            throw new FashionException('This image cannot be read by the mock try-on.', 422);
        }
        return self::fetch($m[1], rawurldecode($m[2]));
    }
}
