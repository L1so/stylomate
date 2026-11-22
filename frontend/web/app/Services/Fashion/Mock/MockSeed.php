<?php

namespace App\Services\Fashion\Mock;

use App\Services\Fashion\FashionException;
use App\Support\FashionOptions;

/** Data awal mode mock. Gambar pakaian digambar dengan PHP GD (PNG transparan 200x220). */
class MockSeed
{
    // Titik poligon (x1,y1,x2,y2,...) kira-kira siluet pakaian.
    private const SHAPES = [
        'top'       => [60, 30, 85, 20, 100, 38, 115, 20, 140, 30, 180, 70, 155, 95, 140, 82, 140, 190, 60, 190, 60, 82, 45, 95, 20, 70],
        'bottom'    => [55, 20, 145, 20, 152, 200, 108, 200, 100, 80, 92, 200, 48, 200],
        'dress'     => [80, 20, 120, 20, 128, 70, 170, 205, 30, 205, 72, 70],
        'outerwear' => [55, 25, 85, 18, 100, 40, 115, 18, 145, 25, 185, 75, 160, 100, 145, 88, 145, 200, 55, 200, 55, 88, 40, 100, 15, 75],
        'shoes'     => [20, 140, 30, 115, 60, 110, 90, 140, 170, 150, 185, 170, 185, 180, 20, 180],
    ];

    /** Sama dengan SEED di backend-fashion/test_recommendation.py. */
    public static function wardrobeMeta(): array
    {
        return [
            ['id' => 1, 'name' => 'White Tee', 'category' => 'top', 'color' => 'white', 'style' => 'casual'],
            ['id' => 2, 'name' => 'Blue Oxford Shirt', 'category' => 'top', 'color' => 'blue', 'style' => 'semi-formal'],
            ['id' => 3, 'name' => 'White Dress Shirt', 'category' => 'top', 'color' => 'white', 'style' => 'formal'],
            ['id' => 4, 'name' => 'Grey Hoodie', 'category' => 'top', 'color' => 'gray', 'style' => 'casual'],
            ['id' => 5, 'name' => 'Black Sports Jersey', 'category' => 'top', 'color' => 'black', 'style' => 'sporty'],
            ['id' => 6, 'name' => 'Blue Jeans', 'category' => 'bottom', 'color' => 'blue', 'style' => 'casual'],
            ['id' => 7, 'name' => 'Black Dress Pants', 'category' => 'bottom', 'color' => 'black', 'style' => 'formal'],
            ['id' => 8, 'name' => 'Beige Chinos', 'category' => 'bottom', 'color' => 'beige', 'style' => 'semi-formal'],
            ['id' => 9, 'name' => 'Navy Track Pants', 'category' => 'bottom', 'color' => 'navy', 'style' => 'sporty'],
            ['id' => 10, 'name' => 'Red Dress', 'category' => 'dress', 'color' => 'red', 'style' => 'semi-formal'],
            ['id' => 11, 'name' => 'White Sneakers', 'category' => 'shoes', 'color' => 'white', 'style' => 'casual'],
        ];
    }

    /** Katalog contoh. Bukan produk/brand mitra sungguhan (PRD 6.3). */
    public static function catalogMeta(): array
    {
        return [
            ['id' => 1, 'name' => 'Striped Tee', 'brand' => 'Demo Brand', 'category' => 'top', 'color' => 'navy', 'style' => 'casual'],
            ['id' => 2, 'name' => 'Linen Shirt', 'brand' => 'Demo Brand', 'category' => 'top', 'color' => 'beige', 'style' => 'semi-formal'],
            ['id' => 3, 'name' => 'Knit Polo', 'brand' => 'Demo Brand', 'category' => 'top', 'color' => 'green', 'style' => 'casual'],
        ];
    }

    /** Membuat seluruh isi database mock + berkas gambarnya. */
    public static function build(): array
    {
        if (!extension_loaded('gd')) {
            throw new FashionException(
                'The mock data needs the PHP GD extension (it draws the sample clothing images). Enable extension=gd in php.ini and restart the server.',
                500
            );
        }
        MockDb::ensureDirs();
        $now = date('c');

        $wardrobe = [];
        foreach (self::wardrobeMeta() as $m) {
            $file = 'seed-w' . $m['id'] . '.png';
            self::drawGarment($m['category'], $m['color'], MockDb::filesDir() . '/' . $file);
            $wardrobe[] = $m + ['image' => $file, 'createdAt' => $now];
        }

        $catalog = [];
        foreach (self::catalogMeta() as $m) {
            $file = 'seed-c' . $m['id'] . '.png';
            self::drawGarment($m['category'], $m['color'], MockDb::filesDir() . '/' . $file);
            $catalog[] = $m + ['image' => $file, 'productUrl' => null];
        }

        return ['wardrobe' => $wardrobe, 'catalog' => $catalog, 'outfits' => [], 'tryOnResults' => [], 'nextId' => 100];
    }

    public static function drawGarment(string $category, string $color, string $path): void
    {
        $im = imagecreatetruecolor(200, 220);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127)); // latar transparan

        [$r, $g, $b] = sscanf(ltrim(FashionOptions::hex($color), '#'), '%2x%2x%2x');
        $fill = imagecolorallocate($im, $r, $g, $b);
        $line = imagecolorallocate($im, 51, 51, 51);

        if ($category === 'accessory') {
            imagefilledellipse($im, 100, 90, 100, 100, $fill);
            imageellipse($im, 100, 90, 100, 100, $line);
        } else {
            $points = self::SHAPES[$category] ?? self::SHAPES['top'];
            imagefilledpolygon($im, $points, $fill); // bentuk 3 argumen: PHP 8.1+
            imagepolygon($im, $points, $line);
        }
        imagepng($im, $path);
    }
}
