<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\OutfitController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\TryOnController;
use App\Http\Controllers\WardrobeController;
use Illuminate\Support\Facades\Route;

// Halaman publik
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{id}', [CatalogController::class, 'show'])->whereNumber('id')->name('catalog.show');

// Hanya tamu (padanan middleware guest, PAWL P7)
Route::middleware('api.guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');         // batasi 10 percobaan per menit
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

// Hanya yang sudah login (padanan middleware auth, PAWL P7)
Route::middleware('api.token')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');   // logout wajib POST
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::delete('/outfits/{id}', [OutfitController::class, 'destroy'])->whereNumber('id')->name('outfits.destroy');
    Route::delete('/try-on/{id}', [TryOnController::class, 'destroy'])->whereNumber('id')->name('try-on.destroy');
    // Proxy gambar privat: diambil dari FastAPI dengan JWT di server lalu diteruskan ke browser
    Route::get('/media/try-on/{id}', [MediaController::class, 'tryOn'])->where('id', '[0-9]{1,9}')->name('media.try-on');
    Route::get('/media/wardrobe/{id}', [MediaController::class, 'wardrobe'])->where('id', '[0-9]{1,9}')->name('media.wardrobe');

    // Halaman fashion milik Anggota 4 (PRD v1.1 §11.1; nama route yang sudah ada jangan diubah)
    Route::get('/wardrobe', [WardrobeController::class, 'index'])->name('wardrobe.index');
    Route::get('/wardrobe/create', [WardrobeController::class, 'create'])->name('wardrobe.create');
    Route::post('/wardrobe', [WardrobeController::class, 'store'])->name('wardrobe.store');
    Route::get('/wardrobe/{id}', [WardrobeController::class, 'show'])->whereNumber('id')->name('wardrobe.show');
    Route::get('/wardrobe/{id}/edit', [WardrobeController::class, 'edit'])->whereNumber('id')->name('wardrobe.edit');
    Route::patch('/wardrobe/{id}', [WardrobeController::class, 'update'])->whereNumber('id')->name('wardrobe.update');
    Route::delete('/wardrobe/{id}', [WardrobeController::class, 'destroy'])->whereNumber('id')->name('wardrobe.destroy');
    Route::get('/mix-match', [OutfitController::class, 'builder'])->name('mix-match.index');
    Route::post('/outfits', [OutfitController::class, 'store'])->name('outfits.store');
    Route::get('/try-on', [TryOnController::class, 'index'])->name('try-on.index');
    Route::post('/try-on/warmup', [TryOnController::class, 'warmup'])->name('try-on.warmup');
    Route::post('/try-on/generate', [TryOnController::class, 'generate'])->middleware('throttle:10,1')->name('try-on.generate');
    Route::post('/try-on/save', [TryOnController::class, 'save'])->name('try-on.save');
    Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations.index');
});
