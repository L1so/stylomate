{{-- Label kecil (kategori/filter). Dengan href menjadi link filter; active = terpilih (terracotta) --}}
@props(['label', 'href' => null, 'active' => false])
@php
    $chipClasses = 'inline-flex min-h-9 items-center rounded-full px-4 text-sm font-medium '
        .($active ? 'bg-terracotta text-white' : 'bg-rose-50 text-charcoal');
@endphp
@if (!empty($href))
    <a href="{{ $href }}" class="{{ $chipClasses }} transition-colors @if (! $active) hover:bg-rose @endif" @if ($active) aria-current="true" @endif>{{ $label }}</a>
@else
    <span class="{{ $chipClasses }}">{{ $label }}</span>
@endif
