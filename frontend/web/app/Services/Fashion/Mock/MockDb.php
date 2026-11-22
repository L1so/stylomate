<?php

namespace App\Services\Fashion\Mock;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * "Database" palsu: satu berkas JSON (storage/app/fashion_mock/db.json) + folder gambar.
 * Dibuat otomatis saat pertama dipakai. Reset: hapus folder storage/app/fashion_mock
 * atau klik "Reset mock data" di banner kuning.
 */
class MockDb
{
    public static function dir(): string
    {
        return storage_path('app/fashion_mock');
    }

    public static function filesDir(): string
    {
        return self::dir() . '/files';
    }

    public static function path(): string
    {
        return self::dir() . '/db.json';
    }

    public static function ensureDirs(): void
    {
        if (!is_dir(self::filesDir())) {
            mkdir(self::filesDir(), 0777, true);
        }
    }

    public static function read(): array
    {
        self::ensureDirs();
        if (is_file(self::path())) {
            $db = json_decode(file_get_contents(self::path()), true);
            if (is_array($db)) {
                return $db;
            }
        }
        $db = MockSeed::build(); // belum ada / rusak -> buat baru
        self::write($db);
        return $db;
    }

    public static function write(array $db): void
    {
        self::ensureDirs();
        file_put_contents(
            self::path(),
            json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    /** Ambil id berikutnya (counter ikut tersimpan saat write()). */
    public static function nextId(array &$db): int
    {
        $id = $db['nextId'];
        $db['nextId'] = $id + 1;
        return $id;
    }

    /** Posisi baris dengan id tertentu di sebuah daftar, atau null. */
    public static function indexOf(array $list, $id): ?int
    {
        foreach ($list as $index => $row) {
            if ((string) $row['id'] === (string) $id) {
                return $index;
            }
        }
        return null;
    }

    public static function url(string $file): string
    {
        return route('media.mock', ['file' => $file]);
    }

    /** Simpan unggahan ke folder gambar mock; kembalikan nama berkas barunya. */
    public static function storeUpload(UploadedFile $file): string
    {
        self::ensureDirs();
        $name = Str::uuid()->toString() . '.' . ($file->extension() ?: 'jpg');
        $file->move(self::filesDir(), $name);
        return $name;
    }

    public static function reset(): void
    {
        self::deleteTree(self::dir());
        self::deleteTree(\App\Services\Fashion\TryOnResults::dir());
    }

    private static function deleteTree(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            is_dir($path) ? self::deleteTree($path) : unlink($path);
        }
        rmdir($dir);
    }
}
