// Kotak pesan: loading / info (tone="info") atau error (tone="error"). Error memakai role="alert".
export default function Notice({ tone = 'info', children, className = '' }) {
  const isError = tone === 'error'
  return (
    <div
      role={isError ? 'alert' : 'status'}
      className={`border px-3 py-2 text-sm ${isError ? 'border-red-700 bg-red-50 text-red-900' : 'border-black bg-[#f2f2f2]'} ${className}`}
    >
      {children}
    </div>
  )
}
