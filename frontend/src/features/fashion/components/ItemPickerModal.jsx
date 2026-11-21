import { useEffect, useState } from 'react'
import { wardrobeService, catalogService } from '../../../services/fashion/index.js'
import useAsync from '../hooks/useAsync.js'
import { toGarment } from '../utils/garment.js'
import ImageBox from './ImageBox.jsx'
import Notice from './Notice.jsx'

// Jendela pilih pakaian (dari Wardrobe atau Catalog) untuk slot "My Outfit" di halaman Try-On.
export default function ItemPickerModal({ onPick, onClose }) {
  const [tab, setTab] = useState('wardrobe')
  const wardrobe = useAsync(() => wardrobeService.listItems(), [])
  const catalog = useAsync(() => catalogService.listItems(), [])
  const current = tab === 'wardrobe' ? wardrobe : catalog

  useEffect(() => {
    const onKey = (e) => e.key === 'Escape' && onClose()
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [onClose])

  return (
    <div className="fixed inset-0 z-50 grid place-items-center bg-black/60 p-4" onClick={onClose}>
      <div
        role="dialog"
        aria-modal="true"
        aria-label="Choose an item"
        className="max-h-[85vh] w-full max-w-[720px] overflow-y-auto bg-white p-5"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="mb-4 flex items-center justify-between">
          <div role="tablist" className="flex gap-2">
            {['wardrobe', 'catalog'].map((t) => (
              <button
                key={t}
                role="tab"
                aria-selected={tab === t}
                onClick={() => setTab(t)}
                className={`px-3 py-1 text-sm ${tab === t ? 'bg-black font-bold text-white' : 'border border-black'}`}
              >
                {t === 'wardrobe' ? 'Wardrobe' : 'Catalog'}
              </button>
            ))}
          </div>
          <button onClick={onClose} className="border border-black px-3 py-1 text-sm" autoFocus>Close</button>
        </div>

        {current.loading && <Notice>Loading…</Notice>}
        {current.error && <Notice tone="error">{current.error}</Notice>}
        {current.data?.length === 0 && <Notice>Nothing here yet.</Notice>}

        <ul className="grid grid-cols-2 gap-4 sm:grid-cols-4">
          {(current.data ?? []).map((item) => (
            <li key={item.id}>
              <button type="button" className="block w-full text-left" onClick={() => onPick(toGarment(tab, item))}>
                <ImageBox src={item.imageUrl} alt={item.name} fit="contain" className="aspect-square w-full" />
                <span className="mt-1 block truncate text-xs">{item.name}</span>
              </button>
            </li>
          ))}
        </ul>
      </div>
    </div>
  )
}
