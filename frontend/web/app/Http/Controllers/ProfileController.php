<?php

namespace App\Http\Controllers;

use App\Services\Fashion\Fashion;

/** STUB milik anggota 2 (Profile): outfit tersimpan + riwayat try-on, semua dari FastAPI. */
class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index', [
            'user'    => session('user'),
            'outfits' => Fashion::outfits()->listOutfits(),
            'history' => Fashion::tryOn()->listHistory(),
        ]);
    }
}
