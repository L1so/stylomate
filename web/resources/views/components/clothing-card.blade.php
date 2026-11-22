{{-- Kartu pakaian Wardrobe (dipakai Anggota 4). image_url lewat route media karena gambar privat --}}
@props(['item', 'href' => null, 'selected' => false])
@php $wrapperTag = !empty($href) ? 'a' : 'div'; @endphp
<article @class([
    'overflow-hidden rounded-md bg-white p-3 shadow-xs transition-shadow hover:shadow-sm',
    'ring-2 ring-terracotta' => $selected,
])>
    <{{ $wrapperTag }} @if (!empty($href)) href="{{ $href }}" @endif class="flex flex-col gap-3">
        @if (!empty($item['image_url']))
            <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" width="300" height="400" loading="lazy" class="aspect-[3/4] w-full rounded-sm bg-grad-photo object-cover">
        @else
            <div class="flex aspect-[3/4] w-full items-center justify-center rounded-sm bg-grad-photo text-sm text-taupe">Belum ada foto</div>
        @endif
        <div class="flex flex-col gap-2 px-1 pb-1">
            <h2 class="font-serif text-lg font-medium leading-[26px]">
                {{ $item['name'] }}
                @if ($selected)
                    <span class="sr-only">(dipilih)</span>
                @endif
            </h2>
            <div class="flex flex-wrap gap-1">
                <x-chip :label="$item['category'] ?? '-'" />
                <x-chip :label="$item['color'] ?? '-'" />
                <x-chip :label="$item['style'] ?? '-'" />
            </div>
        </div>
    </{{ $wrapperTag }}>
</article>
