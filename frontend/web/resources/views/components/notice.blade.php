{{-- Kotak pesan: tone="info" (default) atau tone="error". --}}
@props(['tone' => 'info'])
<div role="{{ $tone === 'error' ? 'alert' : 'status' }}" {{ $attributes->merge(['class' => 'border px-3 py-2 text-sm ' . ($tone === 'error' ? 'border-red-700 bg-red-50 text-red-900' : 'border-black bg-[#f2f2f2]')]) }}>{{ $slot }}</div>
