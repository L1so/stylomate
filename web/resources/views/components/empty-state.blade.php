{{-- Tampilan saat data kosong --}}
@props(['title', 'message' => null, 'actionLabel' => null, 'actionUrl' => null])
<div class="flex flex-col items-center gap-3 rounded-lg bg-white px-6 py-12 text-center shadow-xs">
    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-rose-50 text-rose-700">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.38 3.46 16 2a4 4 0 0 1-8 0L3.62 3.46a2 2 0 0 0-1.34 2.23l.58 3.47a1 1 0 0 0 .99.84H6v10c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V10h2.15a1 1 0 0 0 .99-.84l.58-3.47a2 2 0 0 0-1.34-2.23z"></path></svg>
    </span>
    <h2 class="font-serif text-[28px] font-medium leading-9">{{ $title }}</h2>
    @if (!empty($message))
        <p class="max-w-md text-taupe">{{ $message }}</p>
    @endif
    @if (!empty($actionLabel) && !empty($actionUrl))
        <div class="mt-2">
            <x-button :label="$actionLabel" :href="$actionUrl" variant="primary" type="button" />
        </div>
    @endif
</div>
