<?php

namespace App\Http\Controllers;

use App\Services\Fashion\Fashion;

/** STUB milik anggota 2 (Catalog): GET /catalog dan GET /catalog/{id} -> FastAPI. */
class CatalogController extends Controller
{
    public function index()
    {
        return view('catalog.index', ['items' => Fashion::catalog()->listItems()]);
    }

    public function show($id)
    {
        return view('catalog.show', ['item' => Fashion::catalog()->getItem($id)]);
    }
}
