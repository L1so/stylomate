// "Garment" = pakaian yang akan dicoba di Virtual Try-On. Bisa dari wardrobe ATAU katalog
// (PRD 10.6: tiap hasil merujuk tepat satu sumber), maka ada field `source`.
export const toGarment = (source, item) => ({
  source, // 'wardrobe' | 'catalog'
  id: item.id,
  name: item.name,
  category: item.category,
  imageUrl: item.imageUrl,
})
