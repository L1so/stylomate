<?php

namespace App\Services\Fashion;

use App\Services\Fashion\Api\ApiCatalog;
use App\Services\Fashion\Api\ApiOutfits;
use App\Services\Fashion\Api\ApiRecommendations;
use App\Services\Fashion\Api\ApiTryOn;
use App\Services\Fashion\Api\ApiWardrobe;
use App\Services\Fashion\Mock\MockCatalog;
use App\Services\Fashion\Mock\MockOutfits;
use App\Services\Fashion\Mock\MockRecommendations;
use App\Services\Fashion\Mock\MockTryOn;
use App\Services\Fashion\Mock\MockWardrobe;

/**
 * SAKLAR mock <-> API asli (setara services/fashion/index.js di versi React).
 * Controller HANYA memanggil Fashion::xxx(); tidak pernah menyentuh kelas Mock Api langsung.
 * Kelas Mock dan Api punya nama method & bentuk hasil yang sama.
 */
class Fashion
{
    public static function wardrobe()
    {
        return config('fashion.mock_data') ? new MockWardrobe() : new ApiWardrobe();
    }

    public static function outfits()
    {
        return config('fashion.mock_data') ? new MockOutfits() : new ApiOutfits();
    }

    public static function recommendations()
    {
        return config('fashion.mock_data') ? new MockRecommendations() : new ApiRecommendations();
    }

    public static function catalog()
    {
        return config('fashion.mock_catalog') ? new MockCatalog() : new ApiCatalog();
    }

    public static function tryOn()
    {
        return config('fashion.mock_tryon') ? new MockTryOn() : new ApiTryOn();
    }

    /** Nama bagian yang masih mock, untuk banner kuning di layout. */
    public static function mockStatus(): array
    {
        $status = [
            'data'    => (bool) config('fashion.mock_data'),
            'catalog' => (bool) config('fashion.mock_catalog'),
            'tryOn'   => (bool) config('fashion.mock_tryon'),
        ];
        return array_keys(array_filter($status));
    }

    /** True bila ada bagian yang memakai API asli (maka token login dibutuhkan). */
    public static function usesApi(): bool
    {
        return !config('fashion.mock_data') || !config('fashion.mock_catalog') || !config('fashion.mock_tryon');
    }
}
