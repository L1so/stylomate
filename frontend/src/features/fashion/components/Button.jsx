import { Link } from 'react-router-dom'

// Tombol abu-abu ala wireframe. `buttonClass` dipakai juga oleh <Link> yang tampil seperti tombol.
export const buttonClass =
  'inline-flex h-10 items-center justify-center rounded-md bg-[#616161] px-4 text-[15px] font-bold text-white ' +
  'transition-colors hover:bg-[#4a4a4a] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 ' +
  'focus-visible:outline-black disabled:cursor-not-allowed disabled:opacity-40'

export default function Button({ children, className = '', ...props }) {
  return (
    <button type="button" className={`${buttonClass} ${className}`} {...props}>
      {children}
    </button>
  )
}

export function ButtonLink({ to, children, className = '' }) {
  return (
    <Link to={to} className={`${buttonClass} ${className}`}>
      {children}
    </Link>
  )
}
