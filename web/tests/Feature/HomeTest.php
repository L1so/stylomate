<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HomeTest extends TestCase
{
    private const CATALOG_URL = 'http://fastapi.test/api/catalog*';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function fakeCatalog(mixed $response): void
    {
        Http::fake([self::CATALOG_URL => $response]);
    }

    private function fakeCatalogWithItem(): void
    {
        $this->fakeCatalog(Http::response(['data' => [[
            'id' => 3,
            'name' => 'Kemeja Uji Beranda',
            'brand' => 'Brand X',
            'category' => 'top',
            'color' => 'putih',
            'style' => 'casual',
            'image_url' => null,
            'product_url' => null,
        ]]]));
    }

    public function test_guest_sees_mulai_sekarang_linking_to_register(): void
    {
        $this->fakeCatalogWithItem();

        $this->get('/')
            ->assertOk()
            ->assertSee('Mulai Sekarang')
            ->assertSee('Try On')
            ->assertSee('Smart Outfit Curation')
            ->assertSee('href="'.route('register').'"', false)
            ->assertSee('Sudah punya akun?');
    }

    public function test_logged_in_user_sees_mulai_sekarang_linking_to_wardrobe(): void
    {
        $this->fakeCatalogWithItem();

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/')
            ->assertOk()
            ->assertSee('href="'.route('wardrobe.index').'"', false)
            ->assertDontSee('Sudah punya akun?');
    }

    public function test_home_lists_catalog_items_when_api_is_up(): void
    {
        $this->fakeCatalogWithItem();

        $this->get('/')
            ->assertOk()
            ->assertSee('Dari Catalog')
            ->assertSee('Kemeja Uji Beranda');

        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'limit=4'));
    }

    public function test_home_still_loads_when_catalog_api_fails(): void
    {
        $this->fakeCatalog(Http::response(['detail' => 'Error'], 500));

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Dari Catalog');
    }

    public function test_home_still_loads_when_backend_is_down(): void
    {
        $this->fakeCatalog(fn () => throw new ConnectionException('down'));

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Dari Catalog');
    }
}
