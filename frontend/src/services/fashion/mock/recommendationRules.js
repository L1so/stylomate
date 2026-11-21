// Aturan rekomendasi (rule-based), JavaScript murni tanpa import.
// KEMBARAN dari backend-fashion/recommendation.py. Ubah keduanya bersamaan.
// Cek kesamaan:  npm run check:rules

export const NEUTRAL_COLORS = ['black', 'white', 'gray', 'beige', 'navy', 'brown']
const CLASHES = [['red', 'green'], ['pink', 'orange'], ['red', 'pink'], ['purple', 'orange']]
const ADJACENT_STYLES = {
  casual: ['semi-formal', 'sporty'],
  'semi-formal': ['casual', 'formal'],
  formal: ['semi-formal'],
  sporty: ['casual'],
}
export const SUPPORTED_STYLES = Object.keys(ADJACENT_STYLES)

const styleScore = (itemStyle, wanted) => (itemStyle === wanted ? 2 : ADJACENT_STYLES[wanted].includes(itemStyle) ? 1 : 0)
const isClash = (a, b) => CLASHES.some(([x, y]) => (a === x && b === y) || (a === y && b === x))
const isNeutral = (c) => NEUTRAL_COLORS.includes(c)

function colorScore(a, b) {
  if (isClash(a, b)) return null // null = bentrok, kombinasi dibuang
  if (isNeutral(a) || isNeutral(b)) return 2
  return a === b ? 1 : 0
}
function colorNote(a, b) {
  if (isNeutral(a) || isNeutral(b)) return 'neutral colors are easy to pair'
  return a === b ? 'matching colors' : 'balanced color contrast'
}

/** items: pakaian milik pengguna. Return: { outfits:[{items:[top,bottom],score,reason}], message } */
export function recommendOutfits(items, style, limit = 3) {
  if (!SUPPORTED_STYLES.includes(style)) throw new Error(`Unsupported style: ${style}`)

  const tops = items.filter((i) => i.category === 'top')
  const bottoms = items.filter((i) => i.category === 'bottom')
  if (!tops.length || !bottoms.length) {
    return { outfits: [], message: 'Your wardrobe needs at least one top and one bottom to get suggestions.' }
  }

  const pairs = []
  for (const top of tops) {
    const ts = styleScore(top.style, style)
    if (!ts) continue
    for (const bottom of bottoms) {
      const bs = styleScore(bottom.style, style)
      if (!bs) continue
      const cs = colorScore(top.color, bottom.color)
      if (cs === null) continue
      pairs.push({ top, bottom, score: ts + bs + cs + (top.style === bottom.style ? 1 : 0) })
    }
  }
  if (!pairs.length) {
    return { outfits: [], message: `No matching ${style} combination yet. Try another style or add more items.` }
  }

  // Urutan deterministik: skor tertinggi, lalu id kecil.
  pairs.sort((a, b) => b.score - a.score || a.top.id - b.top.id || a.bottom.id - b.bottom.id)

  return {
    outfits: pairs.slice(0, limit).map(({ top, bottom, score }) => {
      const exact = top.style === style && bottom.style === style
      const styleNote = exact ? `matches ${style} style` : `close to ${style} style`
      return { items: [top, bottom], score, reason: `${top.name} + ${bottom.name}: ${colorNote(top.color, bottom.color)}, ${styleNote}.` }
    }),
    message: null,
  }
}
