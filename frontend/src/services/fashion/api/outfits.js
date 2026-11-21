// Service outfit versi API ASLI (PRD 11.4).
import { request } from '../http.js'
import { normalizeItem } from './wardrobe.js'

// Asumsi respons: { id, name, created_at, items: [ ...item wardrobe ] }
function normalizeOutfit(raw) {
  return { id: raw.id, name: raw.name, createdAt: raw.created_at ?? null, items: (raw.items ?? []).map(normalizeItem) }
}

export async function listOutfits() {
  const data = await request('/api/outfits')
  return (Array.isArray(data) ? data : data?.items ?? []).map(normalizeOutfit)
}
// Asumsi body: { name, wardrobe_item_ids: [id, id] }
export async function createOutfit({ name, itemIds }) {
  return normalizeOutfit(await request('/api/outfits', { method: 'POST', json: { name, wardrobe_item_ids: itemIds } }))
}
export async function deleteOutfit(id) {
  await request(`/api/outfits/${id}`, { method: 'DELETE' })
}
