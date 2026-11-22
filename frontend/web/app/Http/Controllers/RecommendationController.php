<?php

namespace App\Http\Controllers;

use App\Services\Fashion\Fashion;
use App\Support\FashionOptions;
use Illuminate\Http\Request;

/**
 * Smart Outfit Curation (PRD 6.6). Aturan rekomendasi HANYA ada di FastAPI (PRD 9.1);
 * controller ini cuma meneruskan pilihan style dan menampilkan hasilnya.
 *
 *  - Accept: application/json (dipakai Mix & Match)  -> JSON
 *  - Browser biasa                                    -> halaman Blade recommendations/index
 */
class RecommendationController extends Controller
{
    // GET /recommendations?style=casual
    public function index(Request $request)
    {
        $styles = FashionOptions::values(FashionOptions::STYLES);
        $style = (string) $request->query('style', 'casual');
        abort_unless(in_array($style, $styles, true), 422, 'Unsupported style.');

        // Halaman: tampilkan form dulu; hasil baru diminta setelah style dikirim.
        if (!$request->expectsJson() && !$request->has('style')) {
            return view('recommendations.index', ['style' => $style, 'result' => null]);
        }

        $result = Fashion::recommendations()->getRecommendations($style);

        if ($request->expectsJson()) {
            return response()->json($result);
        }
        return view('recommendations.index', ['style' => $style, 'result' => $result]);
    }
}
