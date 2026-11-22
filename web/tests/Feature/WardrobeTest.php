<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WardrobeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.fastapi.use_mock' => false]);
        Http::preventStrayRequests();
    }

    private function item(array $override = []): array
    {
        return $override + ['id' => 5, 'name' => 'Kemeja Putih', 'category' => 'top', 'color' => 'putih', 'style' => 'formal', 'image_url' => '/api/wardrobe/5/image'];
    }

    private function validFields(array $override = []): array
    {
        return $override + ['name' => 'Kemeja Putih', 'category' => 'top', 'color' => 'putih', 'style' => 'formal'];
    }

    private function asUser(): static
    {
        return $this->withSession(['api_token' => 'jwt-1']);
    }

    // ---- akses ----

    public function test_guest_cannot_use_wardrobe_endpoints(): void
    {
        $this->post('/wardrobe', $this->validFields())->assertRedirect(route('login'));
        $this->get('/wardrobe/5/edit')->assertRedirect(route('login'));
        $this->patch('/wardrobe/5', $this->validFields())->assertRedirect(route('login'));
        $this->delete('/wardrobe/5')->assertRedirect(route('login'));

        Http::assertNothingSent();
    }

    // ---- daftar ----

    public function test_index_lists_items_with_jwt_and_private_image_route(): void
    {
        Http::fake(['*/wardrobe' => Http::response(['data' => [$this->item(), $this->item(['id' => 6, 'name' => 'Jeans Biru', 'category' => 'bottom'])]])]);

        $this->asUser()->get('/wardrobe')
            ->assertOk()
            ->assertSee('Kemeja Putih')
            ->assertSee('Jeans Biru')
            ->assertSee('Atasan')
            ->assertSee('/media/wardrobe/5', false)
            ->assertSee('noindex, nofollow', false);

        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && str_ends_with($request->url(), '/wardrobe')
            && $request->hasHeader('Authorization', 'Bearer jwt-1'));
    }

    public function test_index_shows_empty_state(): void
    {
        Http::fake(['*/wardrobe' => Http::response(['data' => []])]);

        $this->asUser()->get('/wardrobe')->assertOk()->assertSee('Wardrobe kamu masih kosong');
    }

    public function test_index_filters_by_category_and_ignores_unknown_category(): void
    {
        Http::fake(['*/wardrobe' => Http::response(['data' => [$this->item(), $this->item(['id' => 6, 'name' => 'Jeans Biru', 'category' => 'bottom'])]])]);

        $this->asUser()->get('/wardrobe?category=bottom')->assertSee('Jeans Biru')->assertDontSee('Kemeja Putih');
        $this->asUser()->get('/wardrobe?category=<script>')->assertSee('Jeans Biru')->assertSee('Kemeja Putih');
    }

    public function test_index_handles_fastapi_down_with_safe_message(): void
    {
        Http::fake(['*/wardrobe' => Http::response('', 500)]);

        $this->asUser()->get('/wardrobe')->assertSee('Halaman belum bisa ditampilkan')->assertSee('Terjadi kesalahan di server. Coba lagi nanti.')->assertDontSee('Exception');
    }

    // ---- tambah ----

    public function test_store_sends_multipart_with_fields_and_image_then_redirects_to_detail(): void
    {
        Http::fake(['*/wardrobe' => Http::response(['data' => $this->item()], 201)]);

        $this->asUser()->post('/wardrobe', $this->validFields() + ['image' => UploadedFile::fake()->image('baju.png')])
            ->assertRedirect(route('wardrobe.show', ['id' => 5]))
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/wardrobe')
            && $request->isMultipart()
            && $request->hasHeader('Authorization', 'Bearer jwt-1')
            && str_contains($request->body(), 'name="image"')
            && str_contains($request->body(), 'name="category"')
            && str_contains($request->body(), 'Kemeja Putih'));
    }

    public function test_store_accepts_jpeg_and_image_of_exactly_10_mb(): void
    {
        Http::fake(['*/wardrobe' => Http::response(['data' => $this->item()], 201)]);

        $this->asUser()->post('/wardrobe', $this->validFields() + ['image' => UploadedFile::fake()->image('baju.jpg')->size(10240)])
            ->assertSessionHasNoErrors();

        Http::assertSentCount(1);
    }

    public static function invalidUploads(): array
    {
        return [
            'bukan gambar (pdf)' => [fn () => UploadedFile::fake()->create('baju.pdf', 100, 'application/pdf'), 'Foto harus berformat JPEG atau PNG.'],
            'format gif ditolak' => [fn () => UploadedFile::fake()->image('baju.gif'), 'Foto harus berformat JPEG atau PNG.'],
            'format webp ditolak' => [fn () => UploadedFile::fake()->image('baju.webp'), 'Foto harus berformat JPEG atau PNG.'],
            'lebih dari 10 MB' => [fn () => UploadedFile::fake()->image('besar.png')->size(10241), 'Ukuran foto maksimal 10 MB.'],
        ];
    }

    #[DataProvider('invalidUploads')]
    public function test_store_rejects_invalid_upload_before_calling_fastapi(\Closure $makeFile, string $message): void
    {
        $this->asUser()->from('/wardrobe/create')
            ->post('/wardrobe', $this->validFields() + ['image' => $makeFile()])
            ->assertRedirect('/wardrobe/create')
            ->assertSessionHasErrors(['image' => $message]);

        Http::assertNothingSent();
    }

    public function test_store_requires_image_and_valid_fields(): void
    {
        $this->asUser()->from('/wardrobe/create')
            ->post('/wardrobe', ['name' => '', 'category' => 'jubah', 'color' => '', 'style' => 'santai'])
            ->assertSessionHasErrors(['image', 'name', 'category', 'color', 'style']);

        Http::assertNothingSent();
    }

    public function test_store_shows_fastapi_422_errors_on_the_form(): void
    {
        Http::fake(['*/wardrobe' => Http::response(['error' => [
            'message' => 'Data tidak valid.',
            'fields' => [['field' => 'name', 'message' => 'Nama pakaian sudah dipakai.']],
        ]], 422)]);

        $this->asUser()->from('/wardrobe/create')
            ->post('/wardrobe', $this->validFields() + ['image' => UploadedFile::fake()->image('baju.png')])
            ->assertRedirect('/wardrobe/create')
            ->assertSessionHasErrors(['name' => 'Nama pakaian sudah dipakai.']);
    }

    public function test_create_form_has_upload_input_limited_to_jpeg_png_and_loading_indicator(): void
    {
        $this->asUser()->get('/wardrobe/create')
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('accept="image/jpeg,image/png"', false)
            ->assertSee('data-loading', false)
            ->assertSee('maksimal 10 MB');
    }

    // ---- detail, edit, hapus ----

    public function test_show_displays_item_with_try_on_button_for_tops(): void
    {
        Http::fake(['*/wardrobe/5' => Http::response(['data' => $this->item()])]);

        $this->asUser()->get('/wardrobe/5')
            ->assertOk()
            ->assertSee('Kemeja Putih')
            ->assertSee('/try-on?wardrobe_item_id=5', false)
            ->assertSee('data-confirm', false);
    }

    public function test_show_hides_try_on_button_for_non_top(): void
    {
        Http::fake(['*/wardrobe/6' => Http::response(['data' => $this->item(['id' => 6, 'category' => 'bottom'])])]);

        $this->asUser()->get('/wardrobe/6')->assertOk()->assertDontSee('wardrobe_item_id=6', false);
    }

    public function test_show_returns_404_when_fastapi_has_no_item(): void
    {
        Http::fake(['*/wardrobe/9' => Http::response(['error' => ['message' => 'Tidak ditemukan']], 404)]);

        $this->asUser()->get('/wardrobe/9')->assertNotFound();
    }

    public function test_edit_prefills_form_without_upload_field(): void
    {
        Http::fake(['*/wardrobe/5' => Http::response(['data' => $this->item()])]);

        $this->asUser()->get('/wardrobe/5/edit')
            ->assertOk()
            ->assertSee('value="Kemeja Putih"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('Ganti foto (opsional)');
    }

    public function test_update_sends_patch_multipart_without_image_and_redirects(): void
    {
        Http::fake(['*/wardrobe/5' => Http::response(['data' => $this->item(['name' => 'Kemeja Baru'])])]);

        $this->asUser()->patch('/wardrobe/5', $this->validFields(['name' => 'Kemeja Baru']))
            ->assertRedirect(route('wardrobe.show', ['id' => 5]))
            ->assertSessionHas('success');

        // Kontrak backend: PATCH /wardrobe/{id} berupa multipart (Form), foto opsional
        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && str_ends_with($request->url(), '/wardrobe/5')
            && $request->isMultipart()
            && str_contains($request->body(), 'Kemeja Baru')
            && ! str_contains($request->body(), 'name="image"'));
    }

    public function test_update_can_replace_the_photo(): void
    {
        Http::fake(['*/wardrobe/5' => Http::response(['data' => $this->item()])]);

        $this->asUser()->patch('/wardrobe/5', $this->validFields() + ['image' => UploadedFile::fake()->image('baru.png')])
            ->assertSessionHasNoErrors();

        Http::assertSent(fn (Request $request) => $request->isMultipart() && str_contains($request->body(), 'name="image"'));
    }

    public function test_update_rejects_invalid_replacement_photo(): void
    {
        $this->asUser()->from('/wardrobe/5/edit')
            ->patch('/wardrobe/5', $this->validFields() + ['image' => UploadedFile::fake()->create('baru.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('image');

        Http::assertNothingSent();
    }

    public function test_destroy_item_still_used_shows_safe_message_instead_of_error_page(): void
    {
        Http::fake(['*/wardrobe/5' => Http::response(['error' => ['message' => 'Delete related outfits and try-on results first']], 409)]);

        $this->asUser()->delete('/wardrobe/5')
            ->assertRedirect(route('wardrobe.show', ['id' => 5]))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'masih dipakai'));
    }

    public function test_update_validates_fields_before_calling_fastapi(): void
    {
        $this->asUser()->from('/wardrobe/5/edit')
            ->patch('/wardrobe/5', $this->validFields(['category' => 'jubah', 'name' => '']))
            ->assertSessionHasErrors(['category', 'name']);

        Http::assertNothingSent();
    }

    public function test_destroy_sends_delete_and_redirects_to_list(): void
    {
        Http::fake(['*/wardrobe/5' => Http::response(null, 204)]);

        $this->asUser()->delete('/wardrobe/5')
            ->assertRedirect(route('wardrobe.index'))
            ->assertSessionHas('success', 'Pakaian dihapus dari Wardrobe.');

        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/wardrobe/5'));
    }

    public function test_non_numeric_ids_are_rejected(): void
    {
        $this->asUser()->get('/wardrobe/abc/edit')->assertNotFound();
        $this->asUser()->delete('/wardrobe/abc')->assertNotFound();

        Http::assertNothingSent();
    }
}
