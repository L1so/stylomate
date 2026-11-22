<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\Request;

// Smart Outfit Curation (F-06): aturan rekomendasi ada di FastAPI; Laravel hanya meneruskan style dan menampilkan hasil
class RecommendationController extends Controller
{
    public function index(Request $request, FastApiClient $api) // GET /recommendations?style=casual
    {
        $styleInput = $request->query('style');
        $style = is_string($styleInput) && array_key_exists($styleInput, FastApiClient::STYLES) ? $styleInput : '';

        $styles = FastApiClient::STYLES;
        $outfits = $style === '' ? null : $api->recommendations($style);

        return view('recommendations.index', compact('styles', 'style', 'outfits'));
    }
}
