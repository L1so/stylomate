<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Proxy gambar privat khusus fashion: hanya /wardrobe/{id}/image dan /try-on/{id}/image, bukan /files/{path}
class FashionMediaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.fastapi.use_mock' => false]);
        Http::preventStrayRequests();
    }

    public function test_wardrobe_image_is_proxied_from_specific_endpoint_with_jwt(): void
    {
        Http::fake(['*/wardrobe/5/image' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png'])]);

        $this->withSession(['api_token' => 'jwt-1'])->get('/media/wardrobe/5')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertSee('PNGDATA');

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/wardrobe/5/image')
            && $request->hasHeader('Authorization', 'Bearer jwt-1'));
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/files/'));
    }

    public function test_try_on_image_is_proxied_and_can_be_downloaded(): void
    {
        Http::fake(['*/try-on/9/image' => Http::response('JPGDATA', 200, ['Content-Type' => 'image/jpeg'])]);

        $response = $this->withSession(['api_token' => 'jwt-1'])->get('/media/try-on/9?download=1')->assertOk();

        $this->assertStringStartsWith('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('stylomate-try-on-9.jpg', $response->headers->get('Content-Disposition'));
    }

    public function test_non_image_response_is_rejected(): void
    {
        Http::fake(['*/wardrobe/5/image' => Http::response('<script>alert(1)</script>', 200, ['Content-Type' => 'text/html'])]);

        $this->withSession(['api_token' => 'jwt-1'])->get('/media/wardrobe/5')->assertStatus(502);
    }

    public function test_other_users_image_returns_404_from_fastapi(): void
    {
        Http::fake(['*/wardrobe/5/image' => Http::response(['error' => ['message' => 'x']], 404)]);

        $this->withSession(['api_token' => 'jwt-1'])->get('/media/wardrobe/5')->assertNotFound();
    }

    public function test_guest_is_redirected_and_nothing_is_fetched(): void
    {
        $this->get('/media/wardrobe/5')->assertRedirect(route('login'));
        $this->get('/media/try-on/5')->assertRedirect(route('login'));

        Http::assertNothingSent();
    }

    public function test_non_numeric_id_does_not_match_the_specific_wardrobe_route(): void
    {
        $route = app('router')->getRoutes()->match(\Illuminate\Http\Request::create('/media/wardrobe/abc'));

        // Hanya angka yang masuk route spesifik; selain itu jatuh ke route /media/{path} milik Anggota 2
        $this->assertSame('media.show', $route->getName());
        $this->assertSame('media.wardrobe', app('router')->getRoutes()->match(\Illuminate\Http\Request::create('/media/wardrobe/12'))->getName());
        $this->assertSame('media.try-on', app('router')->getRoutes()->match(\Illuminate\Http\Request::create('/media/try-on/12'))->getName());
    }
}
