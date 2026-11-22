<?php

namespace App\Services\Fashion\Mock;

/** Outfit versi MOCK. Database menyimpan id item; saat dibaca, id diganti objek item. */
class MockOutfits
{
    private function resolve(array $db, array $outfit): array
    {
        $items = [];
        foreach ($outfit['itemIds'] as $id) {
            $index = MockDb::indexOf($db['wardrobe'], $id);
            if ($index !== null) {
                $items[] = MockWardrobe::present($db['wardrobe'][$index]);
            }
        }
        return ['id' => $outfit['id'], 'name' => $outfit['name'], 'createdAt' => $outfit['createdAt'], 'items' => $items];
    }

    public function listOutfits(): array
    {
        $db = MockDb::read();
        $list = [];
        foreach (array_reverse($db['outfits']) as $outfit) {
            $list[] = $this->resolve($db, $outfit);
        }
        return $list;
    }

    public function createOutfit(string $name, array $itemIds): array
    {
        $db = MockDb::read();
        $outfit = [
            'id'        => MockDb::nextId($db),
            'name'      => $name,
            'itemIds'   => array_values($itemIds),
            'createdAt' => date('c'),
        ];
        $db['outfits'][] = $outfit;
        MockDb::write($db);
        return $this->resolve($db, $outfit);
    }

    public function deleteOutfit($id): void
    {
        $db = MockDb::read();
        $db['outfits'] = array_values(array_filter($db['outfits'], fn ($o) => (string) $o['id'] !== (string) $id));
        MockDb::write($db);
    }
}
