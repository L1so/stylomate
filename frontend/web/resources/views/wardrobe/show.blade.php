@extends('layouts.app')
@section('title', $item['name'] . ' · StyloMate')

{{-- Wireframe "Wardrobe desc.png": gambar + info + Delete / Edit / Start Mix & Match. --}}
@section('content')
@php($options = \App\Support\FashionOptions::class)
<div class="mx-auto grid max-w-[1100px] items-center gap-12 px-6 py-16 lg:grid-cols-[466px_1fr] lg:py-[100px]">
    <x-image-box :src="$item['imageUrl']" :alt="$item['name']" fit="contain" class="aspect-[466/342] w-full" />

    <div class="flex max-w-[501px] flex-col gap-5">
        <x-label as="h1" left class="w-fit max-w-full">{{ $item['name'] }}</x-label>
        <x-label left class="w-fit">{{ $options::label($options::CATEGORIES, $item['category']) }}</x-label>

        <dl class="grid grid-cols-[90px_1fr] gap-y-3 text-sm">
            <dt>Color</dt><dd>{{ $options::label($options::COLORS, $item['color']) }}</dd>
            <dt>Style</dt><dd>{{ $options::label($options::STYLES, $item['style']) }}</dd>
            <dt>Added</dt><dd>{{ !empty($item['createdAt']) ? \Illuminate\Support\Carbon::parse($item['createdAt'])->format('j M Y') : '-' }}</dd>
        </dl>

        <div class="mt-8 grid grid-cols-3 gap-[9px]">
            <form method="POST" action="{{ route('wardrobe.destroy', $item['id']) }}" class="contents"
                  onsubmit="return confirm('Delete this item from your wardrobe?')">
                @csrf
                @method('DELETE')
                <x-button type="submit">Delete</x-button>
            </form>
            <x-button-link href="{{ route('wardrobe.edit', $item['id']) }}">Edit</x-button-link>
            <x-button-link href="{{ route('mixmatch', ['item' => $item['id']]) }}" class="!px-1 !text-[13px]">Start Mix &amp; Match</x-button-link>
        </div>
    </div>
</div>
@endsection
