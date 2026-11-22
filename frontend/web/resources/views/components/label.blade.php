{{-- Pita hitam teks putih (judul bagian di wireframe). Tambahkan atribut `left` untuk rata kiri. --}}
@props(['as' => 'div', 'left' => false])
<{{ $as }} {{ $attributes->merge(['class' => 'bg-black px-3 text-[15px] font-bold leading-6 text-white ' . ($left ? 'text-left' : 'text-center')]) }}>{{ $slot }}</{{ $as }}>
