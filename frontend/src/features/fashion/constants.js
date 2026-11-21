// Nilai-nilai tetap untuk fitur fashion. SEPAKATI daftar ini dengan anggota 1 & 3:
// - category: harus bisa dipetakan ke kategori yang didukung model AI (mis. atasan/bawahan).
// - color/style: dipakai mesin rekomendasi (rule-based) di backend.
export const CATEGORIES = [
  { value: 'top', label: 'Top', plural: 'Tops' },
  { value: 'bottom', label: 'Bottom', plural: 'Bottoms' },
  { value: 'dress', label: 'Dress', plural: 'Dresses' },
  { value: 'outerwear', label: 'Outerwear', plural: 'Outerwear' },
  { value: 'shoes', label: 'Shoes', plural: 'Shoes' },
  { value: 'accessory', label: 'Accessory', plural: 'Accessories' },
]

// PRD 6.6: casual, formal, semi-formal, sporty
export const STYLES = [
  { value: 'casual', label: 'Casual' },
  { value: 'semi-formal', label: 'Semi-formal' },
  { value: 'formal', label: 'Formal' },
  { value: 'sporty', label: 'Sporty' },
]

export const COLORS = [
  { value: 'black', label: 'Black', hex: '#222222' },
  { value: 'white', label: 'White', hex: '#f7f7f7' },
  { value: 'gray', label: 'Gray', hex: '#9e9e9e' },
  { value: 'beige', label: 'Beige', hex: '#d9c7a6' },
  { value: 'navy', label: 'Navy', hex: '#1f2a4d' },
  { value: 'brown', label: 'Brown', hex: '#7a5230' },
  { value: 'blue', label: 'Blue', hex: '#3b6fd4' },
  { value: 'red', label: 'Red', hex: '#c0392b' },
  { value: 'green', label: 'Green', hex: '#3f8f5a' },
  { value: 'yellow', label: 'Yellow', hex: '#e6c229' },
  { value: 'pink', label: 'Pink', hex: '#e58fb0' },
  { value: 'purple', label: 'Purple', hex: '#7b4fb0' },
  { value: 'orange', label: 'Orange', hex: '#e67e22' },
]

// PRD NFR-03 / catatan upload: JPEG atau PNG, maksimal 10 MB per gambar.
export const IMAGE_RULES = {
  types: ['image/jpeg', 'image/png'],
  maxBytes: 10 * 1024 * 1024,
}

export const labelOf = (list, value) => list.find((x) => x.value === value)?.label ?? value
