<?php

namespace App\Services;

use App\Services\Fashion\FashionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Klien HTTP bersama ke FastAPI (PRD v1.1 bagian 14: satu FastApiClient untuk anggota 2 & 4).
 * Mengatur: base URL, header Authorization (token dari SESSION server), timeout, dan pemetaan error.
 * Token TIDAK pernah dikirim ke browser dan TIDAK ditulis ke log.
 */
class FastApiClient
{
    public static function http(?int $timeout = null): PendingRequest
    {
        $request = Http::baseUrl(config('fashion.api_base_url'))
            ->acceptJson()
            ->timeout($timeout ?? config('fashion.api_timeout'));

        $token = session('access_token');
        if ($token) {
            $request = $request->withToken($token);
        }
        return $request;
    }

    /**
     * Menjalankan request dan menerjemahkan semua kegagalan menjadi FashionException
     * (pesannya AMAN ditampilkan ke pengguna). $fn harus mengembalikan Illuminate\Http\Client\Response.
     */
    public static function send(callable $fn): Response
    {
        try {
            $response = $fn();
        } catch (ConnectionException $e) {
            logger()->warning('FastAPI is unreachable or timed out.', ['error' => $e->getMessage()]);
            $hint = config('app.debug') ? ' (debug: check FASTAPI_BASE_URL and that FastAPI is running)' : '';
            throw new FashionException('The service is not reachable right now. Please try again in a moment.' . $hint, 0, $e);
        }

        if ($response->failed()) {
            throw new FashionException(self::errorMessage($response), $response->status());
        }
        return $response;
    }

    /**
     * 5xx: pesan generik (detail server tidak dibocorkan; status dicatat di log tanpa token/body).
     * 4xx: pesan dari FastAPI {"detail": "..."} atau {"detail":[{"msg":"..."}]} (422).
     */
    public static function errorMessage(Response $response): string
    {
        $status = $response->status();

        if ($status >= 500) {
            logger()->warning('FastAPI returned a server error.', ['status' => $status]);
            return 'The service had a problem. Please try again in a moment.';
        }
        if ($status === 401) {
            return 'Your session is no longer valid. Please log in again.';
        }

        $detail = $response->json('detail');
        if (is_string($detail)) {
            return $detail;
        }
        if (is_array($detail)) {
            $parts = array_map(function ($d) {
                return is_array($d) ? ($d['msg'] ?? 'Invalid input.') : (string) $d;
            }, $detail);
            return implode('; ', $parts);
        }
        return 'The request could not be completed (' . $status . ').';
    }

    /** Backend boleh membalas array langsung atau {"items":[...]}. */
    public static function listFrom($data): array
    {
        if (is_array($data) && array_key_exists('items', $data)) {
            return $data['items'];
        }
        return is_array($data) ? $data : [];
    }

    /**
     * Path gambar dari FastAPI (mis. "/uploads/a.jpg") diubah menjadi URL proxy Laravel (/media/api/...),
     * sehingga gambar privat bisa tampil di <img>: Laravel yang menambahkan token, bukan browser.
     */
    public static function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        $base = config('fashion.api_base_url');
        if (str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        } elseif (preg_match('#^(https?:|data:)#i', $path)) {
            return $path; // domain lain: biarkan apa adanya
        }
        return route('media.api', ['path' => ltrim($path, '/')]);
    }
}
