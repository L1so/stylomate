<?php

namespace App\Http\Controllers;

use App\Services\Fashion\FashionException;
use App\Services\FastApiClient;
use Illuminate\Http\Request;

/**
 * STUB milik anggota 2, mengikuti PRD 9.2: form -> Laravel -> FastAPI /api/auth/* -> token disimpan di
 * SESSION SERVER (bukan localStorage, bukan HTML). Dibuat agar fitur fashion bisa diuji dengan FastAPI.
 *
 * ASUMSI kontrak (sepakati dengan anggota 3):
 *   POST /api/auth/login    body JSON {email, password} -> {access_token, user:{...}}
 *   POST /api/auth/register body JSON {name, email, password} -> 201
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $response = FastApiClient::send(function () use ($data) {
                return FastApiClient::http()->post('/api/auth/login', ['email' => $data['email'], 'password' => $data['password']]);
            });
        } catch (FashionException $e) {
            // Kredensial salah: pesan seragam agar tidak membocorkan apakah email terdaftar (TC-04).
            $message = in_array($e->status, [400, 401, 404, 422], true) ? 'Email or password is incorrect.' : $e->getMessage();
            return back()->withInput($request->only('email'))->withErrors(['email' => $message]);
        }

        $token = $response->json('access_token') ?? $response->json('token');
        if (!$token) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'The server did not return an access token.']);
        }

        $request->session()->regenerate(); // cegah session fixation (PRD 6.2)
        $request->session()->put('access_token', $token);
        $request->session()->put('user', $response->json('user') ?? ['email' => $data['email']]);

        return redirect()->intended(route('wardrobe.index'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:80'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            FastApiClient::send(function () use ($data) {
                return FastApiClient::http()->post('/api/auth/register', [
                    'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
                ]);
            });
        } catch (FashionException $e) {
            return back()->withInput($request->only('name', 'email'))->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()->route('login')->with('status', 'Account created. Please log in.');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();      // hapus session + token (PRD 6.2, TC-17)
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
