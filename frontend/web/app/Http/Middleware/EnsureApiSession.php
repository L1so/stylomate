<?php

namespace App\Http\Middleware;

use App\Services\Fashion\Fashion;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware sederhana pemeriksa session (PRD 6.2): rute privat butuh token FastAPI di session.
 * BUKAN pengganti otorisasi FastAPI: FastAPI tetap memeriksa identitas & kepemilikan data.
 *
 * Mode mock penuh (FASHION_MOCK_*=true, hanya untuk pengembangan): tidak ada pemeriksaan,
 * karena tidak ada FastAPI yang dipanggil. Untuk demo, matikan semua mock (lihat README).
 */
class EnsureApiSession
{
    public function handle(Request $request, Closure $next)
    {
        if (Fashion::usesApi() && !$request->session()->has('access_token')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please log in first.'], 401);
            }
            return redirect()->guest(route('login'))->with('error', 'Please log in to continue.'); // TC-06
        }
        return $next($request);
    }
}
