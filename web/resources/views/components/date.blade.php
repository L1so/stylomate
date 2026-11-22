{{-- Format tanggal ISO dari FastAPI dalam Bahasa Indonesia; tanggal tidak valid tampil "-" agar halaman tidak error --}}
@props(['value' => null])
{{ empty($value) ? '-' : rescue(fn () => \Illuminate\Support\Carbon::parse($value)->locale('id')->translatedFormat('j M Y'), '-', false) }}
