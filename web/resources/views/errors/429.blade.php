@extends('layouts.app')

@section('title', 'Terlalu banyak percobaan')
@section('noindex', true)

@section('content')
    <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Terlalu banyak percobaan</h1>
    <x-error-state title="Tunggu sebentar" message="Kamu terlalu sering mencoba. Tunggu sekitar satu menit, lalu coba lagi." />
@endsection
