<?php

namespace App\Http\Controllers;

use App\Services\FastApiClient;

class TryOnController extends Controller
{
    // Anggota 4 melengkapi index dan menambah generate/save (POST /try-on/generate, /try-on/save)
    public function index() // GET /try-on
    {
        return view('try-on.index');
    }

    public function destroy(FastApiClient $api, int $id) // DELETE /try-on/{id}
    {
        $api->deleteTryOn($id);

        return redirect()->route('profile.index')->with('success', 'Hasil Try On dihapus.');
    }
}
