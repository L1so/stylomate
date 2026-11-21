import { readDb, delay } from './db.js'
import { recommendOutfits } from './recommendationRules.js'

export async function getRecommendations({ style }) {
  await delay(500)
  return recommendOutfits(readDb().wardrobe, style)
}
