<?php

namespace App\Http\Controllers;

use App\Services\Fashion\Fashion;
use Illuminate\Http\Request;

/**
 * Mix & Match (PRD 11.1: GET /mix-match -> builder, POST /outfits -> store).
 * Halaman memuat pakaian sekali; interaksi (pilih, saran, simpan) dijalankan Alpine.js
 * yang memanggil GET /recommendations (RecommendationController) dan POST /outfits (di sini).
 */
class OutfitController extends Controller
{
    private const MIXABLE = ['top', 'bottom']; // MVP (PRD 6.5): atasan + bawahan

    // GET /mix-match?item=ID
    public function builder(Request $request)
    {
        $items = Fashion::wardrobe()->listItems();
        $candidates = array_values(array_filter($items, fn ($i) => in_array($i['category'], self::MIXABLE, true)));

        // Datang dari "Start Mix & Match" di Item Detail: item itu langsung terpilih.
        $preselect = null;
        $notice = null;
        $wanted = $request->query('item');
        if ($wanted !== null) {
            foreach ($items as $item) {
                if ((string) $item['id'] !== (string) $wanted) {
                    continue;
                }
                if (in_array($item['category'], self::MIXABLE, true)) {
                    $preselect = $item;
                } else {
                    $notice = 'Only tops and bottoms can be mixed for now.';
                }
            }
        }

        return view('outfits.builder', compact('candidates', 'preselect', 'notice'));
    }

    // POST /outfits  (JSON dari Alpine, atau form biasa dari halaman Recommendations)
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['nullable', 'string', 'max:60'],
            'item_ids'   => ['required', 'array', 'size:2'],
            'item_ids.*' => ['required'],
        ], [
            'item_ids.required' => 'Pick at least one top and one bottom first.',
            'item_ids.size'     => 'Pick at least one top and one bottom first.',
        ]);

        $name = trim((string) ($data['name'] ?? '')) ?: 'My outfit';
        $outfit = Fashion::outfits()->createOutfit($name, $data['item_ids']);

        if ($request->expectsJson()) {
            return response()->json($outfit, 201);
        }
        return back()->with('status', 'Outfit saved: ' . $outfit['name'] . '. You can find it in your Profile.');
    }
}
