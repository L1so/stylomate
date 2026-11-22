<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OutfitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\TryOnController;
use App\Http\Controllers\WardrobeController;
use App\Http\Middleware\EnsureApiSession;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute web StyloMate (PRD v1.1 bagian 11.1)
|--------------------------------------------------------------------------
| Bagian "ANGGOTA 2": Home, Auth, Catalog, Profile (di repo ini berupa STUB agar alur bisa diuji).
| Bagian "ANGGOTA 4": Wardrobe, Mix & Match, Recommendations, Try-On. Salin blok ini ke routes/web.php tim.
| Semua form mutasi memakai CSRF (@csrf) dan method spoofing (@method).
*/

// ---- ANGGOTA 2 (stub): halaman publik & autentikasi ----
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/dev/reset-mock', [DevController::class, 'resetMock'])->name('dev.reset');

// ---- Rute privat: middleware memeriksa session token (PRD 6.2) ----
Route::middleware(EnsureApiSession::class)->group(function () {
    // ANGGOTA 2 (stub)
    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
    Route::get('/catalog/{id}', [CatalogController::class, 'show'])->name('catalog.show');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

    // ANGGOTA 4: Wardrobe  (GET /wardrobe, /wardrobe/create, /wardrobe/{id}, /wardrobe/{id}/edit,
    //                       POST /wardrobe, PATCH /wardrobe/{id}, DELETE /wardrobe/{id})
    Route::resource('wardrobe', WardrobeController::class)->parameters(['wardrobe' => 'id']);

    // ANGGOTA 4: Mix & Match, Recommendations
    Route::get('/mix-match', [OutfitController::class, 'builder'])->name('mixmatch');
    Route::post('/outfits', [OutfitController::class, 'store'])->name('outfits.store');
    Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations');

    // ANGGOTA 4: Virtual Try-On
    Route::get('/try-on', [TryOnController::class, 'index'])->name('tryon');
    Route::post('/try-on/generate', [TryOnController::class, 'generate'])->name('tryon.generate');
    Route::post('/try-on/save', [TryOnController::class, 'save'])->name('tryon.save');
    Route::get('/try-on/result/{file}', [TryOnController::class, 'result'])->name('media.result');

    // Gambar: mock = berkas lokal; api = proxy ke FastAPI dengan token dari session
    Route::get('/media/mock/{file}', [MediaController::class, 'mock'])->name('media.mock');
    Route::get('/media/api/{path}', [MediaController::class, 'api'])->where('path', '.*')->name('media.api');
});
