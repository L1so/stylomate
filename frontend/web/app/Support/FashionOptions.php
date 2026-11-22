<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * Nilai tetap fitur fashion. SEPAKATI daftar ini dengan anggota 1 & 3:
 * - category dipetakan ke kategori model AI; - color/style dipakai mesin rekomendasi.
 */
class FashionOptions
{
    public const CATEGORIES = [
        ['value' => 'top', 'label' => 'Top', 'plural' => 'Tops'],
        ['value' => 'bottom', 'label' => 'Bottom', 'plural' => 'Bottoms'],
        ['value' => 'dress', 'label' => 'Dress', 'plural' => 'Dresses'],
        ['value' => 'outerwear', 'label' => 'Outerwear', 'plural' => 'Outerwear'],
        ['value' => 'shoes', 'label' => 'Shoes', 'plural' => 'Shoes'],
        ['value' => 'accessory', 'label' => 'Accessory', 'plural' => 'Accessories'],
    ];

    // PRD 6.6: casual, formal, semi-formal, sporty
    public const STYLES = [
        ['value' => 'casual', 'label' => 'Casual'],
        ['value' => 'semi-formal', 'label' => 'Semi-formal'],
        ['value' => 'formal', 'label' => 'Formal'],
        ['value' => 'sporty', 'label' => 'Sporty'],
    ];

    public const COLORS = [
        ['value' => 'black', 'label' => 'Black', 'hex' => '#222222'],
        ['value' => 'white', 'label' => 'White', 'hex' => '#f7f7f7'],
        ['value' => 'gray', 'label' => 'Gray', 'hex' => '#9e9e9e'],
        ['value' => 'beige', 'label' => 'Beige', 'hex' => '#d9c7a6'],
        ['value' => 'navy', 'label' => 'Navy', 'hex' => '#1f2a4d'],
        ['value' => 'brown', 'label' => 'Brown', 'hex' => '#7a5230'],
        ['value' => 'blue', 'label' => 'Blue', 'hex' => '#3b6fd4'],
        ['value' => 'red', 'label' => 'Red', 'hex' => '#c0392b'],
        ['value' => 'green', 'label' => 'Green', 'hex' => '#3f8f5a'],
        ['value' => 'yellow', 'label' => 'Yellow', 'hex' => '#e6c229'],
        ['value' => 'pink', 'label' => 'Pink', 'hex' => '#e58fb0'],
        ['value' => 'purple', 'label' => 'Purple', 'hex' => '#7b4fb0'],
        ['value' => 'orange', 'label' => 'Orange', 'hex' => '#e67e22'],
    ];

    /** Ambil kolom `value` dari salah satu daftar di atas. */
    public static function values(array $list): array
    {
        return array_column($list, 'value');
    }

    /** "black" -> "Black". Nilai tak dikenal dikembalikan apa adanya. */
    public static function label(array $list, ?string $value): string
    {
        foreach ($list as $option) {
            if ($option['value'] === $value) {
                return $option['label'];
            }
        }
        return (string) $value;
    }

    public static function hex(string $color): string
    {
        foreach (self::COLORS as $c) {
            if ($c['value'] === $color) {
                return $c['hex'];
            }
        }
        return '#9e9e9e';
    }

    /** Aturan validasi form pakaian. PRD NFR-03: JPEG/PNG, maks 10 MB, isi file diperiksa. */
    public static function itemRules(bool $imageRequired): array
    {
        return [
            'name'     => ['required', 'string', 'max:80'],
            'category' => ['required', Rule::in(self::values(self::CATEGORIES))],
            'color'    => ['required', Rule::in(self::values(self::COLORS))],
            'style'    => ['required', Rule::in(self::values(self::STYLES))],
            'image'    => [$imageRequired ? 'required' : 'nullable', 'file', 'mimetypes:image/jpeg,image/png', 'max:10240'],
        ];
    }

    public static function itemMessages(): array
    {
        return [
            'name.required'     => 'Enter a name for this item.',
            'name.max'          => 'The name is too long (max 80 characters).',
            'category.in'       => 'Choose a valid category.',
            'color.in'          => 'Choose a valid color.',
            'style.in'          => 'Choose a valid style.',
            'image.required'    => 'Choose a photo of the item (if you did, it may be larger than the server upload limit; see README).',
            'image.mimetypes'   => 'Use a JPEG or PNG image.',
            'image.max'         => 'The image is larger than 10 MB. Choose a smaller one.',
            'image.uploaded'    => 'The image could not be uploaded. It may be larger than the server limit (see README, upload limits).',
            'image.file'        => 'This file is not a readable image. Choose another one.',
        ];
    }
}
