<?php

// Konfigurasi fitur fashion (anggota 4). Nilai diatur lewat file .env.
return [
    // true = pakai data/AI PALSU (hanya untuk pengembangan sebelum FastAPI siap).
    // false = semua data lewat FastAPI (WAJIB untuk demo & penilaian: PRD v1.1 bagian 18).
    'mock_data'    => env('FASHION_MOCK_DATA', true),     // wardrobe + outfit + rekomendasi
    'mock_catalog' => env('FASHION_MOCK_CATALOG', true),  // katalog
    'mock_tryon'   => env('FASHION_MOCK_TRYON', true),    // Virtual Try-On

    // Alamat FastAPI (PRD 16.1: FASTAPI_BASE_URL).
    'api_base_url'  => rtrim(env('FASTAPI_BASE_URL', 'http://localhost:8000'), '/'),
    'api_timeout'   => 30,   // detik, request biasa
    'tryon_timeout' => 180,  // detik, inferensi AI (timeout terpisah, PRD 6.7)
];
