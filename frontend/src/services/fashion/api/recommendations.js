// Smart Outfit Curation versi API ASLI (PRD 11.4: GET /api/recommendations?style=casual).
// Asumsi respons: { outfits: [ { items:[top,bottom], score, reason } ], message: string|null }
// (cocok dengan backend-fashion/recommendation.py -> recommend_outfits)
import { request } from '../http.js'
import { normalizeItem } from './wardrobe.js'

export async function getRecommendations({ style }) {
  const data = await request('/api/recommendations', { params: { style } })
  return {
    outfits: (data.outfits ?? []).map((o) => ({
      items: o.items.map(normalizeItem),
      score: o.score ?? null,
      reason: o.reason ?? '',
    })),
    message: data.message ?? null,
  }
}
