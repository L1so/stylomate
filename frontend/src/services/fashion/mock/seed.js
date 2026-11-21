// Data awal untuk mode mock. Gambar pakaian digambar sebagai SVG (tanpa file gambar).
import { COLORS } from '../../../features/fashion/constants.js'

const SHAPES = {
  top: 'M60 30 L85 20 Q100 38 115 20 L140 30 L180 70 L155 95 L140 82 L140 190 L60 190 L60 82 L45 95 L20 70 Z',
  bottom: 'M55 20 H145 L152 200 H108 L100 80 L92 200 H48 Z',
  dress: 'M80 20 H120 L128 70 L170 205 H30 L72 70 Z',
  outerwear: 'M55 25 L85 18 L100 40 L115 18 L145 25 L185 75 L160 100 L145 88 L145 200 L55 200 L55 88 L40 100 L15 75 Z',
  shoes: 'M20 140 Q20 110 60 110 L90 140 L170 150 Q185 152 185 170 V180 H20 Z',
  accessory: 'M100 40 a50 50 0 1 0 0.1 0 Z',
}

export function garmentImage(category, color) {
  const hex = COLORS.find((c) => c.value === color)?.hex ?? '#9e9e9e'
  const path = SHAPES[category] ?? SHAPES.top
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="200" height="220" viewBox="0 0 200 220"><path d="${path}" fill="${hex}" stroke="#333" stroke-width="3" stroke-linejoin="round"/></svg>`
  return `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`
}

// Sama dengan SEED di backend-fashion/test_recommendation.py (dipakai uji kesamaan aturan).
export const SEED_WARDROBE_META = [
  { id: 1, name: 'White Tee', category: 'top', color: 'white', style: 'casual' },
  { id: 2, name: 'Blue Oxford Shirt', category: 'top', color: 'blue', style: 'semi-formal' },
  { id: 3, name: 'White Dress Shirt', category: 'top', color: 'white', style: 'formal' },
  { id: 4, name: 'Grey Hoodie', category: 'top', color: 'gray', style: 'casual' },
  { id: 5, name: 'Black Sports Jersey', category: 'top', color: 'black', style: 'sporty' },
  { id: 6, name: 'Blue Jeans', category: 'bottom', color: 'blue', style: 'casual' },
  { id: 7, name: 'Black Dress Pants', category: 'bottom', color: 'black', style: 'formal' },
  { id: 8, name: 'Beige Chinos', category: 'bottom', color: 'beige', style: 'semi-formal' },
  { id: 9, name: 'Navy Track Pants', category: 'bottom', color: 'navy', style: 'sporty' },
  { id: 10, name: 'Red Dress', category: 'dress', color: 'red', style: 'semi-formal' },
  { id: 11, name: 'White Sneakers', category: 'shoes', color: 'white', style: 'casual' },
]

export function seedWardrobe() {
  const now = new Date().toISOString()
  return SEED_WARDROBE_META.map((m) => ({ ...m, imageUrl: garmentImage(m.category, m.color), createdAt: now }))
}

// Katalog contoh. Bukan produk/brand mitra sungguhan (PRD 6.3: jangan klaim kemitraan).
export function seedCatalog() {
  return [
    { id: 1, name: 'Striped Tee', brand: 'Demo Brand', category: 'top', color: 'navy', style: 'casual' },
    { id: 2, name: 'Linen Shirt', brand: 'Demo Brand', category: 'top', color: 'beige', style: 'semi-formal' },
    { id: 3, name: 'Knit Polo', brand: 'Demo Brand', category: 'top', color: 'green', style: 'casual' },
  ].map((m) => ({ ...m, imageUrl: garmentImage(m.category, m.color), productUrl: null }))
}
