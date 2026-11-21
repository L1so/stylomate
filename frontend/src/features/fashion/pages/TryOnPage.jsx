import { useEffect, useRef, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { wardrobeService, catalogService, tryOnService } from '../../../services/fashion/index.js'
import { checkImageFile } from '../utils/image.js'
import { toGarment } from '../utils/garment.js'
import ImageBox from '../components/ImageBox.jsx'
import Label from '../components/Label.jsx'
import Notice from '../components/Notice.jsx'
import Button from '../components/Button.jsx'
import ItemPickerModal from '../components/ItemPickerModal.jsx'

// Halaman Virtual Try-On (wireframe "Try On.png").
// Alur (PRD 7.2): pilih pakaian -> upload foto -> Generate -> lihat hasil -> Save.
// Tombol "Generate" ditambahkan karena wireframe belum punya pemicu generate.
export default function TryOnPage() {
  const [params] = useSearchParams()
  const fileInput = useRef(null)

  // Foto pengguna
  const [personFile, setPersonFile] = useState(null)
  const [personUrl, setPersonUrl] = useState(null)
  const [photoError, setPhotoError] = useState(null)
  // Slot "My Outfit" (3 kotak) + slot yang sedang dipilih untuk dicoba
  const [slots, setSlots] = useState([null, null, null])
  const [active, setActive] = useState(null)
  const [pickerSlot, setPickerSlot] = useState(null) // nomor slot yang sedang memilih, null = tertutup
  // Hasil
  const [status, setStatus] = useState('idle') // 'idle' | 'loading' | 'done' | 'error'
  const [result, setResult] = useState(null) // { id, imageUrl }
  const [saved, setSaved] = useState(false)
  const [message, setMessage] = useState(null) // { tone, text, link? }

  const garment = active === null ? null : slots[active]
  const canGenerate = Boolean(personFile && garment) && status !== 'loading'

  // Datang dari Catalog/Wardrobe dengan ?catalog=ID atau ?wardrobe=ID: isi slot pertama.
  useEffect(() => {
    const wardrobeId = params.get('wardrobe')
    const catalogId = params.get('catalog')
    if (!wardrobeId && !catalogId) return
    let cancelled = false
    const load = wardrobeId
      ? wardrobeService.getItem(wardrobeId).then((i) => toGarment('wardrobe', i))
      : catalogService.getItem(catalogId).then((i) => toGarment('catalog', i))
    load
      .then((g) => { if (!cancelled) { setSlots([g, null, null]); setActive(0) } })
      .catch((err) => { if (!cancelled) setMessage({ tone: 'error', text: err.message }) })
    return () => { cancelled = true }
  }, [params])

  useEffect(() => () => { if (personUrl) URL.revokeObjectURL(personUrl) }, [personUrl])

  async function onPickPhoto(e) {
    const picked = e.target.files?.[0]
    e.target.value = ''
    if (!picked) return
    const problem = await checkImageFile(picked) // JPEG/PNG, <= 10 MB, bisa dibuka
    if (problem) return setPhotoError(problem)
    setPhotoError(null)
    setPersonFile(picked)
    setPersonUrl(URL.createObjectURL(picked))
    // Foto berganti -> hasil lama tidak relevan lagi.
    setResult(null); setSaved(false); setStatus('idle'); setMessage(null)
  }

  function chooseForSlot(index, g) {
    setSlots((s) => s.map((x, i) => (i === index ? g : x)))
    setActive((a) => a ?? index)
    setPickerSlot(null)
  }

  function removeSlot(index) {
    const next = slots.map((s, i) => (i === index ? null : s))
    setSlots(next)
    if (active === index) {
      const firstFilled = next.findIndex(Boolean)
      setActive(firstFilled === -1 ? null : firstFilled)
    }
  }

  async function onGenerate() {
    setStatus('loading'); setMessage(null); setSaved(false); setResult(null)
    try {
      // >>> TITIK INTEGRASI AI: lihat services/fashion/api/tryOn.js (mode asli) atau mock/tryOn.js
      const r = await tryOnService.generateTryOn({ personFile, garment })
      setResult(r)
      setStatus('done')
    } catch (err) {
      setStatus('error') // kegagalan AI tidak boleh membuat halaman berhenti (PRD NFR-09)
      setMessage({ tone: 'error', text: err.message || 'Generation failed. Please try again.' })
    }
  }

  async function onSave() {
    try {
      await tryOnService.saveResult({ id: result.id, imageUrl: result.imageUrl, garment })
      setSaved(true)
      setMessage({ tone: 'info', text: 'Saved.', link: true })
    } catch (err) {
      setMessage({ tone: 'error', text: err.message })
    }
  }

  async function onShare() {
    try {
      const blob = await (await fetch(result.imageUrl)).blob()
      const file = new File([blob], 'stylomate-try-on.png', { type: blob.type || 'image/png' })
      if (navigator.canShare?.({ files: [file] })) {
        await navigator.share({ files: [file], title: 'My StyloMate try-on' })
      } else {
        // Browser desktop umumnya tidak punya Web Share untuk file -> unduh gambar sebagai gantinya.
        const a = document.createElement('a')
        a.href = URL.createObjectURL(blob)
        a.download = 'stylomate-try-on.png'
        a.click()
        URL.revokeObjectURL(a.href)
      }
    } catch (err) {
      if (err.name !== 'AbortError') setMessage({ tone: 'error', text: 'Could not share this image. Try saving it instead.' })
    }
  }

  return (
    <div className="mx-auto grid max-w-[1440px] gap-10 px-6 py-8 lg:grid-cols-[minmax(0,703px)_minmax(0,424px)] lg:justify-between lg:px-[69px]">
      <div className="flex flex-col gap-5">
        <div className="grid gap-6 sm:grid-cols-[minmax(0,419px)_207px] sm:items-center sm:justify-between">
          {/* My Photo */}
          <div>
            <Label>My Photo</Label>
            <button type="button" onClick={() => fileInput.current?.click()} aria-label="Choose your photo" className="mt-[15px] block w-full">
              <ImageBox src={personUrl} alt="Your photo" fit="contain" className="aspect-[419/297] w-full" />
            </button>
            <input ref={fileInput} type="file" accept="image/jpeg,image/png" className="sr-only" tabIndex={-1} onChange={onPickPhoto} />
            <p className="mt-2 text-xs">Click the box to choose a JPEG or PNG, up to 10 MB. A clear, well-lit photo works best.</p>
            {photoError && <Notice tone="error" className="mt-2">{photoError}</Notice>}
          </div>

          {/* Tombol aksi (tengah) */}
          <div className="flex flex-col gap-[22px]">
            <Button onClick={onGenerate} disabled={!canGenerate} className="w-full">
              {status === 'loading' ? 'Generating…' : 'Generate'}
            </Button>
            <Button onClick={onSave} disabled={!result || saved} className="w-full">{saved ? 'Saved' : 'Save'}</Button>
            <Button onClick={onShare} disabled={!result} className="w-full">Share</Button>
          </div>
        </div>

        {/* My Outfit */}
        <div>
          <Label>My Outfit</Label>
          <ul className="mt-[29px] grid grid-cols-3 gap-6 lg:gap-[57px]">
            {slots.map((g, i) => (
              <li key={i} className="relative">
                <button
                  type="button"
                  onClick={() => (g ? setActive(i) : setPickerSlot(i))}
                  aria-label={g ? `Try on ${g.name}` : `Choose an item for slot ${i + 1}`}
                  aria-pressed={g ? active === i : undefined}
                  className={`block w-full ${g && active === i ? 'outline outline-[3px] outline-offset-2 outline-black' : ''}`}
                >
                  <ImageBox src={g?.imageUrl} alt={g?.name ?? ''} fit="contain" className="aspect-square w-full">
                    {!g && (
                      <span className="absolute inset-0 grid place-items-center">
                        <span className="bg-white px-2 py-1 text-xs">+ Add item</span>
                      </span>
                    )}
                  </ImageBox>
                </button>
                {g && (
                  <button
                    type="button"
                    onClick={() => removeSlot(i)}
                    aria-label={`Remove ${g.name}`}
                    className="absolute right-1 top-1 h-6 w-6 bg-black text-sm text-white"
                  >
                    ×
                  </button>
                )}
              </li>
            ))}
          </ul>
          <p className="mt-3 text-xs">Click a filled slot to choose which item to try on. One item is processed at a time.</p>
        </div>
      </div>

      {/* Result */}
      <div className="lg:mt-[35px]">
        <Label>Result</Label>
        <ImageBox src={result?.imageUrl} alt="Try-on result" fit="contain" className="mt-[17px] aspect-[424/520] w-full">
          {status === 'loading' && (
            <div className="absolute inset-0 grid place-items-center bg-white/85 p-6 text-center text-sm" role="status" aria-live="polite">
              <div>
                <div className="mx-auto mb-4 h-1 w-40 animate-pulse bg-black" />
                Generating your try-on…
                <br />
                This can take up to a minute.
              </div>
            </div>
          )}
        </ImageBox>
        {message && (
          <Notice tone={message.tone} className="mt-3">
            {message.text}{' '}
            {message.link && <Link to="/profile" className="underline">View in Profile</Link>}
          </Notice>
        )}
      </div>

      {pickerSlot !== null && <ItemPickerModal onPick={(g) => chooseForSlot(pickerSlot, g)} onClose={() => setPickerSlot(null)} />}
    </div>
  )
}
