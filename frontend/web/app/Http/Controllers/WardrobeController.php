<?php

namespace App\Http\Controllers;

use App\Services\Fashion\Fashion;
use App\Services\Fashion\FashionException;
use App\Support\FashionOptions;
use Illuminate\Http\Request;

/** Halaman Wardrobe, Add Item, Item Detail, Edit Item (Route::resource 'wardrobe'). */
class WardrobeController extends Controller
{
    // GET /wardrobe?category=top
    public function index(Request $request)
    {
        $items = Fashion::wardrobe()->listItems();
        $category = (string) $request->query('category', 'all');

        $visible = array_values(array_filter($items, fn ($i) => $category === 'all' || $i['category'] === $category));

        $counts = ['all' => count($items)];
        foreach ($items as $item) {
            $counts[$item['category']] = ($counts[$item['category']] ?? 0) + 1;
        }

        return view('wardrobe.index', compact('items', 'visible', 'category', 'counts'));
    }

    // GET /wardrobe/create
    public function create()
    {
        return view('wardrobe.form', ['item' => null]);
    }

    // POST /wardrobe
    public function store(Request $request)
    {
        $data = $request->validate(FashionOptions::itemRules(true), FashionOptions::itemMessages());

        try {
            $item = Fashion::wardrobe()->createItem($data, $request->file('image'));
        } catch (FashionException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('wardrobe.show', $item['id'])->with('status', 'Item saved.'); // PRD 7.3
    }

    // GET /wardrobe/{id}
    public function show($id)
    {
        return view('wardrobe.show', ['item' => Fashion::wardrobe()->getItem($id)]);
    }

    // GET /wardrobe/{id}/edit
    public function edit($id)
    {
        return view('wardrobe.form', ['item' => Fashion::wardrobe()->getItem($id)]);
    }

    // PATCH /wardrobe/{id} (Route::resource juga menerima PUT)
    public function update(Request $request, $id)
    {
        $data = $request->validate(FashionOptions::itemRules(false), FashionOptions::itemMessages());

        try {
            $item = Fashion::wardrobe()->updateItem($id, $data, $request->file('image'));
        } catch (FashionException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('wardrobe.show', $item['id'])->with('status', 'Item updated.');
    }

    // DELETE /wardrobe/{id}
    public function destroy($id)
    {
        Fashion::wardrobe()->deleteItem($id);
        return redirect()->route('wardrobe.index')->with('status', 'Item deleted.');
    }
}
