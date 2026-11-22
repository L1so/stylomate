{{-- Tautan yang tampil seperti tombol. Pakai: <x-button-link href="..."> --}}
<a {{ $attributes->merge(['class' => 'inline-flex h-10 items-center justify-center rounded-md bg-[#616161] px-4 text-[15px] font-bold text-white transition-colors hover:bg-[#4a4a4a] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black']) }}>{{ $slot }}</a>
