// Pita hitam berisi teks putih (judul bagian di wireframe: "My Photo", "Result", dst.).
export default function Label({ children, className = 'text-center', as: Tag = 'div' }) {
  return <Tag className={`bg-black px-3 text-[15px] font-bold leading-6 text-white ${className}`}>{children}</Tag>
}
