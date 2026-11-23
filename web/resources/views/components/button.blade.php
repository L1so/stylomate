{{-- Tombol reusable. Varian: primary, secondary, ghost, danger, outline-light di atas latar gelap/terracotta. Pakai: <x-button label="Simpan" :href="route('x')" variant="secondary" />; :external="true" untuk link ke situs lain --}}
@props(['label', 'href' => null, 'variant' => 'primary', 'type' => 'submit', 'external' => false, 'value' => null])
@php
    $variantClasses = [
        'primary' => 'bg-terracotta text-white hover:bg-terracotta-700',
        'secondary' => 'border border-rose-700 bg-white text-charcoal hover:bg-rose-50',
        'ghost' => 'text-charcoal hover:bg-rose-50',
        'danger' => 'bg-error text-white hover:brightness-90',
        'outline-light' => 'border border-linen text-linen hover:bg-white/10',
    ];
    $buttonClasses = 'inline-flex min-h-12 items-center justify-center gap-2 rounded-full px-6 text-[15px] font-semibold transition-colors duration-200 disabled:cursor-not-allowed disabled:bg-rose-50 disabled:text-rose-700 '
        .($variantClasses[$variant] ?? $variantClasses['primary']);
@endphp
{{-- Ada href = tampil sebagai link, tanpa href = tombol form --}}
@if (!empty($href))
    <a href="{{ $href }}" class="{{ $buttonClasses }}" @if (!empty($external)) target="_blank" rel="noopener noreferrer" @endif>{{ $label }}@if (!empty($external))<span class="sr-only"> (tab baru)</span>@endif</a>
@else
    <button type="{{ $type }}" class="{{ $buttonClasses }}" @if ($value !== null) value="{{ $value }}" @endif>{{ $label }}</button>
@endif
