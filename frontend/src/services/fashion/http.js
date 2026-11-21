// Klien HTTP kecil untuk Main Backend. Kalau anggota 2 sudah punya klien (axios, dsb.),
// cukup ganti isi file ini; service lain hanya memakai `request`, `resolveImageUrl`,
// dan `fetchImageAsObjectUrl`.
import { ApiError } from './errors.js'

export const API_BASE_URL = (import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000').replace(/\/$/, '')

// Kunci localStorage tempat token JWT disimpan. SEPAKATI nama ini dengan anggota 2 (halaman Login).
export const TOKEN_KEY = 'access_token'
const getToken = () => localStorage.getItem(TOKEN_KEY)
const authHeaders = () => (getToken() ? { Authorization: `Bearer ${getToken()}` } : {})

// Path gambar dari backend biasanya relatif ("/uploads/abc.jpg"); jadikan URL penuh.
export function resolveImageUrl(path) {
  if (!path) return null
  if (/^(https?:|data:|blob:)/i.test(path)) return path
  return `${API_BASE_URL}${path.startsWith('/') ? '' : '/'}${path}`
}

async function errorFrom(res) {
  let detail = null
  try { detail = await res.json() } catch { /* respons bukan JSON */ }
  // FastAPI: {"detail": "teks"} atau {"detail": [{"msg": "..."}]} untuk error validasi (422)
  const message =
    typeof detail?.detail === 'string' ? detail.detail
    : Array.isArray(detail?.detail) ? detail.detail.map((d) => d.msg).join('; ')
    : `Request failed (${res.status})`
  return new ApiError(message, res.status, detail)
}

/**
 * request('/api/wardrobe')                                   -> JSON
 * request('/api/x', { method:'POST', json:{...} })           -> kirim JSON
 * request('/api/x', { method:'POST', formData })             -> kirim file (multipart)
 * Jika respons bertipe image/*, hasilnya Blob.
 */
export async function request(path, { method = 'GET', json, formData, params, timeoutMs = 30000 } = {}) {
  const url = new URL(API_BASE_URL + path)
  if (params) Object.entries(params).forEach(([k, v]) => v != null && url.searchParams.set(k, v))

  const headers = authHeaders()
  let body
  if (formData) body = formData // JANGAN set Content-Type manual: browser menambah boundary sendiri
  else if (json !== undefined) { headers['Content-Type'] = 'application/json'; body = JSON.stringify(json) }

  const controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), timeoutMs)
  try {
    const res = await fetch(url, { method, headers, body, signal: controller.signal })
    if (!res.ok) throw await errorFrom(res)
    if (res.status === 204) return null
    const type = res.headers.get('content-type') || ''
    return type.startsWith('image/') ? await res.blob() : await res.json()
  } catch (err) {
    if (err instanceof ApiError) throw err
    if (err.name === 'AbortError') throw new ApiError('The request timed out. Please try again.')
    throw new ApiError('Cannot reach the server. Check that the backend is running, CORS allows this origin, and VITE_API_BASE_URL is correct.')
  } finally {
    clearTimeout(timer)
  }
}

// <img src> tidak bisa mengirim header Authorization. Untuk gambar privat, ambil lewat fetch + token
// lalu tampilkan sebagai blob URL.
export async function fetchImageAsObjectUrl(path) {
  const res = await fetch(resolveImageUrl(path), { headers: authHeaders() })
  if (!res.ok) throw await errorFrom(res)
  return URL.createObjectURL(await res.blob())
}
