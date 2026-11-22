@extends('layouts.app')
@section('title', $item['name'] . ' · StyloMate')
@section('content')
@php($options = \App\Support\FashionOptions::class)
<div class="mx-auto grid max-w-[1000px] items-center gap-10 px-6 py-12 sm:grid-cols-2">
    <p class="sm:col-span-2 text-xs">Catalog detail (placeholder — milik anggota 2)</p>
    <x-image-box :src="$item['imageUrl']" :alt="$item['name']" fit="contain" class="aspect-square w-full" />
    <div class="flex flex-col gap-3 text-sm">
        <h1 class="text-xl font-bold">{{ $item['name'] }}</h1>
        @if (!empty($item['brand']))<p>{{ $item['brand'] }}</p>@endif
        <p>{{ $options::label($options::CATEGORIES, $item['category']) }} · {{ $options::label($options::COLORS, $item['color']) }} · {{ $options::label($options::STYLES, $item['style']) }}</p>
        <div class="mt-4 flex gap-3">
            {{-- Catalog meneruskan pakaian ke Try-On (PRD 6.3) --}}
            <x-button-link href="{{ route('tryon', ['catalog' => $item['id']]) }}" class="w-[172px]">Try On</x-button-link>
            @if (!empty($item['productUrl']))
                <x-button-link href="{{ $item['productUrl'] }}" class="w-[172px]">Store</x-button-link>
            @endif
        </div>
    </div>
</div>
@endsection
