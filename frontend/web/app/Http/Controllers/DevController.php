<?php

namespace App\Http\Controllers;

use App\Services\Fashion\Mock\MockDb;

/** Alat pengembangan: tombol "Reset mock data" di banner kuning (hanya relevan di mode mock). */
class DevController extends Controller
{
    public function resetMock()
    {
        MockDb::reset();
        return redirect()->route('wardrobe.index')->with('status', 'Mock data was reset.');
    }
}
