// Katalog (milik anggota 2/3). Dipakai di sini hanya untuk memilih pakaian di halaman Try-On.
import { request, resolveImageUrl } from '../http.js'

export function normalizeCatalogItem(raw) {
  return {
    id: raw.id,
    name: raw.name,
    brand: raw.brand ?? null,
    category: raw.category,
    color: raw.color,
    style: raw.style,
    imageUrl: resolveImageUrl(raw.image_path ?? raw.image_url),
    productUrl: raw.product_url ?? null,
  }
}
export async function listItems() {
  const data = await request('/api/catalog')
  return (Array.isArray(data) ? data : data?.items ?? []).map(normalizeCatalogItem)
}
export async function getItem(id) {
  return normalizeCatalogItem(await request(`/api/catalog/${id}`))
}
