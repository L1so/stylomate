import { readDb, delay } from './db.js'
import { ApiError } from '../errors.js'

export async function listItems() {
  await delay(250)
  return readDb().catalog
}
export async function getItem(id) {
  await delay(150)
  const item = readDb().catalog.find((i) => String(i.id) === String(id))
  if (!item) throw new ApiError('Catalog item not found.', 404)
  return item
}
