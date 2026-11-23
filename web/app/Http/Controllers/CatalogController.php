<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class CatalogController extends Controller
{
    private const MAX_PAGE = 1000;

    public function index(Request $request, FastApiClient $api) // GET /catalog?search=...&category=...&page=...
    {
        // Validasi query di server: search maksimal 100 karakter, category hanya dari whitelist
        $searchInput = $request->query('search');
        $search = is_string($searchInput) ? mb_substr(trim($searchInput), 0, 100) : '';

        $categoryInput = $request->query('category');
        $category = is_string($categoryInput) && array_key_exists($categoryInput, FastApiClient::CATEGORIES) ? $categoryInput : '';

        $page = min(LengthAwarePaginator::resolveCurrentPage(), self::MAX_PAGE);
        $catalog = $api->catalog($search, $category, $page);

        if ($catalog['items'] === [] && $page > 1) {
            abort(404);
        }

        $items = new LengthAwarePaginator($catalog['items'], $catalog['total'], FastApiClient::CATALOG_PER_PAGE, $page, [
            'path' => $request->url(),
            'query' => array_filter(['search' => $search, 'category' => $category], fn (string $value) => $value !== ''),
        ]);
        $categories = FastApiClient::CATEGORIES;

        return view('catalog.index', compact('items', 'search', 'category', 'categories'));
    }

    public function show(FastApiClient $api, int $id) // GET /catalog/{id}
    {
        $item = $api->catalogItem($id);

        return view('catalog.show', compact('item'));
    }
}
