{{-- Indikator loading. hidden=true: muncul otomatis saat form[data-loading] dikirim (lihat resources/js/app.js) --}}
@props(['message' => 'Memuat…', 'hidden' => false])
<div role="status" aria-live="polite" @if ($hidden) hidden data-loading-indicator @endif class="flex items-center gap-3 text-sm text-taupe">
    <span class="h-5 w-5 animate-spin rounded-full border-2 border-rose-50 border-t-terracotta" aria-hidden="true"></span>
    <span>{{ $message }}</span>
</div>
