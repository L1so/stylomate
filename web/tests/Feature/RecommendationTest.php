<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.fastapi.use_mock' => false]);
        Http::preventStrayRequests();
    }

    private function asUser(): static
    {
        return $this->withSession(['api_token' => 'jwt-1']);
    }

    public function test_without_style_shows_prompt_and_calls_nothing(): void
    {
        $this->asUser()->get('/recommendations')
            ->assertOk()
            ->assertSee('Pilih style dulu')
            ->assertSee('Casual')
            ->assertSee('Semi-formal');

        Http::assertNothingSent();
    }

    public function test_style_is_forwarded_to_fastapi_and_results_are_shown(): void
    {
        Http::fake(['*/recommendations*' => Http::response(['data' => [[
            'name' => 'Santai harian',
            'reason' => 'Warna netral dan sama-sama casual.',
            'items' => [
                ['id' => 7, 'name' => 'Kaos Garis', 'category' => 'top'],
                ['id' => 6, 'name' => 'Jeans Biru', 'category' => 'bottom'],
            ],
        ]]])]);

        $this->asUser()->get('/recommendations?style=casual')
            ->assertOk()
            ->assertSee('Kaos Garis')
            ->assertSee('Jeans Biru')
            ->assertSee('Warna netral dan sama-sama casual.')
            ->assertSee('/media/wardrobe/7', false)
            ->assertSee('Simpan sebagai Outfit');

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && str_ends_with($request->url(), '/recommendations?style=casual')
            && $request->hasHeader('Authorization', 'Bearer jwt-1'));
    }

    public function test_empty_result_shows_empty_state(): void
    {
        Http::fake(['*/recommendations*' => Http::response(['data' => []])]);

        $this->asUser()->get('/recommendations?style=formal')->assertOk()->assertSee('Belum ada rekomendasi');
    }

    public function test_unknown_style_is_ignored(): void
    {
        $this->asUser()->get('/recommendations?style=gothic')->assertOk()->assertSee('Pilih style dulu');

        Http::assertNothingSent();
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/recommendations?style=casual')->assertRedirect(route('login'));

        Http::assertNothingSent();
    }
}
