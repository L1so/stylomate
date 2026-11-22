<?php

use App\Exceptions\FastApiException;
use App\Http\Middleware\EnsureApiToken;
use App\Http\Middleware\RedirectIfApiToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias middleware custom, dipakai di routes/web.php
        $middleware->alias([
            'api.token' => EnsureApiToken::class,
            'api.guest' => RedirectIfApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Error dari FastAPI diubah menjadi pesan yang ramah pengguna supaya halaman tidak crash
        $exceptions->render(function (FastApiException $e, Request $request) {
            if ($e->status === 401) {
                // Token kedaluwarsa/tidak valid: hapus token lalu minta login ulang
                $request->session()->forget('api_token');

                return redirect()->guest(route('login'))->with('error', $e->getMessage());
            }

            if (! $request->isMethod('GET')) {
                // Form: kembali ke halaman sebelumnya tanpa membawa password
                return back()->withInput($request->except(['password', 'password_confirmation']))->with('error', $e->getMessage());
            }

            $status = match (true) {
                $e->status === 0 => 503,
                in_array($e->status, [403, 404], true) => $e->status,
                default => 502,
            };

            return response()->view('errors.api', ['message' => $e->getMessage()], $status);
        });
    })->create();
