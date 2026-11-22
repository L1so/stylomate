<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    private function fakeProfile(?array $outfits = null, ?array $tryOns = null): void
    {
        Http::fake([
            'http://fastapi.test/api/auth/me' => Http::response([
                'id' => 1,
                'name' => 'Budi',
                'email' => 'budi@example.com',
                'created_at' => '2026-11-20T08:00:00Z',
            ]),
            'http://fastapi.test/api/outfits' => Http::response(['data' => $outfits ?? [
                [
                    'id' => 5,
                    'name' => 'Casual Weekend',
                    'created_at' => '2026-11-21T09:00:00Z',
                    'items' => [['id' => 11, 'name' => 'Striped Tee', 'category' => 'top', 'image_path' => 'wardrobe/11.png']],
                ],
            ]]),
            'http://fastapi.test/api/try-on/history' => Http::response(['data' => $tryOns ?? [
                ['id' => 9, 'catalog_item_id' => 1, 'wardrobe_item_id' => null, 'result_image_path' => 'try-on/9.png', 'created_at' => '2026-11-22T07:00:00Z'],
            ]]),
            'http://fastapi.test/api/outfits/5' => Http::response(null, 204),
            'http://fastapi.test/api/try-on/9' => Http::response(null, 204),
        ]);
    }

    public function test_shows_account_and_outfits_on_default_tab(): void
    {
        $this->fakeProfile();

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/profile')
            ->assertOk()
            ->assertSee('Budi')
            ->assertSee('budi@example.com')
            ->assertSee('20 Nov 2026')
            ->assertSee('Casual Weekend')
            ->assertSee('Striped Tee')
            ->assertSee(route('media.show', ['path' => 'wardrobe/11.png']), false)
            ->assertDontSee(route('media.show', ['path' => 'try-on/9.png']), false)
            ->assertSee('noindex, nofollow', false)
            ->assertSee('Keluar');

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer jwt-1'));
    }

    public function test_try_on_tab_shows_history_with_download(): void
    {
        $this->fakeProfile();

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/profile?tab=try-on')
            ->assertOk()
            ->assertSee(route('media.show', ['path' => 'try-on/9.png']), false)
            ->assertSee('Dari Catalog')
            ->assertSee('Unduh')
            ->assertSee(route('media.show', ['path' => 'try-on/9.png', 'download' => 1]), false);
    }

    public function test_unknown_tab_falls_back_to_outfit_tab(): void
    {
        $this->fakeProfile();

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/profile?tab=%3Cscript%3E')
            ->assertOk()
            ->assertSee('Casual Weekend')
            ->assertDontSee('<script>', false);
    }

    public function test_shows_empty_states_when_no_data(): void
    {
        $this->fakeProfile([], []);

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/profile')
            ->assertOk()
            ->assertSee('Belum ada outfit tersimpan');

        $this->withSession(['api_token' => 'jwt-1'])
            ->get('/profile?tab=try-on')
            ->assertOk()
            ->assertSee('Belum ada hasil Try On');
    }

    public function test_invalid_date_does_not_crash_page(): void
    {
        Http::fake([
            'http://fastapi.test/api/auth/me' => Http::response(['id' => 1, 'name' => 'Budi', 'email' => 'budi@example.com', 'created_at' => 'bukan-tanggal']),
            'http://fastapi.test/api/outfits' => Http::response(['data' => []]),
            'http://fastapi.test/api/try-on/history' => Http::response(['data' => []]),
        ]);

        $this->withSession(['api_token' => 'jwt-1'])->get('/profile')->assertOk();
    }

    public function test_deletes_outfit_and_redirects_with_success(): void
    {
        $this->fakeProfile();

        $this->withSession(['api_token' => 'jwt-1'])
            ->delete('/outfits/5')
            ->assertRedirect(route('profile.index'))
            ->assertSessionHas('success', 'Outfit dihapus.');

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && $request->url() === 'http://fastapi.test/api/outfits/5');
    }

    public function test_deletes_try_on_and_redirects_with_success(): void
    {
        $this->fakeProfile();

        $this->withSession(['api_token' => 'jwt-1'])
            ->delete('/try-on/9')
            ->assertRedirect(route('profile.index'))
            ->assertSessionHas('success', 'Hasil Try On dihapus.');

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && $request->url() === 'http://fastapi.test/api/try-on/9');
    }

    public function test_delete_outfit_not_found_shows_error(): void
    {
        Http::fake(['http://fastapi.test/api/outfits/99' => Http::response(['detail' => 'Not found'], 404)]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->from(route('profile.index'))
            ->delete('/outfits/99')
            ->assertRedirect(route('profile.index'))
            ->assertSessionHas('error', 'Data yang kamu cari tidak ditemukan.');
    }

    public function test_delete_outfit_of_another_user_is_forbidden(): void
    {
        Http::fake(['http://fastapi.test/api/outfits/5' => Http::response(['detail' => 'Forbidden'], 403)]);

        $this->withSession(['api_token' => 'jwt-1'])
            ->from(route('profile.index'))
            ->delete('/outfits/5')
            ->assertSessionHas('error', 'Kamu tidak punya akses ke data ini.');
    }

    public function test_guest_cannot_delete_outfit(): void
    {
        $this->delete('/outfits/5')->assertRedirect(route('login'));

        Http::assertNothingSent();
    }

    public function test_delete_outfit_via_get_is_not_allowed(): void
    {
        $this->withSession(['api_token' => 'jwt-1'])->get('/outfits/5')->assertStatus(405);
    }

    public function test_expired_token_on_profile_redirects_to_login(): void
    {
        Http::fake(['http://fastapi.test/api/auth/me' => Http::response(['detail' => 'Unauthorized'], 401)]);

        $this->withSession(['api_token' => 'jwt-expired'])
            ->get('/profile')
            ->assertRedirect(route('login'))
            ->assertSessionMissing('api_token');
    }
}
