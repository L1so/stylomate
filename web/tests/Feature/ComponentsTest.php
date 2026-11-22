<?php

namespace Tests\Feature;

use Tests\TestCase;

class ComponentsTest extends TestCase
{
    public function test_button_with_href_renders_link(): void
    {
        $this->withViewErrors([])
            ->blade('<x-button label="View" href="/catalog" />')
            ->assertSee('<a href="/catalog"', false)
            ->assertDontSee('<button', false);
    }

    public function test_button_external_link_opens_in_new_tab_safely(): void
    {
        $this->withViewErrors([])
            ->blade('<x-button label="Buka" href="https://example.com" :external="true" />')
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('(tab baru)');
    }

    public function test_button_without_href_renders_submit_button(): void
    {
        $this->withViewErrors([])
            ->blade('<x-button label="Save" />')
            ->assertSee('type="submit"', false);
    }

    public function test_components_do_not_leak_parent_variables(): void
    {
        $this->withViewErrors([])
            ->blade('@php($href = "/bocor") <x-button label="Kirim" />')
            ->assertSee('type="submit"', false)
            ->assertDontSee('/bocor', false);
    }

    public function test_input_shows_validation_error_and_aria_invalid(): void
    {
        $this->withViewErrors(['email' => 'The email field is required.'])
            ->blade('<x-input name="email" label="Email" type="email" />')
            ->assertSee('The email field is required.')
            ->assertSee('aria-invalid="true"', false);
    }

    public function test_input_renders_hint_and_links_it_to_field(): void
    {
        $this->withViewErrors([])
            ->blade('<x-input name="password" label="Password" type="password" hint="Gabungkan huruf dan angka." />')
            ->assertSee('Gabungkan huruf dan angka.')
            ->assertSee('aria-describedby="password-hint"', false);
    }

    public function test_password_input_never_echoes_value(): void
    {
        $this->withViewErrors([])
            ->blade('<x-input name="password" label="Password" type="password" value="rahasia123" />')
            ->assertDontSee('rahasia123');
    }

    public function test_product_card_escapes_html_and_shows_placeholder_without_image(): void
    {
        $item = [
            'name' => '<script>alert(1)</script>',
            'brand' => 'Merek',
            'category_label' => 'Atasan',
            'image_url' => null,
        ];

        $this->withViewErrors([])
            ->blade('<x-product-card :item="$item" href="/catalog/1" />', ['item' => $item])
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Belum ada foto')
            ->assertSee('Atasan');
    }

    public function test_product_card_shows_store_button_only_with_product_url(): void
    {
        $item = [
            'name' => 'Kemeja Linen',
            'brand' => 'Merek',
            'category_label' => 'Atasan',
            'image_url' => null,
            'product_url' => 'https://example.com/p/3',
        ];

        $this->withViewErrors([])
            ->blade('<x-product-card :item="$item" href="/catalog/3" />', ['item' => $item])
            ->assertSee('aria-label="Lihat Kemeja Linen di toko (tab baru)"', false);

        $this->withViewErrors([])
            ->blade('<x-product-card :item="$item" href="/catalog/3" />', ['item' => array_merge($item, ['product_url' => null])])
            ->assertDontSee('di toko', false);
    }

    public function test_product_card_shows_try_on_button_when_href_given(): void
    {
        $item = ['name' => 'Kemeja Linen', 'brand' => 'Merek', 'image_url' => null];

        $this->withViewErrors([])
            ->blade('<x-product-card :item="$item" href="/catalog/3" try-on-href="/try-on?catalog_item_id=3" />', ['item' => $item])
            ->assertSee('Try On')
            ->assertSee('href="/try-on?catalog_item_id=3"', false);
    }

    public function test_date_component_formats_iso_date_in_indonesian(): void
    {
        $this->withViewErrors([])
            ->blade('<x-date value="2026-11-20T08:00:00Z" />')
            ->assertSee('20 Nov 2026');

        $this->withViewErrors([])
            ->blade('<x-date value="bukan-tanggal" />')
            ->assertDontSee('bukan-tanggal');
    }

    public function test_clothing_card_marks_selected_item(): void
    {
        $item = [
            'name' => 'Kemeja Putih',
            'category' => 'atasan',
            'color' => 'putih',
            'style' => 'formal',
            'image_url' => null,
        ];

        $this->withViewErrors([])
            ->blade('<x-clothing-card :item="$item" :selected="true" />', ['item' => $item])
            ->assertSee('(dipilih)')
            ->assertSee('formal');
    }

    public function test_confirm_delete_uses_delete_method_csrf_and_confirmation(): void
    {
        $this->withViewErrors([])
            ->blade('<x-confirm-delete action="/outfits/1" />')
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('data-confirm=', false)
            ->assertSee('action="/outfits/1"', false);
    }

    public function test_empty_state_shows_action_link(): void
    {
        $this->withViewErrors([])
            ->blade('<x-empty-state title="No outfits yet" action-label="Create outfit" action-url="/mix-match" />')
            ->assertSee('No outfits yet')
            ->assertSee('href="/mix-match"', false);
    }

    public function test_error_state_has_alert_role(): void
    {
        $this->withViewErrors([])
            ->blade('<x-error-state message="The server is unreachable." />')
            ->assertSee('role="alert"', false)
            ->assertSee('The server is unreachable.');
    }

    public function test_loading_hidden_variant_has_indicator_attribute(): void
    {
        $this->withViewErrors([])
            ->blade('<x-loading :hidden="true" />')
            ->assertSee('data-loading-indicator', false);
    }
}
