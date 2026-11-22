{{-- Placeholder: isi halaman dikerjakan Anggota 4 --}}
@extends('layouts.app')

@section('title', 'Wardrobe')
@section('noindex', true)

@section('content')
    <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Wardrobe</h1>
    <div class="mb-6"><x-button label="Tambah Pakaian" :href="route('wardrobe.create')" type="button" /></div>
    <x-empty-state title="Segera hadir" message="Halaman ini sedang dikerjakan." />
@endsection
