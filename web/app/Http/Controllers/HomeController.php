<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;

class HomeController extends Controller
{
    public function index(FastApiClient $api) // GET /
    {
        // Homepage publik tidak boleh gagal hanya karena Catalog error; section Catalog disembunyikan
        $products = array_slice(rescue(fn () => $api->catalog(perPage: 4)['items'], [], false), 0, 4);

        return view('home', compact('products'));
    }
}
