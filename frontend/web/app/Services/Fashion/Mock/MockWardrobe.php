<?php

namespace App\Services\Fashion\Mock;

use App\Services\Fashion\FashionException;
use Illuminate\Http\UploadedFile;

/** Wardrobe versi MOCK. Method & bentuk hasil sama dengan Api/ApiWardrobe. */
class MockWardrobe
{
    /** Tambahkan imageUrl (URL tayang) ke baris database. */
    public static function present(array $row): array
    {
        $row['imageUrl'] = MockDb::url($row['image']);
        return $row;
    }

    public function listItems(): array
    {
        $db = MockDb::read();
        return array_map([self::class, 'present'], array_reverse($db['wardrobe'])); // terbaru di atas
    }

    public function getItem($id): array
    {
        $db = MockDb::read();
        $index = MockDb::indexOf($db['wardrobe'], $id);
        if ($index === null) {
            throw new FashionException('Item not found.', 404);
        }
        return self::present($db['wardrobe'][$index]);
    }

    public function createItem(array $v, ?UploadedFile $image): array
    {
        if ($image === null) {
            throw new FashionException('Choose a photo of the item.', 422);
        }
        $db = MockDb::read();
        $item = [
            'id'        => MockDb::nextId($db),
            'name'      => $v['name'],
            'category'  => $v['category'],
            'color'     => $v['color'],
            'style'     => $v['style'],
            'image'     => MockDb::storeUpload($image),
            'createdAt' => date('c'),
        ];
        $db['wardrobe'][] = $item;
        MockDb::write($db);
        return self::present($item);
    }

    public function updateItem($id, array $v, ?UploadedFile $image): array
    {
        $db = MockDb::read();
        $index = MockDb::indexOf($db['wardrobe'], $id);
        if ($index === null) {
            throw new FashionException('Item not found.', 404);
        }
        foreach (['name', 'category', 'color', 'style'] as $key) {
            $db['wardrobe'][$index][$key] = $v[$key];
        }
        if ($image !== null) {
            $db['wardrobe'][$index]['image'] = MockDb::storeUpload($image); // ganti gambar hanya bila ada file baru
        }
        MockDb::write($db);
        return self::present($db['wardrobe'][$index]);
    }

    public function deleteItem($id): void
    {
        $db = MockDb::read();
        $db['wardrobe'] = array_values(array_filter($db['wardrobe'], fn ($i) => (string) $i['id'] !== (string) $id));

        // Hapus juga dari outfit tersimpan; outfit yang jadi kosong ikut dihapus.
        foreach ($db['outfits'] as $k => $outfit) {
            $db['outfits'][$k]['itemIds'] = array_values(array_filter(
                $outfit['itemIds'],
                fn ($x) => (string) $x !== (string) $id
            ));
        }
        $db['outfits'] = array_values(array_filter($db['outfits'], fn ($o) => count($o['itemIds']) > 0));
        MockDb::write($db);
    }
}
