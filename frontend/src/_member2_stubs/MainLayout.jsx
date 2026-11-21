// STUB sementara milik anggota 2 (navbar + footer sesuai wireframe). Hapus saat digabung.
import { Link, NavLink, Outlet } from 'react-router-dom'
import { MOCK_STATUS } from '../services/fashion/index.js'

const links = [
  { to: '/catalog', label: 'Catalog' },
  { to: '/wardrobe', label: 'Wardrobe' },
  { to: '/mix-match', label: 'Mix & Match' },
  { to: '/try-on', label: 'Try on' },
]

function Navbar() {
  return (
    <header className="flex h-[70px] items-center justify-between bg-[#616161] px-6 text-[15px] text-white lg:px-[30px]">
      <Link to="/" className="border border-[#444] bg-[#e0e0e0] px-6 py-1 text-sm font-bold text-black">StyloMate</Link>
      <nav aria-label="Main" className="flex items-center gap-6 lg:gap-[44px]">
        {links.map((l) => (
          <NavLink key={l.to} to={l.to} className={({ isActive }) => (isActive ? 'font-bold' : '')}>
            {l.label}
          </NavLink>
        ))}
        <Link to="/profile" aria-label="Profile" className="relative block h-[30px] w-[30px] border border-[#444] bg-[#e0e0e0]">
          <svg className="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <line x1="0" y1="0" x2="100" y2="100" stroke="#444" strokeWidth="1" vectorEffect="non-scaling-stroke" />
            <line x1="100" y1="0" x2="0" y2="100" stroke="#444" strokeWidth="1" vectorEffect="non-scaling-stroke" />
          </svg>
        </Link>
      </nav>
    </header>
  )
}

function Footer() {
  return (
    <footer className="flex h-[93px] items-center justify-center bg-[#d4d4d4] px-4 text-center text-xs">
      © 2026 - StyloMate, Inc. All rights reserved. Address Address
    </footer>
  )
}

export default function MainLayout() {
  const mocks = Object.entries(MOCK_STATUS).filter(([, on]) => on).map(([k]) => k)
  return (
    <div className="flex min-h-screen flex-col">
      <Navbar />
      {/* Penanda dev: hilang otomatis bila semua mock dimatikan */}
      {mocks.length > 0 && (
        <p className="bg-[#fff3b0] px-4 py-1 text-center text-xs">Mock mode: {mocks.join(', ')}</p>
      )}
      <main className="flex-1"><Outlet /></main>
      <Footer />
    </div>
  )
}
