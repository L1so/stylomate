// Virtual Try-On versi API ASLI. INI FILE UTAMA UNTUK INTEGRASI AI.
// Frontend TIDAK memanggil AI Service langsung; ia memanggil Main Backend (PRD bagian 11),
// yang meneruskan ke AI Service anggota 1 (POST /generate: person_image, garment_image, category).
import { request, resolveImageUrl, fetchImageAsObjectUrl } from '../http.js'
import { ApiError } from '../errors.js'

/**
 * @param {{ personFile: File, garment: {source:'wardrobe'|'catalog', id:number|string, category:string} }} args
 * @returns {Promise<{ id: number|string|null, imageUrl: string }>}
 *
 * ASUMSI request (multipart): person_image (file) + wardrobe_item_id ATAU catalog_item_id + category.
 * ASUMSI respons: JSON { id, image_path } ATAU langsung gambar (image/png|jpeg).
 * Belum ada di PRD -> sepakati dengan anggota 3, lalu ubah HANYA fungsi ini.
 */
export async function generateTryOn({ personFile, garment }) {
  const fd = new FormData()
  fd.append('person_image', personFile)
  fd.append(garment.source === 'wardrobe' ? 'wardrobe_item_id' : 'catalog_item_id', String(garment.id))
  fd.append('category', garment.category)

  // Inferensi AI bisa lama -> timeout 3 menit.
  const data = await request('/api/try-on/generate', { method: 'POST', formData: fd, timeoutMs: 180000 })

  if (data instanceof Blob) return { id: null, imageUrl: URL.createObjectURL(data) }

  const path = data.image_path ?? data.image_url ?? data.result_image_path
  if (!path) throw new ApiError('The server did not return a result image.')
  return { id: data.id ?? data.result_id ?? null, imageUrl: await fetchImageAsObjectUrl(path) }
}

// PRD 11.5: POST /api/try-on/save. Asumsi body: { result_id }.
export async function saveResult({ id }) {
  if (id == null) {
    throw new ApiError('The server did not return a result id, so this result cannot be saved. Ask the backend to return { id, image_path }.')
  }
  return request('/api/try-on/save', { method: 'POST', json: { result_id: id } })
}

// PRD 11.5: GET /api/try-on/history
export async function listHistory() {
  const data = await request('/api/try-on/history')
  return (Array.isArray(data) ? data : data?.items ?? []).map((r) => ({
    id: r.id,
    imageUrl: resolveImageUrl(r.result_image_path ?? r.image_path),
    createdAt: r.created_at ?? null,
  }))
}
