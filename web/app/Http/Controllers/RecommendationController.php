<?php

namespace App\Http\Controllers;

// Placeholder Anggota 4: memanggil GET /api/recommendations?style=...
class RecommendationController extends Controller
{
    public function index() // GET /recommendations
    {
        return view('recommendations.index');
    }
}
