<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    private const LIST_URL = 'http://fastapi.test/api/catalog';

    private const ITEM_URL = 'http://fastapi.test/api/catalog/3';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function item(array $overrides = []): array
    {
        return array_merge([
            'id' => 3,
            'name' => 'Linen Shirt',
            'brand' => 'Brand X',
            'category' => 'top',
            'color' => 'beige',
            'style' => 'casual',
            'image_url' => '/api/catalog/3/image',
            'product_url' => 'https://example.com/p/3',
        ], $overrides);
    }

    public function test_index_lists_items_from_data_wrapper(): void
    {
        Http::fake([self::LIST_URL => Http::response(['data' => [$this->item()]])]);

        $this->get('/catalog')
            ->assertOk()
            ->assertSee('Linen Shirt')
            ->assertSee('Brand X')
            ->assertSee('http://fastapi.test/api/catalog/3/image', false)
            ->assertSee(route('catalog.show', 3), false);

        Http::assertSent(fn (Request $request) => $request->url() === self::LIST_URL && ! $request->hasHeader('Authorization'));
    }

    public function test_catalog_image_url_is_made_absolute_from_backend_origin(): void
    {
        Http::fake([self::LIST_URL => Http::response(['data' => [
            $this->item(['id' => 3, 'name' => 'Linen Shirt', 'image_url' => '/api/catalog/3/image']),
            $this->item(['id' => 4, 'name' => 'Cdn Dress', 'image_url' => 'https://cdn.example.com/x.jpg']),
        ]])]);

        $this->get('/catalog')
            ->assertOk()
            ->assertSee('src="http://fastapi.test/api/catalog/3/image"', false)
            ->assertSee('src="https://cdn.example.com/x.jpg"', false);
    }

    public function test_index_shows_empty_state_when_no_items(): void
    {
        Http::fake([self::LIST_URL => Http::response(['data' => []])]);

        $this->get('/catalog')->assertOk()->assertSee('Catalog masih kosong');
    }

    public function test_index_shows_error_when_backend_is_down(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));

        $this->get('/catalog')
            ->assertStatus(503)
            ->assertSee('Server StyloMate belum bisa dihubungi');
    }

    public function test_show_displays_detail_and_try_on_link(): void
    {
        Http::fake([self::ITEM_URL => Http::response(['data' => $this->item()])]);

        $this->get('/catalog/3')
            ->assertOk()
            ->assertSee('Linen Shirt')
            ->assertSee(route('try-on.index', ['catalog_item_id' => 3]), false)
            ->assertSee('Lihat di toko')
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function test_show_hides_store_link_when_product_url_is_null(): void
    {
        Http::fake([self::ITEM_URL => Http::response(['data' => $this->item(['product_url' => null])])]);

        $this->get('/catalog/3')->assertOk()->assertDontSee('Lihat di toko');
    }

    public function test_show_hides_store_link_for_javascript_url(): void
    {
        Http::fake([self::ITEM_URL => Http::response(['data' => $this->item(['product_url' => 'javascript:alert(1)'])])]);

        $this->get('/catalog/3')
            ->assertOk()
            ->assertDontSee('Lihat di toko')
            ->assertDontSee('javascript:', false);
    }

    public function test_show_returns_not_found_when_fastapi_has_no_item(): void
    {
        Http::fake([self::ITEM_URL => Http::response(['detail' => 'Not found'], 404)]);

        $this->get('/catalog/3')
            ->assertNotFound()
            ->assertSee('Data yang kamu cari tidak ditemukan.');
    }

    public function test_show_rejects_non_numeric_id_without_calling_api(): void
    {
        $this->get('/catalog/abc')->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_guest_try_on_is_sent_to_login_and_remembers_url(): void
    {
        $tryOnUrl = route('try-on.index', ['catalog_item_id' => 3]);

        $this->get($tryOnUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', $tryOnUrl);
    }

    public function test_catalog_search_is_sent_as_q(): void
    {
        Http::fake(['http://fastapi.test/api/catalog*' => Http::response(['data' => [$this->item()]])]);

        $this->get('/catalog?search=linen&category=top')
            ->assertOk()
            ->assertSee('value="linen"', false)
            ->assertSee('aria-current="true"', false);

        Http::assertSent(fn (Request $request) => $request->url() === 'http://fastapi.test/api/catalog?q=linen&category=top');
    }

    public function test_new_categories_are_accepted(): void
    {
        Http::fake(['http://fastapi.test/api/catalog*' => Http::response(['data' => []])]);

        $this->get('/catalog?category=outerwear')
            ->assertOk()
            ->assertSee('>Outer</h2>', false);

        Http::assertSent(fn (Request $request) => $request->url() === 'http://fastapi.test/api/catalog?category=outerwear');
    }

    public function test_unknown_category_is_ignored(): void
    {
        Http::fake(['http://fastapi.test/api/catalog*' => Http::response(['data' => []])]);

        $this->get('/catalog?category=%3Cscript%3E')
            ->assertOk()
            ->assertDontSee('<script>', false);

        Http::assertSent(fn (Request $request) => $request->url() === self::LIST_URL);
    }

    public function test_array_search_input_is_ignored(): void
    {
        Http::fake(['http://fastapi.test/api/catalog*' => Http::response(['data' => []])]);

        $this->get('/catalog?search[]=x')->assertOk();

        Http::assertSent(fn (Request $request) => $request->url() === self::LIST_URL);
    }

    public function test_search_is_limited_to_100_characters(): void
    {
        Http::fake(['http://fastapi.test/api/catalog*' => Http::response(['data' => []])]);

        $this->get('/catalog?search='.str_repeat('a', 150))->assertOk();

        Http::assertSent(fn (Request $request) => $request->url() === 'http://fastapi.test/api/catalog?q='.str_repeat('a', 100));
    }

    public function test_no_results_shows_clear_filters(): void
    {
        Http::fake(['http://fastapi.test/api/catalog*' => Http::response(['data' => []])]);

        $this->get('/catalog?search=zzz')
            ->assertOk()
            ->assertSee('Pakaian tidak ditemukan')
            ->assertSee('Hapus Filter');
    }
}
