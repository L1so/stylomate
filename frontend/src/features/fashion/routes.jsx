import { Route } from 'react-router-dom'
import WardrobePage from './pages/WardrobePage.jsx'
import ItemFormPage from './pages/ItemFormPage.jsx'
import ItemDetailPage from './pages/ItemDetailPage.jsx'
import MixMatchPage from './pages/MixMatchPage.jsx'
import TryOnPage from './pages/TryOnPage.jsx'

// Semua route milik anggota 4. Tempel `{fashionRoutes}` di dalam <Route element={<MainLayout/>}> milik tim.
// Untuk halaman yang butuh login, bungkus dengan ProtectedRoute buatan anggota 2.
export const fashionRoutes = (
  <>
    <Route path="wardrobe" element={<WardrobePage />} />
    <Route path="wardrobe/new" element={<ItemFormPage />} />
    <Route path="wardrobe/:id" element={<ItemDetailPage />} />
    <Route path="wardrobe/:id/edit" element={<ItemFormPage />} />
    <Route path="mix-match" element={<MixMatchPage />} />
    <Route path="try-on" element={<TryOnPage />} />
  </>
)
