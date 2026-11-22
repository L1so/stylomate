<?php

namespace App\Services\Fashion;

use Illuminate\Support\Str;

/**
 * Penyimpanan SEMENTARA gambar hasil try-on di Laravel (storage/app/fashion_results).
 * Hanya dipakai bila FastAPI membalas byte gambar (bukan URL) atau di mode mock.
 * Berkas lebih tua dari 1 jam dihapus otomatis setiap ada hasil baru (PRD NFR-08: hasil permanen
 * disimpan hanya atas persetujuan pengguna, yaitu tombol Save -> FastAPI).
 */
class TryOnResults
{
    private const MAX_AGE_SECONDS = 3600;

    public static function dir(): string
    {
        return storage_path('app/fashion_results');
    }

    /** @return array{file:string,url:string} */
    public static function put(string $bytes, string $ext = 'jpg'): array
    {
        if (!is_dir(self::dir())) {
            mkdir(self::dir(), 0777, true);
        }
        self::cleanup();
        $file = Str::uuid()->toString() . '.' . $ext;
        file_put_contents(self::dir() . '/' . $file, $bytes);
        return ['file' => $file, 'url' => route('media.result', ['file' => $file])];
    }

    /** Path lengkap bila nama berkas valid dan ada; selain itu null. */
    public static function path(string $file): ?string
    {
        if (!preg_match('/^[A-Za-z0-9-]+\.(jpg|png)$/', $file)) {
            return null;
        }
        $path = self::dir() . '/' . $file;
        return is_file($path) ? $path : null;
    }

    private static function cleanup(): void
    {
        foreach (glob(self::dir() . '/*') ?: [] as $path) {
            if (is_file($path) && filemtime($path) < time() - self::MAX_AGE_SECONDS) {
                unlink($path);
            }
        }
    }
}
