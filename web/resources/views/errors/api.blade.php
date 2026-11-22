{{-- Halaman error saat FastAPI gagal (dipanggil dari bootstrap/app.php) --}}
@extends('layouts.app')

@section('title', 'Ada yang tidak beres')
@section('noindex', true)

@section('content')
    <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Ada yang tidak beres</h1>
    <x-error-state title="Halaman belum bisa ditampilkan" :message="$message" :retry-url="request()->fullUrl()" />
@endsection
