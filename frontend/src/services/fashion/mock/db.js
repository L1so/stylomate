// "Database" palsu di localStorage browser. Semua mock memakai ini.
// Data bertahan saat refresh. Reset: di console browser ketik  stylomateResetMock()
import { seedWardrobe, seedCatalog } from './seed.js'

const KEY = 'stylomate_mock_db'

function load() {
  try {
    const raw = localStorage.getItem(KEY)
    if (raw) return JSON.parse(raw)
  } catch { /* data rusak -> buat baru */ }
  const fresh = { wardrobe: seedWardrobe(), catalog: seedCatalog(), outfits: [], tryOnResults: [], nextId: 100 }
  writeDb(fresh)
  return fresh
}

export const readDb = load

export function writeDb(db) {
  try {
    localStorage.setItem(KEY, JSON.stringify(db))
  } catch {
    throw new Error('Mock storage is full. Delete some items or run stylomateResetMock() in the console.')
  }
}

export function nextId(db) {
  return db.nextId++ // panggil lalu writeDb(db) supaya counter tersimpan
}

export const delay = (ms = 400) => new Promise((resolve) => setTimeout(resolve, ms))

export function resetMockDb() {
  localStorage.removeItem(KEY)
}
if (typeof window !== 'undefined') window.stylomateResetMock = () => { resetMockDb(); location.reload() }
