{{-- Placeholder: isi halaman dikerjakan Anggota 4 --}}
@extends('layouts.app')

@section('title', 'Detail Pakaian')
@section('noindex', true)

@section('content')
    <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Detail Pakaian</h1>
    <p class="mb-6 text-sm text-gray-500">ID pakaian: {{ $id }}</p>
    <x-empty-state title="Segera hadir" message="Halaman ini sedang dikerjakan." />
@endsection
