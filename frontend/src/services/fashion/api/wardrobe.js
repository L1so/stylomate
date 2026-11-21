// Service wardrobe versi API ASLI (PRD 11.3). Bentuk fungsi HARUS sama dengan mock/wardrobe.js.
import { request, resolveImageUrl } from '../http.js'

// Satu-satunya tempat yang tahu nama field backend (image_path, created_at).
// Kalau backend memakai nama lain, cukup ubah di sini.
export function normalizeItem(raw) {
  return {
    id: raw.id,
    name: raw.name,
    category: raw.category,
    color: raw.color,
    style: raw.style,
    imageUrl: resolveImageUrl(raw.image_path ?? raw.image_url),
    createdAt: raw.created_at ?? null,
  }
}

const toList = (data) => (Array.isArray(data) ? data : data?.items ?? [])

function toFormData({ name, category, color, style, imageFile }) {
  const fd = new FormData()
  fd.append('name', name)
  fd.append('category', category)
  fd.append('color', color)
  fd.append('style', style)
  if (imageFile) fd.append('image', imageFile) // nama field "image": sepakati dengan anggota 3
  return fd
}

export async function listItems() {
  return toList(await request('/api/wardrobe')).map(normalizeItem)
}
export async function getItem(id) {
  return normalizeItem(await request(`/api/wardrobe/${id}`))
}
export async function createItem(values) {
  return normalizeItem(await request('/api/wardrobe', { method: 'POST', formData: toFormData(values), timeoutMs: 60000 }))
}
export async function updateItem(id, values) {
  return normalizeItem(await request(`/api/wardrobe/${id}`, { method: 'PATCH', formData: toFormData(values), timeoutMs: 60000 }))
}
export async function deleteItem(id) {
  await request(`/api/wardrobe/${id}`, { method: 'DELETE' })
}
