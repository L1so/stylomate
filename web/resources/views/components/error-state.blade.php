{{-- Tampilan error, misalnya FastAPI mati/timeout. Pesan ramah pengguna tanpa detail teknis --}}
@props(['message', 'title' => 'Terjadi kesalahan', 'retryUrl' => null])
<div role="alert" class="flex flex-col items-center gap-3 rounded-lg bg-white px-6 py-12 text-center shadow-xs">
    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-error">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
    </span>
    <h2 class="font-serif text-[28px] font-medium leading-9">{{ $title }}</h2>
    <p class="max-w-md text-taupe">{{ $message }}</p>
    @if (!empty($retryUrl))
        <div class="mt-2">
            <x-button label="Coba Lagi" :href="$retryUrl" variant="secondary" type="button" />
        </div>
    @endif
</div>
