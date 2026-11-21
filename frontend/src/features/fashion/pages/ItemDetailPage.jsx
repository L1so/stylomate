import { useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import { wardrobeService } from '../../../services/fashion/index.js'
import { CATEGORIES, COLORS, STYLES, labelOf } from '../constants.js'
import useAsync from '../hooks/useAsync.js'
import ImageBox from '../components/ImageBox.jsx'
import Label from '../components/Label.jsx'
import Notice from '../components/Notice.jsx'
import Button, { ButtonLink } from '../components/Button.jsx'

const formatDate = (iso) =>
  iso ? new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '-'

// Halaman Item Detail (wireframe "Wardrobe desc.png"): gambar + info + Delete / Edit / Start Mix & Match.
export default function ItemDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { data: item, loading, error } = useAsync(() => wardrobeService.getItem(id), [id])
  const [busy, setBusy] = useState(false)
  const [actionError, setActionError] = useState(null)

  async function onDelete() {
    if (!window.confirm(`Delete "${item.name}" from your wardrobe?`)) return
    setBusy(true)
    setActionError(null)
    try {
      await wardrobeService.deleteItem(id)
      navigate('/wardrobe')
    } catch (err) {
      setActionError(err.message)
      setBusy(false)
    }
  }

  if (loading) return <div className="mx-auto max-w-[600px] px-6 py-24"><Notice>Loading item…</Notice></div>
  if (error) {
    return (
      <div className="mx-auto max-w-[600px] px-6 py-24">
        <Notice tone="error">{error}</Notice>
        <ButtonLink to="/wardrobe" className="mt-4 w-[172px]">Back to Wardrobe</ButtonLink>
      </div>
    )
  }

  return (
    <div className="mx-auto grid max-w-[1100px] items-center gap-12 px-6 py-16 lg:grid-cols-[466px_1fr] lg:py-[100px]">
      <ImageBox src={item.imageUrl} alt={item.name} fit="contain" className="aspect-[466/342] w-full" />

      <div className="flex max-w-[501px] flex-col gap-5">
        <Label as="h1" className="w-fit max-w-full text-left">{item.name}</Label>
        <Label className="w-fit text-left">{labelOf(CATEGORIES, item.category)}</Label>

        <dl className="grid grid-cols-[90px_1fr] gap-y-3 text-sm">
          <dt>Color</dt><dd>{labelOf(COLORS, item.color)}</dd>
          <dt>Style</dt><dd>{labelOf(STYLES, item.style)}</dd>
          <dt>Added</dt><dd>{formatDate(item.createdAt)}</dd>
        </dl>

        {actionError && <Notice tone="error">{actionError}</Notice>}

        <div className="mt-8 grid grid-cols-3 gap-[9px]">
          <Button onClick={onDelete} disabled={busy}>Delete</Button>
          <ButtonLink to={`/wardrobe/${id}/edit`}>Edit</ButtonLink>
          <ButtonLink to={`/mix-match?item=${id}`} className="!px-1 !text-[13px]">Start Mix &amp; Match</ButtonLink>
        </div>
      </div>
    </div>
  )
}
