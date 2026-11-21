import { Routes, Route, Navigate } from 'react-router-dom'
// ---- STUB anggota 2: hapus/ganti saat menggabung dengan frontend core ----
import MainLayout from './_member2_stubs/MainLayout.jsx'
import { HomePage, CatalogPage, ProfilePage, LoginPage } from './_member2_stubs/pages.jsx'
// ---- MILIK ANGGOTA 4: cukup satu baris ini yang perlu dibawa ke App.jsx tim ----
import { fashionRoutes } from './features/fashion/routes.jsx'

export default function App() {
  return (
    <Routes>
      <Route element={<MainLayout />}>
        <Route index element={<HomePage />} />
        <Route path="catalog" element={<CatalogPage />} />
        <Route path="profile" element={<ProfilePage />} />
        <Route path="login" element={<LoginPage />} />
        {fashionRoutes}
        <Route path="*" element={<Navigate to="/" replace />} />
      </Route>
    </Routes>
  )
}
