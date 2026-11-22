<?php

namespace App\Http\Requests;

use App\Services\FastApiClient;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// Validasi awal Wardrobe di Laravel; FastAPI tetap memvalidasi ulang. Dipakai untuk tambah (foto wajib) dan edit (tanpa foto)
class WardrobeItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;   // akses dijaga middleware api.token; kepemilikan data dicek FastAPI
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', Rule::in(array_keys(FastApiClient::CATEGORIES))],
            'color' => ['required', 'string', 'max:30'],
            'style' => ['required', Rule::in(array_keys(FastApiClient::STYLES))],
        ];

        // JPEG/PNG, maks 10 MB. Wajib saat tambah; saat edit boleh kosong (foto lama dipertahankan)
        $rules['image'] = [$this->isMethod('post') ? 'required' : 'nullable', 'file', 'mimetypes:image/jpeg,image/png', 'mimes:jpg,jpeg,png', 'max:10240'];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute harus berupa teks.',
            'max.string' => ':Attribute maksimal :max karakter.',
            'in' => ':Attribute tidak valid. Pilih salah satu dari daftar.',
            'image.required' => 'Foto pakaian wajib diunggah.',
            'image.file' => 'Foto pakaian harus berupa file.',
            'image.uploaded' => 'Foto gagal diunggah. Pastikan ukurannya tidak lebih dari 10 MB.',
            'image.mimetypes' => 'Foto harus berformat JPEG atau PNG.',
            'image.mimes' => 'Foto harus berformat JPEG atau PNG.',
            'image.max' => 'Ukuran foto maksimal 10 MB.',
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'category' => 'kategori', 'color' => 'warna', 'style' => 'style', 'image' => 'foto'];
    }

    // Data siap kirim ke FastAPI: tanpa foto, nama/warna dirapikan
    public function fields(): array
    {
        $data = $this->validated();

        return [
            'name' => trim($data['name']),
            'category' => $data['category'],
            'color' => trim($data['color']),
            'style' => $data['style'],
        ];
    }
}
