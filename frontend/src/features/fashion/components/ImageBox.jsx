// Kotak gambar ala wireframe: abu-abu, border hitam, tanda X bila belum ada gambar.
// Ukuran diatur dari luar lewat className (mis. "aspect-square w-full").
export default function ImageBox({ src, alt = '', fit = 'cover', plain = false, className = '', children }) {
  return (
    <div className={`relative overflow-hidden bg-[#e0e0e0] ${plain ? '' : 'border border-black'} ${className}`}>
      {src ? (
        <img
          src={src}
          alt={alt}
          className={`absolute inset-0 h-full w-full ${fit === 'contain' ? 'object-contain' : 'object-cover'}`}
        />
      ) : (
        <svg className="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
          <line x1="0" y1="0" x2="100" y2="100" stroke="black" strokeWidth="1" vectorEffect="non-scaling-stroke" />
          <line x1="100" y1="0" x2="0" y2="100" stroke="black" strokeWidth="1" vectorEffect="non-scaling-stroke" />
        </svg>
      )}
      {children}
    </div>
  )
}
