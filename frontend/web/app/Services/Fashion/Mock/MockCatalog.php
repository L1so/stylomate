<?php

namespace App\Services\Fashion\Mock;

use App\Services\Fashion\FashionException;

/** Katalog versi MOCK (3 item contoh). */
class MockCatalog
{
    public static function present(array $row): array
    {
        $row['imageUrl'] = MockDb::url($row['image']);
        return $row;
    }

    public function listItems(): array
    {
        return array_map([self::class, 'present'], MockDb::read()['catalog']);
    }

    public function getItem($id): array
    {
        $db = MockDb::read();
        $index = MockDb::indexOf($db['catalog'], $id);
        if ($index === null) {
            throw new FashionException('Catalog item not found.', 404);
        }
        return self::present($db['catalog'][$index]);
    }
}
