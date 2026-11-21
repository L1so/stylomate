import { useCallback, useEffect, useState } from 'react'

/**
 * Menjalankan fungsi async saat halaman dibuka dan melacak status-nya.
 *   const { data, loading, error, reload } = useAsync(() => wardrobeService.listItems(), [])
 * `deps` = kapan harus dijalankan ulang (seperti useEffect). `reload()` = jalankan ulang manual.
 */
export default function useAsync(fn, deps) {
  const [state, setState] = useState({ data: null, loading: true, error: null })
  const [tick, setTick] = useState(0)
  const reload = useCallback(() => setTick((t) => t + 1), [])

  useEffect(() => {
    let cancelled = false // abaikan hasil jika halaman sudah berpindah
    setState((s) => ({ ...s, loading: true, error: null }))
    fn()
      .then((data) => !cancelled && setState({ data, loading: false, error: null }))
      .catch((err) => !cancelled && setState({ data: null, loading: false, error: err.message || 'Something went wrong.' }))
    return () => { cancelled = true }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [...deps, tick])

  return { ...state, reload }
}
