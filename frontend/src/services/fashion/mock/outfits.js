// Service outfit versi MOCK. Outfit menyimpan id item; saat dibaca, id diganti objek item.
import { readDb, writeDb, nextId, delay } from './db.js'

const withItems = (db, outfit) => ({
  id: outfit.id,
  name: outfit.name,
  createdAt: outfit.createdAt,
  items: outfit.itemIds.map((id) => db.wardrobe.find((i) => i.id === id)).filter(Boolean),
})

export async function listOutfits() {
  await delay(250)
  const db = readDb()
  return db.outfits.map((o) => withItems(db, o)).reverse()
}

export async function createOutfit({ name, itemIds }) {
  await delay()
  const db = readDb()
  const outfit = { id: nextId(db), name, itemIds, createdAt: new Date().toISOString() }
  db.outfits.push(outfit)
  writeDb(db)
  return withItems(db, outfit)
}

export async function deleteOutfit(id) {
  await delay(200)
  const db = readDb()
  db.outfits = db.outfits.filter((o) => String(o.id) !== String(id))
  writeDb(db)
}
