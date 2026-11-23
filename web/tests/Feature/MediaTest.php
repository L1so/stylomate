<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaTest extends TestCase
{
    private const TRY_ON_URL = 'http://fastapi.test/api/try-on/9/image';

    private const WARDROBE_URL = 'http://fastapi.test/api/wardrobe/11/image';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_proxies_try_on_image_with_token(): void
    {
        Http::fake([self::TRY_ON_URL => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/9')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee('PNGDATA');

        Http::assertSent(fn (Request $request) => $request->url() === self::TRY_ON_URL && $request->hasHeader('Authorization', 'Bearer jwt-1'));
    }

    public function test_proxies_wardrobe_image_with_token(): void
    {
        Http::fake([self::WARDROBE_URL => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/wardrobe/11')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        Http::assertSent(fn (Request $request) => $request->url() === self::WARDROBE_URL && $request->hasHeader('Authorization', 'Bearer jwt-1'));
    }

    public function test_wardrobe_thumbnail_requests_small_size_from_fastapi(): void
    {
        Http::fake(['http://fastapi.test/api/wardrobe/5/image?size=thumb' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/wardrobe/5?size=thumb')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        Http::assertSent(fn (Request $request) => $request->url() === 'http://fastapi.test/api/wardrobe/5/image?size=thumb'
            && $request->hasHeader('Authorization', 'Bearer jwt-1'));
    }

    public function test_wardrobe_image_without_size_does_not_send_size_param(): void
    {
        Http::fake(['http://fastapi.test/api/wardrobe/5/image' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $this->withSession(['api_token' => 'jwt-1'])->get('/media/wardrobe/5')->assertOk();

        Http::assertSent(fn (Request $request) => $request->url() === 'http://fastapi.test/api/wardrobe/5/image');
    }

    public function test_download_sends_attachment_header(): void
    {
        Http::fake([self::TRY_ON_URL => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $response = $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/9?download=1')
            ->assertOk();

        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('stylomate-try-on-9.png', $response->headers->get('Content-Disposition'));
    }

    public function test_view_without_download_has_no_attachment_header(): void
    {
        Http::fake([self::TRY_ON_URL => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $response = $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/9')
            ->assertOk();

        $this->assertNull($response->headers->get('Content-Disposition'));
    }

    public function test_rejects_non_image_content(): void
    {
        Http::fake([self::TRY_ON_URL => Http::response('<html></html>', 200, ['Content-Type' => 'text/html'])]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/9')
            ->assertStatus(502);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/media/try-on/9')->assertRedirect(route('login'));

        Http::assertNothingSent();
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/../secret.png')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_non_numeric_wardrobe_id_is_rejected(): void
    {
        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/wardrobe/abc')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_fastapi_not_found_returns_404(): void
    {
        Http::fake([self::TRY_ON_URL => Http::response(['detail' => 'Not found'], 404)]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/9')
            ->assertNotFound();
    }

    public function test_token_is_not_exposed_in_profile_html(): void
    {
        Http::fake([
            'http://fastapi.test/api/auth/me' => Http::response(['data' => ['id' => 1, 'name' => 'Budi', 'email' => 'budi@example.com']]),
            'http://fastapi.test/api/outfits?*' => Http::response(['data' => []]),
            'http://fastapi.test/api/try-on/history?*' => Http::response(['data' => [
                ['id' => 9, 'catalog_item_id' => 1, 'wardrobe_item_id' => null, 'image_url' => '/api/try-on/9/image', 'created_at' => '2026-11-22T07:00:00Z'],
            ]]),
        ]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/profile?tab=try-on')
            ->assertOk()
            ->assertSee(route('media.try-on', ['id' => 9]), false)
            ->assertDontSee('jwt-1');
    }
}
