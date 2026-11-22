<?php

namespace App\Http\Controllers;

// Placeholder Anggota 4: store, update, destroy menyusul sesuai PRD v1.1 §11.1
class WardrobeController extends Controller
{
    public function index() // GET /wardrobe
    {
        return view('wardrobe.index');
    }

    public function create() // GET /wardrobe/create
    {
        return view('wardrobe.create');
    }

    public function show(int $id) // GET /wardrobe/{id}
    {
        return view('wardrobe.show', compact('id'));
    }
}
