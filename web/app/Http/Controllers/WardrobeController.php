<?php

namespace App\Http\Controllers;

use App\Exceptions\FastApiException;
use App\Http\Requests\WardrobeItemRequest;
use App\Services\FastApiClient;
use Illuminate\Http\Request;

// Wardrobe (F-04): semua data milik FastAPI; controller hanya validasi awal, memanggil FastApiClient, dan merender Blade
class WardrobeController extends Controller
{
    public function index(Request $request, FastApiClient $api) // GET /wardrobe?category=top
    {
        // Filter kategori hanya dari whitelist
        $categoryInput = $request->query('category');
        $category = is_string($categoryInput) && array_key_exists($categoryInput, FastApiClient::CATEGORIES) ? $categoryInput : '';

        $allItems = $api->wardrobeItems();
        $items = $category === '' ? $allItems : array_values(array_filter($allItems, fn (array $item) => ($item['category'] ?? null) === $category));
        $categories = FastApiClient::CATEGORIES;

        return view('wardrobe.index', compact('items', 'category', 'categories') + ['totalCount' => count($allItems)]);
    }

    public function create() // GET /wardrobe/create
    {
        return view('wardrobe.create', [
            'categories' => FastApiClient::CATEGORIES,
            'styles' => FastApiClient::STYLES,
        ]);
    }

    public function store(WardrobeItemRequest $request, FastApiClient $api) // POST /wardrobe (multipart)
    {
        $item = $api->createWardrobeItem($request->fields(), $request->file('image'));

        $target = isset($item['id']) && is_numeric($item['id']) ? route('wardrobe.show', ['id' => (int) $item['id']]) : route('wardrobe.index');

        return redirect($target)->with('success', 'Pakaian berhasil ditambahkan ke Wardrobe.');
    }

    public function show(FastApiClient $api, int $id) // GET /wardrobe/{id}
    {
        $item = $api->wardrobeItem($id);

        return view('wardrobe.show', compact('item'));
    }

    public function edit(FastApiClient $api, int $id) // GET /wardrobe/{id}/edit
    {
        $item = $api->wardrobeItem($id);

        return view('wardrobe.edit', [
            'item' => $item,
            'categories' => FastApiClient::CATEGORIES,
            'styles' => FastApiClient::STYLES,
        ]);
    }

    public function update(WardrobeItemRequest $request, FastApiClient $api, int $id) // PATCH /wardrobe/{id}
    {
        $api->updateWardrobeItem($id, $request->fields(), $request->file('image'));

        return redirect()->route('wardrobe.show', ['id' => $id])->with('success', 'Data pakaian diperbarui.');
    }

    public function destroy(FastApiClient $api, int $id) // DELETE /wardrobe/{id}
    {
        try {
            $api->deleteWardrobeItem($id);   // FastAPI memastikan item milik user ini
        } catch (FastApiException $e) {
            if ($e->status === 409) {
                // Item masih dipakai outfit atau hasil Try-On
                return redirect()->route('wardrobe.show', ['id' => $id])->with('error', 'Pakaian ini masih dipakai di outfit atau hasil Try On. Hapus dulu outfit atau hasil Try On tersebut.');
            }
            throw $e;
        }

        return redirect()->route('wardrobe.index')->with('success', 'Pakaian dihapus dari Wardrobe.');
    }
}
