import { useState } from 'react'
import { Link } from 'react-router-dom'
import { wardrobeService } from '../../../services/fashion/index.js'
import { CATEGORIES } from '../constants.js'
import useAsync from '../hooks/useAsync.js'
import ImageBox from '../components/ImageBox.jsx'
import Label from '../components/Label.jsx'
import Notice from '../components/Notice.jsx'
import { ButtonLink } from '../components/Button.jsx'

// Halaman Wardrobe (wireframe "Wardrobe.png"): sidebar kategori + judul + tombol Add Item + grid pakaian.
export default function WardrobePage() {
  const { data: items, loading, error, reload } = useAsync(() => wardrobeService.listItems(), [])
  const [category, setCategory] = useState('all')

  const all = items ?? []
  const visible = all.filter((i) => category === 'all' || i.category === category)
  const countOf = (value) => all.filter((i) => value === 'all' || i.category === value).length
  const filters = [{ value: 'all', plural: 'All items' }, ...CATEGORIES]

  return (
    <div className="mx-auto flex max-w-[1440px] flex-col gap-8 px-6 py-12 lg:flex-row lg:gap-16 lg:px-[50px] lg:py-[70px]">
      <aside aria-label="Filter by category" className="h-fit w-full shrink-0 border-[3px] border-black p-5 lg:min-h-[550px] lg:w-[240px]">
        <ul className="flex flex-col gap-3">
          {filters.map((f) => (
            <li key={f.value}>
              <button
                type="button"
                onClick={() => setCategory(f.value)}
                aria-pressed={category === f.value}
                className={`flex w-full justify-between px-3 py-2 text-left text-sm ${
                  category === f.value ? 'bg-black font-bold text-white' : 'hover:bg-[#e0e0e0]'
                }`}
              >
                <span>{f.plural}</span>
                <span>{countOf(f.value)}</span>
              </button>
            </li>
          ))}
        </ul>
      </aside>

      <section className="min-w-0 flex-1">
        <div className="mb-[46px] flex items-center justify-between gap-4">
          <Label as="h1" className="min-w-[278px] text-left">My Wardrobe</Label>
          <ButtonLink to="/wardrobe/new" className="w-[172px]">Add Item</ButtonLink>
        </div>

        {loading && <Notice>Loading your wardrobe…</Notice>}
        {error && (
          <Notice tone="error">
            {error}{' '}
            <button type="button" onClick={reload} className="underline">Try again</button>
          </Notice>
        )}
        {!loading && !error && visible.length === 0 && (
          <Notice>
            {all.length === 0
              ? 'Your wardrobe is empty. Add your first item to start mixing outfits.'
              : 'No items in this category yet.'}{' '}
            <Link to="/wardrobe/new" className="underline">Add Item</Link>
          </Notice>
        )}

        <ul className="grid grid-cols-2 gap-x-[53px] gap-y-9 md:grid-cols-3 xl:grid-cols-4">
          {visible.map((item) => (
            <li key={item.id}>
              <Link to={`/wardrobe/${item.id}`} aria-label={item.name} className="group block">
                <ImageBox src={item.imageUrl} alt={item.name} fit="contain" className="aspect-[197/222] w-full">
                  <span className="absolute inset-x-0 bottom-0 truncate bg-black/80 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">
                    {item.name}
                  </span>
                </ImageBox>
              </Link>
            </li>
          ))}
        </ul>
      </section>
    </div>
  )
}
