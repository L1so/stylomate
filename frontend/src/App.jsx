import { useState } from 'react'
import api from './services/api'

export default function App() {
  const [status, setStatus] = useState('Not tested')
  async function checkBackend() {
    setStatus('Checking...')
    try {
      const { data } = await api.get('/health', { timeout: 5000 })
      setStatus(data.status === 'ok' ? 'Backend connected' : 'Unexpected response')
    } catch {
      setStatus('Backend unavailable')
    }
  }
  return (
    <main className="mx-auto flex min-h-screen max-w-3xl flex-col items-center justify-center gap-4 p-8">
      <p className="text-sm font-semibold uppercase tracking-widest text-emerald-700">Development starter</p>
      <h1 className="text-4xl font-bold text-slate-900">StyloMate</h1>
      <p className="text-center text-slate-600">AI-powered sustainable fashion</p>
      <button onClick={checkBackend} className="rounded-lg bg-emerald-700 px-5 py-3 font-medium text-white">Check backend</button>
      <p role="status">{status}</p>
    </main>
  )
}
