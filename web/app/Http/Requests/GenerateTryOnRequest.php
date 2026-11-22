<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Try-On: foto orang (JPEG/PNG, maks 10 MB) + tepat satu item. 'item' berbentuk "wardrobe:12" atau "catalog:3"
class GenerateTryOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item' => ['required', 'string', 'regex:/^(wardrobe|catalog):[1-9][0-9]{0,9}$/'],
            'person_image' => ['required', 'file', 'mimetypes:image/jpeg,image/png', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'item.required' => 'Pilih satu atasan dari Wardrobe atau Catalog.',
            'item.regex' => 'Pilihan pakaian tidak valid.',
            'item.string' => 'Pilihan pakaian tidak valid.',
            'person_image.required' => 'Foto kamu wajib diunggah.',
            'person_image.file' => 'Foto harus berupa file.',
            'person_image.uploaded' => 'Foto gagal diunggah. Pastikan ukurannya tidak lebih dari 10 MB.',
            'person_image.mimetypes' => 'Foto harus berformat JPEG atau PNG.',
            'person_image.mimes' => 'Foto harus berformat JPEG atau PNG.',
            'person_image.max' => 'Ukuran foto maksimal 10 MB.',
        ];
    }

    /** @return array{0: ?int, 1: ?int} [wardrobe_item_id, catalog_item_id] */
    public function selectedIds(): array
    {
        [$source, $id] = explode(':', $this->validated('item'));

        return $source === 'wardrobe' ? [(int) $id, null] : [null, (int) $id];
    }
}
