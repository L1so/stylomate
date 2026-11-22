<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOutfitRequest;
use App\Services\FastApiClient;

class OutfitController extends Controller
{
    public function builder(FastApiClient $api) // GET /mix-match
    {
        $items = $api->wardrobeItems();

        // Kelompokkan per kategori (atasan dan bawahan paling atas); kategori kosong tidak ditampilkan
        $groups = [];
        foreach (FastApiClient::CATEGORIES as $key => $label) {
            $inGroup = array_values(array_filter($items, fn (array $item) => ($item['category'] ?? null) === $key));
            if ($inGroup !== []) {
                $groups[$key] = ['label' => $label, 'items' => $inGroup];
            }
        }

        return view('mix-match.index', compact('groups') + ['totalCount' => count($items)]);
    }

    public function store(StoreOutfitRequest $request, FastApiClient $api) // POST /outfits
    {
        $ids = array_map('intval', $request->validated('wardrobe_item_ids'));

        // Aturan PRD: outfit minimal satu top dan satu bottom. Dicek di sini agar error tampil di form;
        // FastAPI tetap menolak (422) bila aturan ini dilanggar.
        $owned = collect($api->wardrobeItems())->keyBy('id');
        $categories = collect($ids)->map(fn (int $id) => $owned[$id]['category'] ?? null);

        if ($categories->contains(fn ($category) => $category === null)) {
            return back()->withInput()->withErrors(['wardrobe_item_ids' => 'Ada pakaian yang tidak ditemukan di Wardrobe kamu.']);
        }

        if (! $categories->contains('top') || ! $categories->contains('bottom')) {
            return back()->withInput()->withErrors(['wardrobe_item_ids' => 'Pilih minimal satu atasan dan satu bawahan.']);
        }

        $api->createOutfit($request->validated('name'), $ids);

        return redirect()->route('profile.index')->with('success', 'Outfit berhasil disimpan.');
    }

    public function destroy(FastApiClient $api, int $id) // DELETE /outfits/{id}
    {
        $api->deleteOutfit($id);                            // FastAPI memastikan outfit milik user ini

        return redirect()->route('profile.index')->with('success', 'Outfit dihapus.');
    }
}
