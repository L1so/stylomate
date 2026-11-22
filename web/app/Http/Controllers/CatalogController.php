<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request, FastApiClient $api) // GET /catalog?search=...&category=...
    {
        // Validasi query di server: search maksimal 100 karakter, category hanya dari whitelist
        $searchInput = $request->query('search');
        $search = is_string($searchInput) ? mb_substr(trim($searchInput), 0, 100) : '';

        $categoryInput = $request->query('category');
        $category = is_string($categoryInput) && array_key_exists($categoryInput, FastApiClient::CATEGORIES) ? $categoryInput : '';

        $items = $api->catalog($search, $category);
        $categories = FastApiClient::CATEGORIES;

        return view('catalog.index', compact('items', 'search', 'category', 'categories'));
    }

    public function show(FastApiClient $api, int $id) // GET /catalog/{id}
    {
        $item = $api->catalogItem($id);

        return view('catalog.show', compact('item'));
    }
}
