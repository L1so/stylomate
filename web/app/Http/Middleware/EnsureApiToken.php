<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Padanan middleware 'auth' (PAWL P7): tanpa JWT FastAPI di session berarti belum login
        if (! $request->session()->has('api_token')) {
            // guest() menyimpan URL tujuan agar setelah login bisa redirect()->intended()
            return redirect()->guest(route('login'))->with('error', 'Silakan masuk dulu untuk melanjutkan.');
        }

        return $next($request);
    }
}
