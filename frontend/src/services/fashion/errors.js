// Error standar untuk semua service (mock maupun asli) supaya halaman cukup baca `err.message`.
export class ApiError extends Error {
  constructor(message, status = 0, detail = null) {
    super(message)
    this.name = 'ApiError'
    this.status = status // kode HTTP; 0 = gagal sebelum ada respons (server mati, timeout)
    this.detail = detail
  }
}
