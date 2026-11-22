<?php

namespace App\Http\Controllers;

use App\Exceptions\FastApiException;
use App\Services\FastApiClient;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(Request $request, FastApiClient $api)
    {
        // Validasi di Laravel dulu supaya error tampil lewat @error; FastAPI tetap memvalidasi ulang
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        try {
            $token = $api->login($credentials['email'], $credentials['password']);
        } catch (FastApiException $e) {
            if (in_array($e->status, [400, 401, 403], true)) {
                // Pesan GENERIK: jangan bocorkan apakah email terdaftar (PAWL P7)
                return back()->withErrors(['email' => 'Email atau kata sandi belum cocok. Periksa lagi.'])->onlyInput('email');
            }
            throw $e;
        }

        $request->session()->regenerate();                 // cegah session fixation
        $request->session()->put('api_token', $token);     // simpan JWT FastAPI di session

        return redirect()->intended(route('wardrobe.index'))->with('success', 'Berhasil masuk. Selamat datang kembali!');
    }

    public function register(Request $request, FastApiClient $api)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],   // butuh input password_confirmation
        ]);

        try {
            $api->register($data['name'], $data['email'], $data['password']);
        } catch (FastApiException $e) {
            // Sepakati dengan Anggota 3: email duplikat dibalas 409 (atau 400)
            if (in_array($e->status, [400, 409], true)) {
                return back()
                    ->withErrors(['email' => 'Email sudah terdaftar. Masuk dengan email ini atau gunakan email lain.'])
                    ->withInput($request->only('name', 'email'));
            }
            throw $e;
        }

        // Setelah daftar langsung login supaya mendapat JWT
        $token = $api->login($data['email'], $data['password']);
        $request->session()->regenerate();
        $request->session()->put('api_token', $token);

        return redirect()->route('wardrobe.index')->with('success', 'Akun berhasil dibuat. Selamat datang di StyloMate!');
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['api_token', 'api_user']);   // 1. hapus JWT dari session
        $request->session()->invalidate();                        // 2. hancurkan session
        $request->session()->regenerateToken();                   // 3. ganti token CSRF

        return redirect()->route('home')->with('success', 'Kamu sudah keluar. Sampai jumpa lagi!');
    }
}
