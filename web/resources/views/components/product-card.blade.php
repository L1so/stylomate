{{-- Kartu produk Catalog: foto 3:4, tombol ikon "Lihat di toko" hanya bila ada product_url, brand, nama, meta, tombol Try On --}}
@props(['item', 'href', 'tryOnHref' => null])
@php
    $meta = collect([$item['category_label'] ?? null, $item['color'] ?? null])->filter()->map(fn ($value) => ucfirst($value))->implode(' · ');
@endphp
<article class="flex flex-col gap-3">
    <div class="relative">
        <a href="{{ $href }}" tabindex="-1" aria-hidden="true" class="block overflow-hidden rounded-md bg-grad-photo">
            @if (!empty($item['image_url']))
                <img src="{{ $item['image_url'] }}" alt="" width="300" height="400" loading="lazy" class="aspect-[3/4] w-full object-cover">
            @else
                <div class="flex aspect-[3/4] w-full items-center justify-center text-sm text-taupe">Belum ada foto</div>
            @endif
        </a>
        @if (!empty($item['product_url']))
            <a href="{{ $item['product_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Lihat {{ $item['name'] }} di toko (tab baru)" class="absolute right-3 top-3 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white text-charcoal shadow-xs transition-colors hover:bg-rose-50">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"></path><path d="M10 14 21 3"></path><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path></svg>
            </a>
        @endif
    </div>
    <div class="flex flex-col gap-1">
        <span class="text-xs font-semibold uppercase tracking-[0.12em] text-taupe">{{ $item['brand'] ?? '-' }}</span>
        <h2 class="font-serif text-xl font-medium leading-[26px]"><a href="{{ $href }}" class="hover:text-terracotta">{{ $item['name'] }}</a></h2>
        @if ($meta !== '')
            <span class="text-sm text-taupe">{{ $meta }}</span>
        @endif
    </div>
    @if (!empty($tryOnHref))
        <div>
            <x-button label="Try On" :href="$tryOnHref" variant="secondary" type="button" />
        </div>
    @endif
</article>
