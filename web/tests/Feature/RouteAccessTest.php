<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Halaman fashion memanggil FastAPI saat dibuka; semua dijawab kosong kecuali detail pakaian
        Http::fake([
            '*/wardrobe/5' => Http::response(['data' => ['id' => 5, 'name' => 'Kemeja Putih', 'category' => 'top', 'color' => 'putih', 'style' => 'formal']]),
            '*/wardrobe?*' => Http::response(['data' => []]),
            '*/catalog*' => Http::response(['data' => []]),
            '*/outfits?*' => Http::response(['data' => []]),
        ]);
    }

    public static function privatePages(): array
    {
        return [
            'wardrobe' => ['/wardrobe', 'Wardrobe'],
            'tambah pakaian' => ['/wardrobe/create', 'Tambah Pakaian'],
            'detail pakaian' => ['/wardrobe/5', 'Detail Pakaian'],
            'mix & match' => ['/mix-match', 'Mix &amp; Match'],
            'virtual try-on' => ['/try-on', 'Try On'],
            'rekomendasi' => ['/recommendations', 'Rekomendasi Outfit'],
        ];
    }

    public static function publicPages(): array
    {
        return [
            'beranda' => ['/'],
            'katalog' => ['/catalog'],
            'masuk' => ['/login'],
            'daftar' => ['/register'],
        ];
    }

    #[DataProvider('privatePages')]
    public function test_guest_is_redirected_to_login_from_private_pages(string $uri, string $title): void
    {
        $this->get($uri)->assertRedirect(route('login'));
    }

    #[DataProvider('privatePages')]
    public function test_logged_in_user_can_open_private_pages_marked_noindex(string $uri, string $title): void
    {
        $this->withSession(['api_token' => 'token-uji'])
            ->get($uri)
            ->assertOk()
            ->assertSee($title, false)
            ->assertSee('noindex, nofollow', false);
    }

    #[DataProvider('publicPages')]
    public function test_guest_can_open_public_pages(string $uri): void
    {
        $this->get($uri)->assertOk();
    }

    public function test_wardrobe_detail_rejects_non_numeric_id(): void
    {
        $this->withSession(['api_token' => 'token-uji'])->get('/wardrobe/abc')->assertNotFound();
    }

    public function test_logged_in_user_is_redirected_away_from_login_and_register(): void
    {
        $this->withSession(['api_token' => 'token-uji'])->get('/login')->assertRedirect(route('wardrobe.index'));
        $this->withSession(['api_token' => 'token-uji'])->get('/register')->assertRedirect(route('wardrobe.index'));
    }

    public function test_redirect_to_login_remembers_intended_url(): void
    {
        $this->get('/mix-match')->assertSessionHas('url.intended', url('/mix-match'));
    }

    public function test_logout_clears_token_and_redirects_home(): void
    {
        $this->withSession(['api_token' => 'token-uji', 'api_user' => ['name' => 'Uji']])
            ->post('/logout')
            ->assertRedirect(route('home'))
            ->assertSessionMissing('api_token')
            ->assertSessionHas('success', 'Kamu sudah keluar. Sampai jumpa lagi!');
    }

    public function test_guest_cannot_logout(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
    }

    public function test_logout_via_get_is_not_allowed(): void
    {
        $this->withSession(['api_token' => 'token-uji'])->get('/logout')->assertStatus(405);
    }
}
