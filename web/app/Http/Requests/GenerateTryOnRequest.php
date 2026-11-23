<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// Try-On: foto orang + tepat satu sumber. 'item' berbentuk "wardrobe:12", "catalog:3", atau "outfit:5".
class GenerateTryOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item' => ['required', 'string', 'regex:/^(wardrobe|catalog|outfit):[1-9][0-9]{0,9}$/'],
            'person_image' => ['required', 'file', 'mimetypes:image/jpeg,image/png', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'item.required' => 'Pilih satu pakaian atau outfit untuk Try On.',
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

    /** @return array{0: ?int, 1: ?int, 2: ?int} [wardrobe_item_id, catalog_item_id, outfit_id] */
    public function selectedIds(): array
    {
        [$source, $id] = explode(':', $this->validated('item'));

        return match ($source) {
            'wardrobe' => [(int) $id, null, null],
            'catalog' => [null, (int) $id, null],
            default => [null, null, (int) $id],
        };
    }
}
