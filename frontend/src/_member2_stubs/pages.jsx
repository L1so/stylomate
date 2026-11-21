// STUB sementara halaman milik anggota 2 (Homepage, Catalog, Profile, Login).
// Tujuannya hanya agar alur fitur anggota 4 bisa diuji ujung-ke-ujung. Ganti dengan halaman asli.
import { useState } from 'react'
import { Link } from 'react-router-dom'
import { catalogService, outfitService, tryOnService } from '../services/fashion/index.js'
import { TOKEN_KEY } from '../services/fashion/http.js'
import useAsync from '../features/fashion/hooks/useAsync.js'
import ImageBox from '../features/fashion/components/ImageBox.jsx'
import Notice from '../features/fashion/components/Notice.jsx'
import { ButtonLink } from '../features/fashion/components/Button.jsx'

const Page = ({ title, children }) => (
  <div className="mx-auto max-w-[1000px] px-6 py-12">
    <h1 className="mb-6 text-xl font-bold">{title}</h1>
    {children}
  </div>
)

export function HomePage() {
  return (
    <Page title="StyloMate (placeholder)">
      <div className="flex flex-wrap gap-3">
        <ButtonLink to="/wardrobe" className="w-[172px]">Wardrobe</ButtonLink>
        <ButtonLink to="/mix-match" className="w-[172px]">Mix &amp; Match</ButtonLink>
        <ButtonLink to="/try-on" className="w-[172px]">Try on</ButtonLink>
      </div>
    </Page>
  )
}

export function CatalogPage() {
  const { data, loading, error } = useAsync(() => catalogService.listItems(), [])
  return (
    <Page title="Catalog (placeholder)">
      {loading && <Notice>Loading…</Notice>}
      {error && <Notice tone="error">{error}</Notice>}
      <ul className="grid grid-cols-2 gap-6 sm:grid-cols-4">
        {(data ?? []).map((item) => (
          <li key={item.id}>
            <ImageBox src={item.imageUrl} alt={item.name} fit="contain" className="aspect-square w-full" />
            <p className="mt-1 text-sm font-bold">{item.name}</p>
            {/* Beginilah catalog meneruskan pakaian ke Try-On (PRD TC-09) */}
            <Link to={`/try-on?catalog=${item.id}`} className="text-sm underline">Try On</Link>
          </li>
        ))}
      </ul>
    </Page>
  )
}

export function ProfilePage() {
  const outfits = useAsync(() => outfitService.listOutfits(), [])
  const history = useAsync(() => tryOnService.listHistory(), [])
  return (
    <Page title="Profile (placeholder)">
      <h2 className="mb-2 font-bold">Saved outfits</h2>
      {outfits.error && <Notice tone="error">{outfits.error}</Notice>}
      {outfits.data?.length === 0 && <Notice>No saved outfits yet.</Notice>}
      <ul className="mb-8 flex flex-col gap-3">
        {(outfits.data ?? []).map((o) => (
          <li key={o.id} className="flex items-center gap-3 border border-black p-2 text-sm">
            {o.items.map((i) => <ImageBox key={i.id} src={i.imageUrl} alt={i.name} fit="contain" className="h-16 w-16" />)}
            <span>{o.name}</span>
          </li>
        ))}
      </ul>
      <h2 className="mb-2 font-bold">Saved try-on results</h2>
      {history.error && <Notice tone="error">{history.error}</Notice>}
      {history.data?.length === 0 && <Notice>No saved try-on results yet.</Notice>}
      <ul className="grid grid-cols-2 gap-4 sm:grid-cols-4">
        {(history.data ?? []).map((r) => (
          <li key={r.id}><ImageBox src={r.imageUrl} alt="Saved try-on" fit="contain" className="aspect-[3/4] w-full" /></li>
        ))}
      </ul>
    </Page>
  )
}

// Login sementara: tempel token JWT dari Swagger backend (/docs) agar bisa menguji API asli
// sebelum halaman Login anggota 2 selesai.
export function LoginPage() {
  const [token, setToken] = useState(localStorage.getItem(TOKEN_KEY) ?? '')
  const [saved, setSaved] = useState(false)
  return (
    <Page title="Login (placeholder)">
      <p className="mb-3 text-sm">Untuk uji API asli: tempel access token (JWT) di sini.</p>
      <textarea className="h-28 w-full border border-black p-2 text-xs" value={token} onChange={(e) => { setToken(e.target.value); setSaved(false) }} aria-label="Access token" />
      <div className="mt-3 flex items-center gap-3">
        <button className="h-10 bg-[#616161] px-4 font-bold text-white" onClick={() => { localStorage.setItem(TOKEN_KEY, token.trim()); setSaved(true) }}>Save token</button>
        <button className="h-10 border border-black px-4" onClick={() => { localStorage.removeItem(TOKEN_KEY); setToken(''); setSaved(false) }}>Clear</button>
        {saved && <span className="text-sm">Token saved.</span>}
      </div>
    </Page>
  )
}
