<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request, FastApiClient $api) // GET /profile?tab=outfit|try-on
    {
        // Tab hanya boleh dari whitelist; selain itu kembali ke tab outfit
        $tab = $request->query('tab') === 'try-on' ? 'try-on' : 'outfit';
        $user = $api->me();
        $outfits = $api->outfits();
        $tryOns = $api->tryOnHistory();

        return view('profile.index', compact('user', 'outfits', 'tryOns', 'tab'));
    }
}
