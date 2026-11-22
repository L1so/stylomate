@extends('layouts.app')
@section('title', 'Wardrobe · StyloMate')

{{-- Wireframe "Wardrobe.png": sidebar kategori, judul + tombol Add Item, grid pakaian. Tanpa JavaScript. --}}
@section('content')
@php
    $filters = array_merge([['value' => 'all', 'plural' => 'All items']], \App\Support\FashionOptions::CATEGORIES);
@endphp
<div class="mx-auto flex max-w-[1440px] flex-col gap-8 px-6 py-12 lg:flex-row lg:gap-16 lg:px-[50px] lg:py-[70px]">
    <aside aria-label="Filter by category" class="h-fit w-full shrink-0 border-[3px] border-black p-5 lg:min-h-[550px] lg:w-[240px]">
        <ul class="flex flex-col gap-3">
            @foreach ($filters as $f)
                <li>
                    <a href="{{ $f['value'] === 'all' ? route('wardrobe.index') : route('wardrobe.index', ['category' => $f['value']]) }}"
                       @if ($category === $f['value']) aria-current="page" @endif
                       class="flex w-full justify-between px-3 py-2 text-left text-sm {{ $category === $f['value'] ? 'bg-black font-bold text-white' : 'hover:bg-[#e0e0e0]' }}">
                        <span>{{ $f['plural'] }}</span>
                        <span>{{ $counts[$f['value']] ?? 0 }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </aside>

    <section class="min-w-0 flex-1">
        <div class="mb-[46px] flex items-center justify-between gap-4">
            <x-label as="h1" left class="min-w-[278px]">My Wardrobe</x-label>
            <x-button-link href="{{ route('wardrobe.create') }}" class="w-[172px]">Add Item</x-button-link>
        </div>

        @if (count($visible) === 0)
            <x-notice>
                {{ count($items) === 0 ? 'Your wardrobe is empty. Add your first item to start mixing outfits.' : 'No items in this category yet.' }}
                <a href="{{ route('wardrobe.create') }}" class="underline">Add Item</a>
            </x-notice>
        @endif

        <ul class="grid grid-cols-2 gap-x-[53px] gap-y-9 md:grid-cols-3 xl:grid-cols-4">
            @foreach ($visible as $item)
                <li>
                    <a href="{{ route('wardrobe.show', $item['id']) }}" aria-label="{{ $item['name'] }}" class="group block">
                        <x-image-box :src="$item['imageUrl']" :alt="$item['name']" fit="contain" class="aspect-[197/222] w-full">
                            <span class="absolute inset-x-0 bottom-0 truncate bg-black/80 px-2 py-1 text-xs text-white opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">{{ $item['name'] }}</span>
                        </x-image-box>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
