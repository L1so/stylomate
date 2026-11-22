<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_proxies_private_image_with_token(): void
    {
        Http::fake(['http://fastapi.test/api/files/try-on/9.png' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/9.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee('PNGDATA');

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer jwt-1'));
    }

    public function test_download_sends_attachment_header(): void
    {
        Http::fake(['http://fastapi.test/api/files/try-on/9.png' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $response = $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/9.png?download=1')
            ->assertOk();

        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('stylomate-9.png', $response->headers->get('Content-Disposition'));
    }

    public function test_view_without_download_has_no_attachment_header(): void
    {
        Http::fake(['http://fastapi.test/api/files/try-on/9.png' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $response = $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/9.png')
            ->assertOk();

        $this->assertNull($response->headers->get('Content-Disposition'));
    }

    public function test_rejects_non_image_content(): void
    {
        Http::fake(['http://fastapi.test/api/files/try-on/9.png' => Http::response('<html></html>', 200, ['Content-Type' => 'text/html'])]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/9.png')
            ->assertStatus(502);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/media/try-on/9.png')->assertRedirect(route('login'));

        Http::assertNothingSent();
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/../secret.png')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_invalid_characters_are_rejected(): void
    {
        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/a%3Cb.png')
            ->assertNotFound();

        Http::assertNothingSent();
    }

    public function test_fastapi_not_found_returns_404(): void
    {
        Http::fake(['http://fastapi.test/api/files/try-on/9.png' => Http::response(['detail' => 'Not found'], 404)]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/media/try-on/9.png')
            ->assertNotFound();
    }
}
