<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TryOnWarmupTest extends TestCase
{
    public function test_warmup_only_runs_for_signed_in_users_when_enabled(): void
    {
        Http::fake();
        config(['services.fastapi.ai_warmup' => false, 'services.fastapi.use_mock' => false]);

        $this->post('/try-on/warmup')->assertRedirect(route('login'));
        $this->withSession(['api_token' => 'backend-jwt'])->post('/try-on/warmup')->assertNoContent();

        Http::assertNothingSent();
    }

    public function test_warmup_forwards_once_during_the_cooldown(): void
    {
        Http::fake(['*/try-on/warmup' => Http::response(null, 204)]);
        config(['services.fastapi.ai_warmup' => true, 'services.fastapi.use_mock' => false]);

        $this->withSession(['api_token' => 'backend-jwt']);
        $this->post('/try-on/warmup')->assertNoContent();
        $this->post('/try-on/warmup')->assertNoContent();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/try-on/warmup')
            && $request->hasHeader('Authorization', 'Bearer backend-jwt'));
    }

    public function test_page_only_announces_warmup_to_signed_in_users(): void
    {
        Http::fake(['*/catalog*' => Http::response(['data' => []])]);
        config(['services.fastapi.ai_warmup' => true, 'services.fastapi.use_mock' => false]);

        $this->get('/')->assertDontSee('name="ai-warmup-url"', false);
        $this->withSession(['api_token' => 'backend-jwt'])
            ->get('/')->assertSee('name="ai-warmup-url" content="/try-on/warmup"', false);
    }
}
