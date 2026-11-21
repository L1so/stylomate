// Service wardrobe versi MOCK (localStorage). Fungsi & bentuk datanya sama dengan api/wardrobe.js.
import { readDb, writeDb, nextId, delay } from './db.js'
import { ApiError } from '../errors.js'
import { garmentImage } from './seed.js'
import { fileToDownscaledDataUrl } from '../../../features/fashion/utils/image.js'

export async function listItems() {
  await delay()
  return [...readDb().wardrobe].reverse() // item terbaru di atas
}

export async function getItem(id) {
  await delay(200)
  const item = readDb().wardrobe.find((i) => String(i.id) === String(id))
  if (!item) throw new ApiError('Item not found.', 404)
  return item
}

export async function createItem({ name, category, color, style, imageFile }) {
  await delay()
  // Perkecil gambar supaya muat di localStorage (batas ~5 MB).
  const imageUrl = imageFile ? await fileToDownscaledDataUrl(imageFile) : garmentImage(category, color)
  const db = readDb()
  const item = { id: nextId(db), name, category, color, style, imageUrl, createdAt: new Date().toISOString() }
  db.wardrobe.push(item)
  writeDb(db)
  return item
}

export async function updateItem(id, { name, category, color, style, imageFile }) {
  await delay()
  const db = readDb()
  const item = db.wardrobe.find((i) => String(i.id) === String(id))
  if (!item) throw new ApiError('Item not found.', 404)
  Object.assign(item, { name, category, color, style })
  if (imageFile) item.imageUrl = await fileToDownscaledDataUrl(imageFile)
  writeDb(db)
  return item
}

export async function deleteItem(id) {
  await delay(250)
  const db = readDb()
  db.wardrobe = db.wardrobe.filter((i) => String(i.id) !== String(id))
  // Hapus juga dari outfit tersimpan; outfit yang kosong ikut dihapus.
  db.outfits.forEach((o) => { o.itemIds = o.itemIds.filter((x) => String(x) !== String(id)) })
  db.outfits = db.outfits.filter((o) => o.itemIds.length > 0)
  writeDb(db)
}
