{{-- Kotak gambar ala wireframe. Ukuran HARUS diberikan lewat class (mis. aspect-square w-full). --}}
@props(['src' => null, 'alt' => '', 'fit' => 'cover', 'plain' => false])
<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-[#e0e0e0] ' . ($plain ? '' : 'border border-black')]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $alt }}" class="absolute inset-0 h-full w-full {{ $fit === 'contain' ? 'object-contain' : 'object-cover' }}">
    @else
        @include('partials.placeholder-x')
    @endif
    {{ $slot }}
</div>
