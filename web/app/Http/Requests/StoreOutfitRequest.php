<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOutfitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'wardrobe_item_ids' => ['required', 'array', 'min:2', 'max:12'],
            'wardrobe_item_ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama outfit wajib diisi.',
            'name.max' => 'Nama outfit maksimal 100 karakter.',
            'wardrobe_item_ids.required' => 'Pilih minimal satu atasan dan satu bawahan.',
            'wardrobe_item_ids.array' => 'Pilihan pakaian tidak valid.',
            'wardrobe_item_ids.min' => 'Pilih minimal satu atasan dan satu bawahan.',
            'wardrobe_item_ids.max' => 'Satu outfit maksimal 12 pakaian.',
            'wardrobe_item_ids.*.integer' => 'Pilihan pakaian tidak valid.',
            'wardrobe_item_ids.*.min' => 'Pilihan pakaian tidak valid.',
            'wardrobe_item_ids.*.distinct' => 'Pilihan pakaian tidak valid.',
        ];
    }
}
