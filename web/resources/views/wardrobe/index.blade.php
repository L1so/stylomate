@extends('layouts.app')

@section('title', 'Wardrobe')
@section('noindex', true)

@section('content')
    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Wardrobe</h1>
                <p class="text-taupe">Isi lemari digitalmu. Gunakan untuk Mix &amp; Match, rekomendasi, dan Try On.</p>
            </div>
            <x-button label="Tambah Pakaian" :href="route('wardrobe.create')" type="button" />
        </div>

        {{-- Filter kategori lewat query ?category= supaya tanpa JavaScript --}}
        @if ($totalCount > 0)
            <nav aria-label="Filter kategori" class="flex flex-wrap gap-2">
                <x-chip label="Semua" :href="route('wardrobe.index')" :active="$category === ''" />
                @foreach ($categories as $key => $label)
                    <x-chip :label="$label" :href="route('wardrobe.index', ['category' => $key])" :active="$category === $key" />
                @endforeach
            </nav>
        @endif

        @if ($totalCount === 0)
            <x-empty-state title="Wardrobe kamu masih kosong" message="Tambahkan pakaian pertamamu untuk mulai memadukan outfit." action-label="Tambah Pakaian" :action-url="route('wardrobe.create')" />
        @elseif (count($items) === 0)
            <x-empty-state title="Tidak ada pakaian di kategori ini" message="Coba kategori lain atau tambahkan pakaian baru." action-label="Lihat semua" :action-url="route('wardrobe.index')" />
        @else
            <ul class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($items as $item)
                    <li><x-clothing-card :item="$item" :href="route('wardrobe.show', ['id' => $item['id']])" /></li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
