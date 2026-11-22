<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Padanan middleware 'guest' (PAWL P7): yang sudah login tidak perlu ke halaman login/register
        if ($request->session()->has('api_token')) {
            return redirect()->route('wardrobe.index');
        }

        return $next($request);
    }
}
