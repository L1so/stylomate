<?php

namespace App\Http\Controllers;

use App\Services\Fashion\Media;

/**
 * Menayangkan gambar lewat Laravel:
 *  - /media/mock/{file}  : berkas mock di storage
 *  - /media/api/{path}   : proxy ke backend DENGAN token dari sesi (gambar privat bisa tampil di <img>)
 */
class MediaController extends Controller
{
    public function mock(string $file)
    {
        return $this->respond(Media::fetch('mock', $file));
    }

    public function api(string $path)
    {
        return $this->respond(Media::fetch('api', $path));
    }

    private function respond(array $media)
    {
        return response($media['body'], 200, [
            'Content-Type'  => $media['mime'],
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
