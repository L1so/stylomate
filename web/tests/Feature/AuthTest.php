<?php

namespace Tests\Feature;

use App\Services\FastApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private const BASE = 'fastapi.test/api';

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        Route::middleware(['web', 'api.token'])->get('/_api-me-test', fn (FastApiClient $api) => $api->me());
    }

    private function validLogin(): array
    {
        return ['email' => 'budi@example.com', 'password' => 'rahasia123'];
    }

    private function validRegister(): array
    {
        return [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ];
    }

    public function test_login_stores_access_token_from_data_envelope(): void
    {
        Http::fake([
            self::BASE.'/auth/login' => Http::response(['data' => [
                'access_token' => 'jwt-abc',
                'token_type' => 'bearer',
                'expires_in' => 3600,
                'user' => ['id' => 1, 'name' => 'Budi', 'email' => 'budi@example.com'],
            ]]),
        ]);

        $this->from('/login')->post('/login', $this->validLogin())
            ->assertRedirect(route('wardrobe.index'))
            ->assertSessionHas('api_token', 'jwt-abc');

        Http::assertSent(fn ($r) => $r->url() === 'http://fastapi.test/api/auth/login' && $r['email'] === 'budi@example.com');
    }

    public function test_login_with_wrong_credentials_shows_generic_message(): void
    {
        Http::fake([
            self::BASE.'/auth/login' => Http::response(['detail' => 'Invalid credentials'], 401),
        ]);

        $this->from('/login')->post('/login', $this->validLogin())
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi belum cocok. Periksa lagi.'])
            ->assertSessionMissing('api_token');
    }

    public function test_login_validates_input_before_calling_api(): void
    {
        Http::fake();

        $this->from('/login')->post('/login', ['email' => '', 'password' => ''])
            ->assertSessionHasErrors(['email', 'password']);

        Http::assertNothingSent();
    }

    public function test_login_redirects_to_intended_page(): void
    {
        Http::fake([
            self::BASE.'/auth/login' => Http::response(['data' => ['access_token' => 'jwt-abc']]),
        ]);

        $this->get('/mix-match');

        $this->post('/login', $this->validLogin())
            ->assertRedirect(url('/mix-match'));
    }

    public function test_register_success_logs_user_in(): void
    {
        Http::fake([
            self::BASE.'/auth/register' => Http::response(['data' => ['id' => 7, 'name' => 'Budi', 'email' => 'budi@example.com']], 201),
            self::BASE.'/auth/login' => Http::response(['data' => ['access_token' => 'jwt-baru']]),
        ]);

        $this->from('/register')->post('/register', $this->validRegister())
            ->assertRedirect(route('wardrobe.index'))
            ->assertSessionHas('api_token', 'jwt-baru');
    }

    public function test_register_rejects_duplicate_email(): void
    {
        Http::fake([
            self::BASE.'/auth/register' => Http::response(['detail' => 'Email already registered'], 409),
        ]);

        $this->from('/register')->post('/register', $this->validRegister())
            ->assertSessionHasErrors(['email' => 'Email sudah terdaftar. Masuk dengan email ini atau gunakan email lain.']);

        $this->assertNull(session()->getOldInput('password'));
        $this->assertSame('Budi', session()->getOldInput('name'));
    }

    public function test_register_requires_matching_password_confirmation(): void
    {
        Http::fake();

        $data = $this->validRegister();
        $data['password_confirmation'] = 'berbeda123';

        $this->from('/register')->post('/register', $data)
            ->assertSessionHasErrors('password');

        Http::assertNothingSent();
    }

    public function test_validation_error_fields_are_mapped_to_form(): void
    {
        Http::fake([
            self::BASE.'/auth/register' => Http::response([
                'error' => [
                    'message' => 'Validation failed',
                    'fields' => [['field' => 'email', 'message' => 'Email tidak valid.']],
                ],
            ], 422),
        ]);

        $this->from('/register')->post('/register', $this->validRegister())
            ->assertSessionHasErrors(['email' => 'Email tidak valid.']);
    }

    public function test_backend_down_shows_friendly_message_on_form(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));

        $this->from('/login')->post('/login', $this->validLogin())
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'Server StyloMate belum bisa dihubungi. Coba lagi sebentar lagi.');

        $this->assertNull(session()->getOldInput('password'));
    }

    public function test_backend_server_error_on_login_shows_friendly_message(): void
    {
        Http::fake([
            self::BASE.'/auth/login' => Http::response([], 500),
        ]);

        $this->from('/login')->post('/login', $this->validLogin())
            ->assertSessionHas('error', 'Terjadi kesalahan di server. Coba lagi nanti.');
    }

    public function test_expired_token_on_private_page_logs_user_out(): void
    {
        Http::fake([
            self::BASE.'/auth/me' => Http::response([], 401),
        ]);

        $this->withSession(['api_token' => 'kedaluwarsa'])
            ->get('/_api-me-test')
            ->assertRedirect(route('login'))
            ->assertSessionMissing('api_token')
            ->assertSessionHas('error', 'Sesi kamu sudah berakhir. Silakan masuk lagi.');

        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer kedaluwarsa'));
    }

    public function test_server_error_on_page_shows_error_view(): void
    {
        Http::fake([
            self::BASE.'/auth/me' => Http::response([], 500),
        ]);

        $this->withSession(['api_token' => 'token-uji'])
            ->get('/_api-me-test')
            ->assertStatus(502)
            ->assertSee('Halaman belum bisa ditampilkan')
            ->assertSee('Terjadi kesalahan di server. Coba lagi nanti.');
    }

    public function test_backend_down_on_page_shows_503(): void
    {
        Http::fake(fn () => throw new ConnectionException('down'));

        $this->withSession(['api_token' => 'token-uji'])
            ->get('/_api-me-test')
            ->assertStatus(503)
            ->assertSee('Server StyloMate belum bisa dihubungi');
    }

    public function test_login_is_rate_limited(): void
    {
        Http::fake([
            self::BASE.'/auth/login' => Http::response([], 401),
        ]);

        for ($i = 0; $i < 10; $i++) {
            $this->from('/login')->post('/login', $this->validLogin());
        }

        $this->from('/login')->post('/login', $this->validLogin())
            ->assertStatus(429)
            ->assertSee('Terlalu banyak percobaan');
    }
}
