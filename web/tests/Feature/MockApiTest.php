<?php

namespace Tests\Feature;

use App\Services\MockFastApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MockApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.fastapi.use_mock' => true]);
        Http::preventStrayRequests();
    }

    public function test_demo_account_can_log_in_without_backend(): void
    {
        $this->from('/login')->post('/login', [
            'email' => MockFastApiClient::DEMO_EMAIL,
            'password' => MockFastApiClient::DEMO_PASSWORD,
        ])->assertRedirect(route('wardrobe.index'))
            ->assertSessionHas('api_token');
    }

    public function test_mock_rejects_wrong_password(): void
    {
        $this->from('/login')->post('/login', [
            'email' => MockFastApiClient::DEMO_EMAIL,
            'password' => 'salah-sekali',
        ])->assertSessionHasErrors(['email' => 'Email atau kata sandi belum cocok. Periksa lagi.']);
    }

    public function test_mock_register_then_login_and_me(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Sari',
            'email' => 'sari@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('wardrobe.index'));

        $this->assertSame('mock-token:sari@example.com', session('api_token'));
    }

    public function test_mock_rejects_duplicate_demo_email(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Demo Kedua',
            'email' => MockFastApiClient::DEMO_EMAIL,
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_page_shows_demo_hint_only_in_mock_mode(): void
    {
        $this->get('/login')->assertSee(MockFastApiClient::DEMO_EMAIL);

        config(['services.fastapi.use_mock' => false]);

        $this->get('/login')->assertDontSee(MockFastApiClient::DEMO_EMAIL);
    }

    public function test_mock_catalog_lists_products_and_returns_404_for_unknown_id(): void
    {
        $this->get('/catalog')->assertOk()->assertSee('Kemeja Linen Oversize');
        $this->get('/catalog/999')->assertNotFound();
    }

    public function test_mock_catalog_search_and_category_filter(): void
    {
        $this->get('/catalog?search=jeans')
            ->assertSee('Jeans Lurus Denim Daur Ulang')
            ->assertDontSee('Kemeja Linen Oversize');

        $this->get('/catalog?category=bottom')
            ->assertSee('Kulot Katun Organik')
            ->assertDontSee('Kemeja Linen Oversize');

        $this->get('/catalog?category=shoes')
            ->assertSee('Sneakers Kanvas')
            ->assertDontSee('Kemeja Linen Oversize');
    }

    public function test_mock_catalog_item_uses_local_illustration(): void
    {
        $this->get('/catalog/1')
            ->assertOk()
            ->assertSee('/images/illustrations/kemeja.svg', false);
    }

    public function test_demo_user_can_delete_mock_outfit(): void
    {
        $this->post('/login', [
            'email' => MockFastApiClient::DEMO_EMAIL,
            'password' => MockFastApiClient::DEMO_PASSWORD,
        ]);

        $this->get('/profile')->assertOk()->assertSee('Pengguna Demo')->assertSee('Santai ke Kampus');

        $this->delete('/outfits/1')->assertRedirect(route('profile.index'));

        $this->get('/profile')->assertDontSee('Santai ke Kampus')->assertSee('Kuliah hari Senin');
    }

    public function test_mock_media_returns_placeholder_png(): void
    {
        $this->post('/login', [
            'email' => MockFastApiClient::DEMO_EMAIL,
            'password' => MockFastApiClient::DEMO_PASSWORD,
        ]);

        $this->get('/media/try-on/1')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');

        $this->get('/media/wardrobe/11')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }
}
