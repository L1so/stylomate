<?php

namespace App\Providers;

use App\Services\FastApiClient;
use App\Services\MockFastApiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Mode mock hanya untuk development; di production selalu memanggil FastAPI asli
        $this->app->bind(FastApiClient::class, fn () => config('services.fastapi.use_mock') && ! $this->app->isProduction()
            ? new MockFastApiClient
            : new FastApiClient);
    }

    public function boot(): void {}
}
