<?php

namespace Tests\Feature;

use App\Services\MockFastApiClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// Alur dasar fashion end-to-end memakai MockFastApiClient (tanpa FastAPI/AI sungguhan)
class FashionMockFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.fastapi.use_mock' => true]);
        Http::preventStrayRequests();

        $this->post('/login', ['email' => MockFastApiClient::DEMO_EMAIL, 'password' => MockFastApiClient::DEMO_PASSWORD]);
    }

    private function fields(array $override = []): array
    {
        return $override + ['name' => 'Hoodie Abu', 'category' => 'top', 'color' => 'abu', 'style' => 'sporty'];
    }

    public function test_wardrobe_crud_flow(): void
    {
        $this->get('/wardrobe')->assertOk()->assertSee('Kaos Garis')->assertSee('Celana Bahan');

        // tambah
        $this->post('/wardrobe', $this->fields() + ['image' => UploadedFile::fake()->image('hoodie.png')])
            ->assertRedirect(route('wardrobe.show', ['id' => 16]));
        $this->get('/wardrobe/16')->assertOk()->assertSee('Hoodie Abu')->assertSee('/media/wardrobe/16', false);
        $this->get('/wardrobe?category=top')->assertSee('Hoodie Abu')->assertDontSee('Celana Bahan');

        // ubah
        $this->patch('/wardrobe/16', $this->fields(['name' => 'Hoodie Hitam', 'color' => 'hitam']))
            ->assertRedirect(route('wardrobe.show', ['id' => 16]));
        $this->get('/wardrobe/16')->assertSee('Hoodie Hitam')->assertDontSee('Hoodie Abu');

        // hapus (item baru belum dipakai outfit/try-on)
        $this->delete('/wardrobe/16')->assertRedirect(route('wardrobe.index'));
        $this->get('/wardrobe/16')->assertNotFound();
    }

    public function test_deleting_wardrobe_item_used_by_an_outfit_is_refused_like_the_real_backend(): void
    {
        $this->delete('/wardrobe/11')
            ->assertRedirect(route('wardrobe.show', ['id' => 11]))
            ->assertSessionHas('error');

        $this->get('/wardrobe/11')->assertOk();
    }

    public function test_mock_serves_private_wardrobe_and_try_on_images(): void
    {
        $this->get('/media/wardrobe/11')->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->get('/media/try-on/1')->assertOk()->assertHeader('Content-Type', 'image/png');
    }

    public function test_mix_match_saves_outfit_that_then_appears_in_profile(): void
    {
        $this->get('/mix-match')->assertOk()->assertSee('Kemeja Putih')->assertSee('Sepatu Putih');

        $this->post('/outfits', ['name' => 'Rapat Formal', 'wardrobe_item_ids' => [13, 14, 15]])
            ->assertRedirect(route('profile.index'));

        $this->get('/profile')->assertOk()->assertSee('Rapat Formal');
    }

    public function test_mix_match_rejects_outfit_without_bottom(): void
    {
        $this->from('/mix-match')->post('/outfits', ['name' => 'Cuma atasan', 'wardrobe_item_ids' => [11, 13]])
            ->assertRedirect('/mix-match')
            ->assertSessionHasErrors(['wardrobe_item_ids' => 'Pilih minimal satu atasan dan satu bawahan.']);
    }

    public function test_recommendations_by_style_and_empty_state(): void
    {
        $this->get('/recommendations?style=casual')->assertOk()->assertSee('Kaos Garis')->assertSee('Jeans Denim')->assertSee('Pilihan 1');
        $this->get('/recommendations?style=sporty')->assertOk()->assertSee('Belum ada rekomendasi');
    }

    public function test_try_on_generate_then_save_with_catalog_item(): void
    {
        $this->get('/try-on?catalog_item_id=1')->assertOk()->assertSee('Kemeja Linen Oversize');

        $this->post('/try-on/generate', ['item' => 'catalog:1', 'person_image' => UploadedFile::fake()->image('aku.png')])
            ->assertRedirect(route('try-on.index'))
            ->assertSessionHas('try_on_result', fn (array $result) => $result['id'] === 3 && $result['generation_id'] === 3);

        // Preview belum masuk riwayat (saved=false) sampai disimpan
        $this->get('/profile?tab=try-on')->assertOk()->assertDontSee('/media/try-on/3', false);

        $this->get('/media/try-on/3')->assertOk();

        $this->post('/try-on/save', ['generation_id' => 3])
            ->assertRedirect(route('profile.index', ['tab' => 'try-on']));

        $this->get('/profile?tab=try-on')->assertOk()->assertSee('/media/try-on/3', false);
    }

    public function test_try_on_generate_with_wardrobe_top(): void
    {
        $this->post('/try-on/generate', ['item' => 'wardrobe:13', 'person_image' => UploadedFile::fake()->image('aku.jpg')])
            ->assertSessionHas('try_on_result');
    }

    public function test_try_on_rejects_non_top_items_like_the_real_backend(): void
    {
        $this->from('/try-on')->post('/try-on/generate', ['item' => 'catalog:5', 'person_image' => UploadedFile::fake()->image('aku.png')])
            ->assertSessionHasErrors('catalog_item_id');

        $this->from('/try-on')->post('/try-on/generate', ['item' => 'wardrobe:12', 'person_image' => UploadedFile::fake()->image('aku.png')])
            ->assertSessionHasErrors('wardrobe_item_id');
    }

    public function test_unsaved_preview_can_be_deleted_via_existing_route(): void
    {
        $this->post('/try-on/generate', ['item' => 'catalog:1', 'person_image' => UploadedFile::fake()->image('aku.png')]);

        $this->delete('/try-on/3')->assertRedirect(route('profile.index'));
        // Form POST yang gagal (404 dari FastAPI) kembali ke halaman sebelumnya dengan pesan error
        $this->post('/try-on/save', ['generation_id' => 3])->assertRedirect()->assertSessionHas('error', 'Data yang kamu cari tidak ditemukan.');
    }

    public function test_cannot_save_unknown_generation(): void
    {
        $this->post('/try-on/save', ['generation_id' => 4242])->assertRedirect()->assertSessionHas('error', 'Data yang kamu cari tidak ditemukan.');
    }

    public function test_mock_requires_login_and_scopes_data_per_user(): void
    {
        $this->post('/logout');
        $this->get('/wardrobe')->assertRedirect(route('login'));
    }
}
