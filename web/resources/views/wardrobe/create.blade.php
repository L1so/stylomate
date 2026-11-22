@extends('layouts.app')

@section('title', 'Tambah Pakaian')
@section('noindex', true)

@section('content')
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-2">
            <a href="{{ route('wardrobe.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-taupe hover:text-terracotta">&larr; Kembali ke Wardrobe</a>
            <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Tambah Pakaian</h1>
            <p class="text-taupe">Unggah foto pakaian lalu isi detailnya.</p>
        </div>

        @include('wardrobe._form', ['item' => null, 'action' => route('wardrobe.store'), 'submitLabel' => 'Simpan Pakaian', 'cancelUrl' => route('wardrobe.index')])
    </div>
@endsection
