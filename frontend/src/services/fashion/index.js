// TITIK SAKLAR mock <-> API asli. Halaman hanya mengimpor dari file ini, tidak pernah dari api/ atau mock/.
// Atur lewat file .env (lihat .env.example). Mengubah .env butuh restart `npm run dev`.
import * as apiWardrobe from './api/wardrobe.js'
import * as apiOutfits from './api/outfits.js'
import * as apiRecommendations from './api/recommendations.js'
import * as apiCatalog from './api/catalog.js'
import * as apiTryOn from './api/tryOn.js'
import * as mockWardrobe from './mock/wardrobe.js'
import * as mockOutfits from './mock/outfits.js'
import * as mockRecommendations from './mock/recommendations.js'
import * as mockCatalog from './mock/catalog.js'
import * as mockTryOn from './mock/tryOn.js'

const isOn = (value) => value !== 'false' // default: mock menyala

const mockData = isOn(import.meta.env.VITE_MOCK_DATA)
const mockCatalogOn = isOn(import.meta.env.VITE_MOCK_CATALOG)
const mockTryOnOn = isOn(import.meta.env.VITE_MOCK_TRYON)

// Wardrobe + outfit + rekomendasi satu kelompok: mock-nya saling membaca data yang sama.
export const wardrobeService = mockData ? mockWardrobe : apiWardrobe
export const outfitService = mockData ? mockOutfits : apiOutfits
export const recommendationService = mockData ? mockRecommendations : apiRecommendations
export const catalogService = mockCatalogOn ? mockCatalog : apiCatalog
export const tryOnService = mockTryOnOn ? mockTryOn : apiTryOn

export const MOCK_STATUS = { data: mockData, catalog: mockCatalogOn, tryOn: mockTryOnOn }
