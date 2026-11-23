<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OutfitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.fastapi.use_mock' => false]);
        Http::preventStrayRequests();
    }

    private function wardrobe(): array
    {
        return ['data' => [
            ['id' => 5, 'name' => 'Kemeja Putih', 'category' => 'top', 'color' => 'putih', 'style' => 'formal'],
            ['id' => 6, 'name' => 'Celana Bahan', 'category' => 'bottom', 'color' => 'hitam', 'style' => 'formal'],
            ['id' => 7, 'name' => 'Kaos Garis', 'category' => 'top', 'color' => 'putih', 'style' => 'casual'],
            ['id' => 8, 'name' => 'Sepatu Putih', 'category' => 'shoes', 'color' => 'putih', 'style' => 'casual'],
        ]];
    }

    private function asUser(): static
    {
        return $this->withSession(['api_token' => 'jwt-1']);
    }

    public function test_builder_groups_wardrobe_items_by_category(): void
    {
        Http::fake(['*/wardrobe?*' => Http::response($this->wardrobe())]);

        $this->asUser()->get('/mix-match')
            ->assertOk()
            ->assertSeeInOrder(['Atasan', 'Kemeja Putih', 'Bawahan', 'Celana Bahan', 'Sepatu', 'Sepatu Putih'])
            ->assertSee('name="wardrobe_item_ids[]"', false)
            ->assertSee('noindex, nofollow', false);
    }

    public function test_builder_shows_empty_state_when_wardrobe_is_empty(): void
    {
        Http::fake(['*/wardrobe?*' => Http::response(['data' => []])]);

        $this->asUser()->get('/mix-match')->assertOk()->assertSee('Wardrobe kamu masih kosong')->assertDontSee('Simpan Outfit');
    }

    public function test_store_posts_name_and_item_ids_as_json(): void
    {
        Http::fake([
            '*/wardrobe?*' => Http::response($this->wardrobe()),
            '*/outfits' => Http::response(['data' => ['id' => 9]], 201),
        ]);

        $this->asUser()->post('/outfits', ['name' => 'Santai ke Kampus', 'wardrobe_item_ids' => ['7', '6']])
            ->assertRedirect(route('profile.index'))
            ->assertSessionHas('success', 'Outfit berhasil disimpan.');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/outfits')
            && $request->hasHeader('Authorization', 'Bearer jwt-1')
            && $request->data() === ['name' => 'Santai ke Kampus', 'wardrobe_item_ids' => [7, 6]]);
    }

    public function test_store_requires_at_least_one_top_and_one_bottom(): void
    {
        Http::fake(['*/wardrobe?*' => Http::response($this->wardrobe())]);

        foreach ([['5', '7'], ['6', '8'], ['5', '8']] as $ids) {
            $this->asUser()->from('/mix-match')
                ->post('/outfits', ['name' => 'Tidak lengkap', 'wardrobe_item_ids' => $ids])
                ->assertRedirect('/mix-match')
                ->assertSessionHasErrors(['wardrobe_item_ids' => 'Pilih minimal satu atasan dan satu bawahan.']);
        }

        Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), '/outfits'));
    }

    public function test_store_rejects_items_not_in_users_wardrobe(): void
    {
        Http::fake(['*/wardrobe?*' => Http::response($this->wardrobe())]);

        $this->asUser()->from('/mix-match')
            ->post('/outfits', ['name' => 'Milik orang lain', 'wardrobe_item_ids' => ['5', '999']])
            ->assertSessionHasErrors('wardrobe_item_ids');

        Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), '/outfits'));
    }

    public function test_store_validates_name_and_ids_before_calling_fastapi(): void
    {
        $this->asUser()->from('/mix-match')->post('/outfits', ['name' => '', 'wardrobe_item_ids' => ['abc']])
            ->assertSessionHasErrors(['name', 'wardrobe_item_ids.0']);

        $this->asUser()->from('/mix-match')->post('/outfits', ['name' => 'Satu', 'wardrobe_item_ids' => ['5']])
            ->assertSessionHasErrors('wardrobe_item_ids');

        $this->asUser()->from('/mix-match')->post('/outfits', ['name' => str_repeat('a', 101), 'wardrobe_item_ids' => ['5', '6']])
            ->assertSessionHasErrors('name');

        Http::assertNothingSent();
    }

    public function test_store_shows_fastapi_422_error_on_the_form(): void
    {
        Http::fake([
            '*/wardrobe?*' => Http::response($this->wardrobe()),
            '*/outfits' => Http::response(['error' => ['message' => 'x', 'fields' => [['field' => 'name', 'message' => 'Nama outfit sudah dipakai.']]]], 422),
        ]);

        $this->asUser()->from('/mix-match')->post('/outfits', ['name' => 'Dobel', 'wardrobe_item_ids' => ['5', '6']])
            ->assertSessionHasErrors(['name' => 'Nama outfit sudah dipakai.']);
    }

    public function test_guest_cannot_create_outfit(): void
    {
        $this->post('/outfits', ['name' => 'X', 'wardrobe_item_ids' => ['5', '6']])->assertRedirect(route('login'));

        Http::assertNothingSent();
    }
}
