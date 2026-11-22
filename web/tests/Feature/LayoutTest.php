<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->get('/_layout-test', fn () => view('layouts.app'));
        Route::middleware('web')->get('/_layout-private-test', fn () => Blade::render("@extends('layouts.app')\n@section('noindex', true)\n@section('content')\nisi\n@endsection"));
    }

    public function test_guest_sees_masuk_and_daftar_links(): void
    {
        $this->get('/_layout-test')
            ->assertOk()
            ->assertSee('Masuk')
            ->assertSee('Daftar')
            ->assertDontSee('Keluar')
            ->assertSee('og:title', false);
    }

    public function test_private_page_is_noindex_without_open_graph(): void
    {
        $this->get('/_layout-private-test')
            ->assertSee('noindex, nofollow', false)
            ->assertDontSee('og:title', false)
            ->assertDontSee('rel="canonical"', false);
    }

    public function test_logged_in_user_sees_private_menu_and_logout(): void
    {
        $this->withSession(['api_token' => 'token-uji'])
            ->get('/_layout-test')
            ->assertSee('Wardrobe')
            ->assertSee('Keluar')
            ->assertSee('aria-label="Profil"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('Daftar');
    }

    public function test_success_flash_message_is_shown(): void
    {
        $this->withSession(['success' => 'Data saved successfully.'])
            ->get('/_layout-test')
            ->assertSee('Data saved successfully.');
    }

    public function test_layout_sets_lang_and_csrf_meta(): void
    {
        $this->get('/_layout-test')
            ->assertSee('<html lang="id">', false)
            ->assertSee('name="csrf-token"', false);
    }
}
