import { useEffect, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { wardrobeService, outfitService, recommendationService } from '../../../services/fashion/index.js'
import { CATEGORIES, STYLES, labelOf } from '../constants.js'
import useAsync from '../hooks/useAsync.js'
import ImageBox from '../components/ImageBox.jsx'
import Label from '../components/Label.jsx'
import Notice from '../components/Notice.jsx'
import Button from '../components/Button.jsx'

const MIXABLE = ['top', 'bottom'] // MVP (PRD 6.5): hanya atasan + bawahan
const inputClass = 'h-11 w-full border border-black bg-white px-3 text-sm'

// Halaman Mix & Match (wireframe "Mix & Match.png") + Smart Outfit Curation lewat tombol Generate Suggestion.
export default function MixMatchPage() {
  const [params] = useSearchParams()
  const { data: items, loading, error } = useAsync(() => wardrobeService.listItems(), [])

  const [selection, setSelection] = useState({ top: null, bottom: null })
  const [name, setName] = useState('')
  const [style, setStyle] = useState('casual')
  const [suggestion, setSuggestion] = useState(null) // { style, outfits, index } hasil rekomendasi terakhir
  const [message, setMessage] = useState(null) // { tone, text, link? }
  const [busy, setBusy] = useState(null) // 'generate' | 'save' | null

  const candidates = (items ?? []).filter((i) => MIXABLE.includes(i.category))

  // Datang dari tombol "Start Mix & Match" di Item Detail: /mix-match?item=ID -> pilih item itu dulu.
  const preselectId = params.get('item')
  useEffect(() => {
    if (!items || !preselectId) return
    const found = items.find((i) => String(i.id) === preselectId)
    if (!found) return
    if (MIXABLE.includes(found.category)) setSelection((s) => ({ ...s, [found.category]: found }))
    else setMessage({ tone: 'info', text: 'Only tops and bottoms can be mixed for now.' })
  }, [items, preselectId])

  // Klik item di daftar kiri: masuk slot sesuai kategorinya; klik lagi = lepas.
  function toggle(item) {
    setSelection((s) => ({ ...s, [item.category]: s[item.category]?.id === item.id ? null : item }))
    setMessage(null)
  }

  function showSuggestion(outfits, index, forStyle) {
    const outfit = outfits[index]
    const next = { top: null, bottom: null }
    outfit.items.forEach((i) => { if (MIXABLE.includes(i.category)) next[i.category] = i })
    setSelection(next)
    setSuggestion({ style: forStyle, outfits, index })
    setMessage({ tone: 'info', text: `Suggestion ${index + 1} of ${outfits.length}: ${outfit.reason}` })
  }

  async function onGenerate() {
    setMessage(null)
    // Gaya sama dengan hasil sebelumnya -> tampilkan saran berikutnya (tanpa panggil API lagi).
    if (suggestion && suggestion.style === style) {
      return showSuggestion(suggestion.outfits, (suggestion.index + 1) % suggestion.outfits.length, style)
    }
    setBusy('generate')
    try {
      const { outfits, message: note } = await recommendationService.getRecommendations({ style })
      if (outfits.length === 0) {
        setSuggestion(null)
        setMessage({ tone: 'info', text: note ?? 'No suggestions found.', link: 'add' })
      } else {
        showSuggestion(outfits, 0, style)
      }
    } catch (err) {
      setMessage({ tone: 'error', text: err.message })
    } finally {
      setBusy(null)
    }
  }

  async function onSave() {
    if (!selection.top || !selection.bottom) {
      return setMessage({ tone: 'error', text: 'Pick at least one top and one bottom first.' })
    }
    setBusy('save')
    try {
      const outfit = await outfitService.createOutfit({
        name: name.trim() || `${labelOf(STYLES, style)} outfit`,
        itemIds: [selection.top.id, selection.bottom.id],
      })
      setMessage({ tone: 'info', text: `Saved "${outfit.name}".`, link: 'profile' })
    } catch (err) {
      setMessage({ tone: 'error', text: err.message })
    } finally {
      setBusy(null)
    }
  }

  return (
    <div className="mx-auto grid max-w-[1440px] gap-10 px-6 py-12 lg:grid-cols-[300px_minmax(0,466px)_346px] lg:justify-between lg:px-[69px] lg:py-[90px]">
      {/* Kolom kiri: daftar pakaian dari wardrobe */}
      <section>
        <Label>Wardrobe</Label>
        <div className="mt-[26px] h-[410px] overflow-y-auto border-[3px] border-black p-4">
          {loading && <Notice>Loading…</Notice>}
          {error && <Notice tone="error">{error}</Notice>}
          {!loading && !error && candidates.length === 0 && (
            <Notice>
              No tops or bottoms yet. <Link to="/wardrobe/new" className="underline">Add Item</Link>
            </Notice>
          )}
          <ul className="flex flex-col gap-3">
            {candidates.map((item) => {
              const selected = selection[item.category]?.id === item.id
              return (
                <li key={item.id}>
                  <button
                    type="button"
                    onClick={() => toggle(item)}
                    aria-pressed={selected}
                    className={`flex w-full items-center gap-3 p-2 text-left text-sm ${selected ? 'bg-black text-white' : 'hover:bg-[#e0e0e0]'}`}
                  >
                    <ImageBox src={item.imageUrl} alt="" fit="contain" className="h-12 w-12 shrink-0 bg-white" />
                    <span className="min-w-0">
                      <span className="block truncate font-bold">{item.name}</span>
                      <span className="block text-xs">{labelOf(CATEGORIES, item.category)}</span>
                    </span>
                  </button>
                </li>
              )
            })}
          </ul>
        </div>
      </section>

      {/* Kolom tengah: pratinjau outfit (atasan & bawahan berdampingan, PRD 6.5) */}
      <section aria-label="Outfit preview">
        <div className="grid aspect-square w-full grid-cols-2 border border-black">
          {MIXABLE.map((slot, i) => (
            <div key={slot} className={i === 0 ? 'border-r border-black' : ''}>
              <ImageBox plain src={selection[slot]?.imageUrl} alt={selection[slot]?.name ?? ''} fit="contain" className="h-full w-full">
                {selection[slot] && (
                  <span className="absolute inset-x-0 bottom-0 truncate bg-black/80 px-2 py-1 text-xs text-white">
                    {selection[slot].name}
                  </span>
                )}
              </ImageBox>
            </div>
          ))}
        </div>
      </section>

      {/* Kolom kanan: form outfit + tombol */}
      <section className="flex flex-col gap-5">
        <Label>Outfit</Label>
        <input
          className={inputClass}
          placeholder="Outfit name"
          aria-label="Outfit name"
          value={name}
          maxLength={60}
          onChange={(e) => setName(e.target.value)}
        />
        <select className={inputClass} aria-label="Style" value={style} onChange={(e) => setStyle(e.target.value)}>
          {STYLES.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
        </select>

        <div className="mt-4 flex flex-col gap-[21px]">
          <Button onClick={onGenerate} disabled={busy !== null} className="h-10 w-full">
            {busy === 'generate' ? 'Generating…' : 'Generate Suggestion'}
          </Button>
          <Button onClick={onSave} disabled={busy !== null} className="h-10 w-full">
            {busy === 'save' ? 'Saving…' : 'Save Outfit'}
          </Button>
        </div>

        {message && (
          <Notice tone={message.tone}>
            {message.text}{' '}
            {message.link === 'profile' && <Link to="/profile" className="underline">View in Profile</Link>}
            {message.link === 'add' && <Link to="/wardrobe/new" className="underline">Add Item</Link>}
          </Notice>
        )}
      </section>
    </div>
  )
}
