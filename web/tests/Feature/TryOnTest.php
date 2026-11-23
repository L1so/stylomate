<?php

namespace Tests\Feature;

use App\Services\FastApiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TryOnTest extends TestCase
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

    private function catalogTop(): array
    {
        return ['id' => 3, 'name' => 'Sweater Rajut', 'brand' => 'Brand A', 'category' => 'top', 'color' => 'cokelat', 'style' => 'casual', 'image_path' => null, 'product_url' => null];
    }

    private function catalogBottom(): array
    {
        return ['id' => 5, 'name' => 'Kulot', 'brand' => 'Brand B', 'category' => 'bottom', 'color' => 'hitam', 'style' => 'formal', 'image_path' => null, 'product_url' => null];
    }

    private function fakePickers(?array $catalogItem = null): void
    {
        $catalogItem ??= $this->catalogTop();

        Http::fake([
            '*/catalog/3' => Http::response(['data' => $catalogItem]),
            '*/catalog/5' => Http::response(['data' => $this->catalogBottom()]),
            '*/catalog/7' => Http::response(['data' => ['id' => 7, 'name' => 'Dress', 'category' => 'dress'] + $catalogItem]),
            '*/catalog*' => Http::response(['data' => [$this->catalogTop(), $this->catalogBottom()]]),
            '*/wardrobe?*' => Http::response(['data' => [
                ['id' => 5, 'name' => 'Kemeja Putih', 'category' => 'top', 'color' => 'putih', 'style' => 'formal'],
                ['id' => 6, 'name' => 'Celana Bahan', 'category' => 'bottom', 'color' => 'hitam', 'style' => 'formal'],
            ]]),
            '*/outfits?*' => Http::response(['data' => [
                ['id' => 9, 'name' => 'Formal Lengkap', 'wardrobe_item_ids' => [5, 6]],
            ]]),
        ]);
    }

    private function png(): UploadedFile
    {
        return UploadedFile::fake()->image('foto.png');
    }

    // ---- halaman ----

    public function test_page_lists_outfits_tops_and_bottoms(): void
    {
        $this->fakePickers();

        $this->asUser()->get('/try-on')
            ->assertOk()
            ->assertSee('Formal Lengkap')
            ->assertSee('Kemeja Putih')
            ->assertSee('Celana Bahan')
            ->assertSee('Sweater Rajut')
            ->assertSee('Kulot')
            ->assertSee('value="outfit:9"', false)
            ->assertSee('value="wardrobe:5"', false)
            ->assertSee('value="catalog:3"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('data-loading', false)
            ->assertSee('noindex, nofollow', false);

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/catalog?'));
    }

    public function test_index_shows_privacy_dialog(): void
    {
        $this->fakePickers();

        $this->asUser()->get('/try-on')
            ->assertOk()
            ->assertSee('data-consent-dialog="privasi-try-on"', false)
            ->assertSee('id="privasi-try-on"', false)
            ->assertSee('Foto badanmu tidak kami simpan')
            ->assertSee('name="privacy_consent"', false);
    }

    public function test_index_has_no_javascript_privacy_consent_and_catalog_link(): void
    {
        $this->fakePickers();

        $this->asUser()->get('/try-on')
            ->assertOk()
            ->assertSee('<noscript>', false)
            ->assertSee('name="privacy_consent" value="1"', false)
            ->assertSee('Saya sudah membaca dan setuju')
            ->assertSee('Cari di Catalog');
    }

    public function test_privacy_text_without_configured_ai_provider_hides_third_party_name(): void
    {
        config(['services.fastapi.ai_provider' => '']);
        $this->fakePickers();

        $this->asUser()->get('/try-on')
            ->assertOk()
            ->assertSee('Foto diproses oleh server AI StyloMate')
            ->assertDontSee('pihak ketiga');
    }

    public function test_catalog_item_id_from_catalog_button_is_preselected(): void
    {
        $this->fakePickers();

        $html = $this->asUser()->get('/try-on?catalog_item_id=3')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="catalog:3"[^>]*checked/', $html);
        $this->assertDoesNotMatchRegularExpression('/value="wardrobe:5"[^>]*checked/', $html);
    }

    public function test_wardrobe_item_id_is_preselected(): void
    {
        $this->fakePickers();

        $html = $this->asUser()->get('/try-on?wardrobe_item_id=5')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="wardrobe:5"[^>]*checked/', $html);
    }

    public function test_bottom_catalog_item_is_preselected(): void
    {
        $this->fakePickers();

        $html = $this->asUser()->get('/try-on?catalog_item_id=5')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="catalog:5"[^>]*checked/', $html);
    }

    public function test_outfit_id_from_profile_button_is_preselected(): void
    {
        $this->fakePickers();

        $html = $this->asUser()->get('/try-on?outfit_id=9')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/value="outfit:9"[^>]*checked/', $html);
    }

    public function test_unsupported_catalog_item_shows_notice(): void
    {
        $this->fakePickers();

        $html = $this->asUser()->get('/try-on?catalog_item_id=7')
            ->assertOk()
            ->assertSee('mendukung atasan, bawahan')
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/value="catalog:7"/', $html);
    }

    public function test_invalid_catalog_item_id_in_query_is_ignored(): void
    {
        $this->fakePickers();

        $this->asUser()->get('/try-on?catalog_item_id=abc')->assertOk();

        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/catalog/'));
    }

    public function test_unknown_catalog_item_returns_404(): void
    {
        Http::fake([
            '*/catalog/99' => Http::response(['error' => ['message' => 'x']], 404),
            '*/catalog*' => Http::response(['data' => []]),
            '*/wardrobe?*' => Http::response(['data' => []]),
            '*/outfits?*' => Http::response(['data' => []]),
        ]);

        $this->asUser()->get('/try-on?catalog_item_id=99')->assertNotFound();
    }

    public function test_saved_result_section_is_shown_after_generate(): void
    {
        $this->fakePickers();

        $this->withSession(['api_token' => 'jwt-1', 'try_on_result' => ['id' => 77, 'generation_id' => 77, 'image_url' => url('/media/try-on/77'), 'item' => 'catalog:3']])
            ->get('/try-on')
            ->assertOk()
            ->assertSee('Hasil Try On')
            ->assertSee('/media/try-on/77', false)
            ->assertSee('name="generation_id" value="77"', false);
    }

    // ---- generate ----

    public function test_generate_with_catalog_item_sends_multipart_and_redirects_with_result(): void
    {
        Http::fake(['*/try-on/generate' => Http::response(['data' => ['id' => 77, 'generation_id' => 77, 'saved' => false, 'image_url' => '/api/try-on/77/image', 'wardrobe_item_id' => null, 'catalog_item_id' => 3]], 201)]);

        $this->asUser()->post('/try-on/generate', ['item' => 'catalog:3', 'privacy_consent' => '1', 'person_image' => $this->png()])
            ->assertRedirect(route('try-on.index'))
            ->assertSessionHas('try_on_result', fn (array $result) => $result['id'] === 77
                && $result['generation_id'] === 77
                && str_ends_with($result['image_url'], '/media/try-on/77')
                && $result['item'] === 'catalog:3');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/try-on/generate')
            && $request->isMultipart()
            && $request->hasHeader('Authorization', 'Bearer jwt-1')
            && str_contains($request->body(), 'name="person_image"')
            && str_contains($request->body(), 'name="catalog_item_id"')
            && ! str_contains($request->body(), 'wardrobe_item_id'));
    }

    public function test_generate_with_wardrobe_item_sends_only_wardrobe_item_id(): void
    {
        Http::fake(['*/try-on/generate' => Http::response(['data' => ['generation_id' => 78]], 201)]);

        $this->asUser()->post('/try-on/generate', ['item' => 'wardrobe:5', 'privacy_consent' => '1', 'person_image' => $this->png()])
            ->assertRedirect(route('try-on.index'))
            ->assertSessionHas('try_on_result', fn (array $result) => $result['id'] === 78);

        Http::assertSent(fn (Request $request) => str_contains($request->body(), 'name="wardrobe_item_id"')
            && ! str_contains($request->body(), 'catalog_item_id'));
    }

    public function test_generate_with_outfit_sends_only_outfit_id(): void
    {
        Http::fake(['*/try-on/generate' => Http::response(['data' => ['generation_id' => 79, 'outfit_id' => 9]], 201)]);

        $this->asUser()->post('/try-on/generate', ['item' => 'outfit:9', 'privacy_consent' => '1', 'person_image' => $this->png()])
            ->assertRedirect(route('try-on.index'))
            ->assertSessionHas('try_on_result', fn (array $result) => $result['id'] === 79 && $result['item'] === 'outfit:9');

        Http::assertSent(fn (Request $request) => str_contains($request->body(), 'name="outfit_id"')
            && ! str_contains($request->body(), 'wardrobe_item_id')
            && ! str_contains($request->body(), 'catalog_item_id'));
    }

    public static function invalidGenerateInputs(): array
    {
        return [
            'tanpa item' => [[], 'item'],
            'format item salah' => [['item' => 'catalog:abc'], 'item'],
            'id nol' => [['item' => 'wardrobe:0'], 'item'],
            'sumber tidak dikenal' => [['item' => 'files:3'], 'item'],
            'dua item sekaligus' => [['item' => ['catalog:3', 'wardrobe:5']], 'item'],
            'tanpa foto' => [['item' => 'catalog:3', 'person_image' => null], 'person_image'],
            'foto pdf' => [['item' => 'catalog:3', 'person_image' => 'pdf'], 'person_image'],
            'foto gif' => [['item' => 'catalog:3', 'person_image' => 'gif'], 'person_image'],
            'foto lebih dari 10 MB' => [['item' => 'catalog:3', 'person_image' => 'big'], 'person_image'],
        ];
    }

    #[DataProvider('invalidGenerateInputs')]
    public function test_generate_rejects_invalid_input_before_calling_fastapi(array $input, string $errorField): void
    {
        $kind = array_key_exists('person_image', $input) ? $input['person_image'] : 'png';
        $input['person_image'] = match ($kind) {
            'pdf' => UploadedFile::fake()->create('foto.pdf', 100, 'application/pdf'),
            'gif' => UploadedFile::fake()->image('foto.gif'),
            'big' => UploadedFile::fake()->image('foto.png')->size(10241),
            null => null,
            default => $this->png(),
        };
        $input = array_filter($input, fn ($value) => $value !== null) + ['privacy_consent' => '1'];

        $this->asUser()->from('/try-on')->post('/try-on/generate', $input)
            ->assertRedirect('/try-on')
            ->assertSessionHasErrors($errorField);

        Http::assertNothingSent();
    }

    public function test_generate_requires_privacy_consent(): void
    {
        $this->asUser()->from('/try-on')->post('/try-on/generate', [
            'item' => 'catalog:3',
            'person_image' => UploadedFile::fake()->create('foto.jpg', 100, 'image/jpeg'),
        ])
            ->assertRedirect('/try-on')
            ->assertSessionHasErrors('privacy_consent');

        Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), '/try-on/generate'));
    }

    public function test_generate_shows_fastapi_422_for_unsupported_item(): void
    {
        Http::fake(['*/try-on/generate' => Http::response(['error' => [
            'message' => 'Data tidak valid.',
            'fields' => [['field' => 'wardrobe_item_id', 'message' => 'Try On hanya mendukung atasan dan bawahan.']],
        ]], 422)]);

        $this->asUser()->from('/try-on')->post('/try-on/generate', ['item' => 'wardrobe:6', 'privacy_consent' => '1', 'person_image' => $this->png()])
            ->assertRedirect('/try-on')
            ->assertSessionHasErrors(['wardrobe_item_id' => 'Try On hanya mendukung atasan dan bawahan.']);
    }

    public function test_generate_server_error_returns_to_form_with_safe_message_and_keeps_selection(): void
    {
        Http::fake(['*/try-on/generate' => Http::response('trace internal', 500)]);

        $this->asUser()->from('/try-on')->post('/try-on/generate', ['item' => 'catalog:3', 'privacy_consent' => '1', 'person_image' => $this->png()])
            ->assertRedirect('/try-on')
            ->assertSessionHas('error', 'Terjadi kesalahan di server. Coba lagi nanti.')
            ->assertSessionHasInput('item', 'catalog:3');
    }

    public function test_generate_timeout_returns_to_form_with_safe_message(): void
    {
        Http::fake(['*/try-on/generate' => fn () => throw new ConnectionException('cURL error 28: timeout')]);

        $this->asUser()->from('/try-on')->post('/try-on/generate', ['item' => 'catalog:3', 'privacy_consent' => '1', 'person_image' => $this->png()])
            ->assertRedirect('/try-on')
            ->assertSessionHas('error', 'Server StyloMate belum bisa dihubungi. Coba lagi sebentar lagi.');
    }

    public function test_generate_response_without_id_is_treated_as_server_error(): void
    {
        Http::fake(['*/try-on/generate' => Http::response(['data' => ['saved' => false]])]);

        $this->asUser()->from('/try-on')->post('/try-on/generate', ['item' => 'catalog:3', 'privacy_consent' => '1', 'person_image' => $this->png()])
            ->assertSessionHas('error');
    }

    public function test_ai_timeout_is_separate_from_the_normal_timeout(): void
    {
        config(['services.fastapi.timeout' => 10, 'services.fastapi.ai_timeout' => 120, 'services.fastapi.ai_outfit_timeout' => 240]);

        $client = new class extends FastApiClient
        {
            public function aiSeconds(): int
            {
                return $this->aiTimeout();
            }

            public function outfitSeconds(): int
            {
                return $this->aiTimeout(true);
            }
        };

        $this->assertSame(120, $client->aiSeconds());
        $this->assertSame(240, $client->outfitSeconds());
    }

    // ---- simpan ----

    public function test_save_posts_generation_id_and_redirects_to_profile_tab(): void
    {
        Http::fake(['*/try-on/save' => Http::response(['data' => ['id' => 4]], 201)]);

        $this->asUser()->post('/try-on/save', ['generation_id' => '77'])
            ->assertRedirect(route('profile.index', ['tab' => 'try-on']))
            ->assertSessionHas('success', 'Hasil Try On disimpan ke profil.');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/try-on/save')
            && $request->data() === ['generation_id' => 77]);
    }

    public function test_save_rejects_invalid_generation_id(): void
    {
        foreach (['', 'abc', '0', '-3'] as $value) {
            $this->asUser()->from('/try-on')->post('/try-on/save', ['generation_id' => $value])
                ->assertSessionHasErrors('generation_id');
        }

        Http::assertNothingSent();
    }

    public function test_guest_cannot_generate_or_save(): void
    {
        $this->post('/try-on/generate', ['item' => 'catalog:3', 'privacy_consent' => '1', 'person_image' => $this->png()])->assertRedirect(route('login'));
        $this->post('/try-on/save', ['generation_id' => 1])->assertRedirect(route('login'));

        Http::assertNothingSent();
    }
}
