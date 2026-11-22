<?php

namespace App\Services\Fashion;

use Illuminate\Http\Request;

/**
 * Error standar semua service (mock maupun FastAPI). Pesannya AMAN ditampilkan ke pengguna.
 * $status: kode HTTP dari FastAPI; 0 = gagal sebelum ada respons (server mati, timeout).
 * Laravel memanggil render() otomatis saat exception ini tidak di-catch di controller.
 */
class FashionException extends \RuntimeException
{
    public int $status;

    public function __construct(string $message, int $status = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->status = $status;
    }

    public function httpStatus(): int
    {
        return ($this->status >= 400 && $this->status <= 599) ? $this->status : 502;
    }

    public function render(Request $request)
    {
        // PRD 7 / TC-07: 401 dari FastAPI -> session dibersihkan dan pengguna diarahkan ke login.
        if ($this->status === 401) {
            $request->session()->forget(['access_token', 'user']);
            if (!$request->expectsJson()) {
                return redirect()->route('login')->with('error', 'Your session has ended. Please log in again.');
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], $this->httpStatus());
        }

        return response()->view('errors.fashion', [
            'message' => $this->getMessage(),
            'code'    => $this->httpStatus(),
        ], $this->httpStatus());
    }
}
