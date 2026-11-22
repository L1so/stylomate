@extends('layouts.app')
@section('title', 'Recommendations · StyloMate')

{{-- PRD 7: Curation = pilih gaya -> rekomendasi -> simpan. Halaman Blade murni (tanpa JavaScript):
     GET /recommendations?style=... lalu tiap saran dapat disimpan lewat form POST /outfits. --}}
@section('content')
@php($options = \App\Support\FashionOptions::class)
<div class="mx-auto max-w-[1000px] px-6 py-12">
    <x-label as="h1" left class="mb-6 w-[278px]">Outfit Recommendation</x-label>

    <form method="GET" action="{{ route('recommendations') }}" class="mb-8 flex flex-wrap items-end gap-3">
        <label class="flex flex-col gap-1 text-xs">Style
            <select name="style" class="h-10 w-[220px] border border-black bg-white px-3 text-sm">
                @foreach ($options::STYLES as $s)
                    <option value="{{ $s['value'] }}" @selected($style === $s['value'])>{{ $s['label'] }}</option>
                @endforeach
            </select>
        </label>
        <x-button type="submit" class="w-[200px]">Get suggestions</x-button>
        <a href="{{ route('mixmatch') }}" class="text-sm underline">Open Mix &amp; Match</a>
    </form>

    @if ($result !== null)
        @if (count($result['outfits']) === 0)
            <x-notice>
                {{ $result['message'] ?? 'No suggestions found.' }}
                <a href="{{ route('wardrobe.create') }}" class="underline">Add Item</a>
            </x-notice>
        @endif

        <ul class="flex flex-col gap-6">
            @foreach ($result['outfits'] as $index => $outfit)
                <li class="border border-black p-4">
                    <p class="mb-3 text-sm font-bold">Suggestion {{ $index + 1 }}</p>
                    <div class="mb-3 flex gap-4">
                        @foreach ($outfit['items'] as $item)
                            <div class="w-[140px]">
                                <x-image-box :src="$item['imageUrl']" :alt="$item['name']" fit="contain" class="aspect-square w-full" />
                                <p class="mt-1 truncate text-xs">{{ $item['name'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <p class="mb-3 text-sm">{{ $outfit['reason'] }}</p>

                    {{-- Simpan outfit: form biasa + CSRF --}}
                    <form method="POST" action="{{ route('outfits.store') }}">
                        @csrf
                        <input type="hidden" name="name" value="{{ $options::label($options::STYLES, $style) }} suggestion {{ $index + 1 }}">
                        @foreach ($outfit['items'] as $item)
                            <input type="hidden" name="item_ids[]" value="{{ $item['id'] }}">
                        @endforeach
                        <x-button type="submit" class="w-[200px]">Save outfit</x-button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
