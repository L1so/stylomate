// Fungsi bantu gambar: validasi (PRD NFR-03: format, ukuran, isi) dan pengecilan untuk mock.
import { IMAGE_RULES } from '../constants.js'

export function loadImage(src) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    if (!/^(data:|blob:)/.test(src)) img.crossOrigin = 'anonymous' // agar canvas tidak "tainted" bila server mengizinkan CORS
    img.onload = () => resolve(img)
    img.onerror = () => reject(new Error('The image could not be loaded.'))
    img.src = src
  })
}

/** Return teks error (string) bila file tidak valid, atau null bila valid. */
export async function checkImageFile(file) {
  if (!IMAGE_RULES.types.includes(file.type)) return 'Use a JPEG or PNG image.'
  if (file.size > IMAGE_RULES.maxBytes) return 'The image is larger than 10 MB. Choose a smaller one.'
  const url = URL.createObjectURL(file)
  try {
    await loadImage(url) // cek "isi": file yang hanya diganti ekstensinya akan gagal dibuka
    return null
  } catch {
    return 'This file is not a readable image. Choose another one.'
  } finally {
    URL.revokeObjectURL(url)
  }
}

/** Dipakai mode mock saja: perkecil gambar jadi data URL JPEG agar muat di localStorage. */
export async function fileToDownscaledDataUrl(file, maxSize = 800) {
  const url = URL.createObjectURL(file)
  try {
    const img = await loadImage(url)
    const scale = Math.min(1, maxSize / Math.max(img.naturalWidth, img.naturalHeight))
    const canvas = document.createElement('canvas')
    canvas.width = Math.round(img.naturalWidth * scale)
    canvas.height = Math.round(img.naturalHeight * scale)
    canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height)
    return canvas.toDataURL('image/jpeg', 0.85)
  } finally {
    URL.revokeObjectURL(url)
  }
}
