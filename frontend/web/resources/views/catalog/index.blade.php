@extends('layouts.app')
@section('title', 'Catalog · StyloMate')
@section('content')
<div class="mx-auto max-w-[1000px] px-6 py-12">
    <h1 class="mb-6 text-xl font-bold">Catalog (placeholder)</h1>
    <ul class="grid grid-cols-2 gap-6 sm:grid-cols-4">
        @foreach ($items as $item)
            <li>
                <x-image-box :src="$item['imageUrl']" :alt="$item['name']" fit="contain" class="aspect-square w-full" />
                <a href="{{ route('catalog.show', $item['id']) }}" class="mt-1 block text-sm font-bold underline">{{ $item['name'] }}</a>
                {{-- Beginilah catalog meneruskan pakaian ke Try-On (PRD TC-09) --}}
                <a href="{{ route('tryon', ['catalog' => $item['id']]) }}" class="text-sm underline">Try On</a>
            </li>
        @endforeach
    </ul>
</div>
@endsection
