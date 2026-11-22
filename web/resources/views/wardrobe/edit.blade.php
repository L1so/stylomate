@extends('layouts.app')

@section('title', 'Edit Pakaian')
@section('noindex', true)

@section('content')
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-2">
            <a href="{{ route('wardrobe.show', ['id' => $item['id']]) }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-taupe hover:text-terracotta">&larr; Kembali ke detail</a>
            <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Edit Pakaian</h1>
        </div>

        @include('wardrobe._form', ['item' => $item, 'action' => route('wardrobe.update', ['id' => $item['id']]), 'submitLabel' => 'Simpan Perubahan', 'cancelUrl' => route('wardrobe.show', ['id' => $item['id']])])
    </div>
@endsection
