import { useEffect, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { wardrobeService } from '../../../services/fashion/index.js'
import { CATEGORIES, COLORS, STYLES } from '../constants.js'
import { checkImageFile } from '../utils/image.js'
import ImageBox from '../components/ImageBox.jsx'
import Label from '../components/Label.jsx'
import Notice from '../components/Notice.jsx'
import Button from '../components/Button.jsx'

const inputClass = 'h-9 w-full border border-black bg-white px-2 text-sm'

function Field({ label, children }) {
  return (
    <label className="flex flex-col gap-1 text-xs">
      {label}
      {children}
    </label>
  )
}

// Halaman Add Item (wireframe "Wardrobe item.png"). Dipakai juga untuk Edit (route /wardrobe/:id/edit).
export default function ItemFormPage() {
  const { id } = useParams()
  const isEdit = Boolean(id)
  const navigate = useNavigate()
  const fileInput = useRef(null)

  const [values, setValues] = useState({ name: '', category: 'top', color: 'black', style: 'casual' })
  const [file, setFile] = useState(null) // File yang baru dipilih
  const [previewUrl, setPreviewUrl] = useState(null) // pratinjau file baru
  const [existingUrl, setExistingUrl] = useState(null) // gambar lama (mode edit)
  const [loading, setLoading] = useState(isEdit)
  const [loadFailed, setLoadFailed] = useState(false)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState(null)

  // Mode edit: isi form dengan data item yang ada.
  useEffect(() => {
    if (!isEdit) return
    let cancelled = false
    wardrobeService
      .getItem(id)
      .then((item) => {
        if (cancelled) return
        setValues({ name: item.name, category: item.category, color: item.color, style: item.style })
        setExistingUrl(item.imageUrl)
        setLoading(false)
      })
      .catch((err) => {
        if (cancelled) return
        setError(err.message)
        setLoadFailed(true)
        setLoading(false)
      })
    return () => { cancelled = true }
  }, [id, isEdit])

  // Bebaskan memori blob URL lama ketika pratinjau berganti / halaman ditutup.
  useEffect(() => () => { if (previewUrl) URL.revokeObjectURL(previewUrl) }, [previewUrl])

  const setField = (name) => (e) => setValues((v) => ({ ...v, [name]: e.target.value }))

  async function onPickFile(e) {
    const picked = e.target.files?.[0]
    e.target.value = '' // agar memilih file yang sama dua kali tetap memicu onChange
    if (!picked) return
    const problem = await checkImageFile(picked)
    if (problem) return setError(problem)
    setError(null)
    setFile(picked)
    setPreviewUrl(URL.createObjectURL(picked))
  }

  async function onSubmit(e) {
    e.preventDefault()
    if (!values.name.trim()) return setError('Enter a name for this item.')
    if (!isEdit && !file) return setError('Choose a photo of the item.')
    setSaving(true)
    setError(null)
    try {
      const payload = { ...values, name: values.name.trim(), imageFile: file }
      const saved = isEdit ? await wardrobeService.updateItem(id, payload) : await wardrobeService.createItem(payload)
      navigate(`/wardrobe/${saved.id}`) // PRD 7.3: Simpan -> Item Detail
    } catch (err) {
      setError(err.message)
      setSaving(false)
    }
  }

  if (loading) return <div className="mx-auto max-w-[600px] px-6 py-24"><Notice>Loading item…</Notice></div>
  if (loadFailed) {
    return (
      <div className="mx-auto max-w-[600px] px-6 py-24">
        <Notice tone="error">{error}</Notice>
        <Button className="mt-4 w-[172px]" onClick={() => navigate('/wardrobe')}>Back to Wardrobe</Button>
      </div>
    )
  }

  return (
    <div className="mx-auto grid max-w-[1100px] items-center gap-12 px-6 py-16 lg:grid-cols-[466px_1fr] lg:py-[100px]">
      <div>
        <button type="button" onClick={() => fileInput.current?.click()} aria-label="Choose item photo" className="block w-full">
          <ImageBox src={previewUrl ?? existingUrl} fit="contain" className="aspect-[466/342] w-full" />
        </button>
        <p className="mt-2 text-xs">Click the box to choose a JPEG or PNG, up to 10 MB.</p>
        <input ref={fileInput} type="file" accept="image/jpeg,image/png" className="sr-only" tabIndex={-1} onChange={onPickFile} />
      </div>

      <form onSubmit={onSubmit} noValidate className="flex max-w-[443px] flex-col gap-4">
        <Label as="h1" className="w-[167px] text-left">{isEdit ? 'Edit Item' : 'Add Item'}</Label>

        <Field label="Name">
          <input className={inputClass} value={values.name} onChange={setField('name')} maxLength={80} />
        </Field>

        <div className="grid grid-cols-2 gap-x-[29px] gap-y-4">
          <Field label="Category">
            <select className={inputClass} value={values.category} onChange={setField('category')}>
              {CATEGORIES.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
            </select>
          </Field>
          <Field label="Color">
            <select className={inputClass} value={values.color} onChange={setField('color')}>
              {COLORS.map((c) => <option key={c.value} value={c.value}>{c.label}</option>)}
            </select>
          </Field>
          <Field label="Style">
            <select className={inputClass} value={values.style} onChange={setField('style')}>
              {STYLES.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
            </select>
          </Field>
          <Field label="Photo">
            <Button className="!h-9 w-full !text-xs" onClick={() => fileInput.current?.click()}>
              {file ? 'Change photo' : 'Choose photo'}
            </Button>
          </Field>
        </div>

        {error && <Notice tone="error">{error}</Notice>}

        <div className="mt-6 grid grid-cols-2 gap-x-[29px]">
          <Button onClick={() => navigate(isEdit ? `/wardrobe/${id}` : '/wardrobe')}>Cancel</Button>
          <Button type="submit" disabled={saving}>{saving ? 'Saving…' : 'Save'}</Button>
        </div>
      </form>
    </div>
  )
}
