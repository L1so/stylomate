<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;

class OutfitController extends Controller
{
    // Anggota 4 melengkapi builder dan menambah store (POST /outfits)
    public function builder() // GET /mix-match
    {
        return view('mix-match.index');
    }

    public function destroy(FastApiClient $api, int $id) // DELETE /outfits/{id}
    {
        $api->deleteOutfit($id);                            // FastAPI memastikan outfit milik user ini

        return redirect()->route('profile.index')->with('success', 'Outfit dihapus.');
    }
}
