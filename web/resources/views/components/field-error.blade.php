{{-- Pesan error satu field (dari validate() atau dari FastAPI 422); ikon + teks, bukan warna saja --}}
@props(['name', 'id' => null])
@error($name)
    <p id="{{ $id ?? $name.'-error' }}" class="flex items-start gap-1.5 text-sm text-error">
        <svg class="mt-0.5 flex-none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
        <span>{{ $message }}</span>
    </p>
@enderror
