@extends('layouts.app')

@section('title', 'Catalog')
@section('description', 'Jelajahi Catalog StyloMate dan pilih pakaian untuk dicoba lewat Try On.')

@section('content')
    <section class="flex flex-wrap items-center justify-between gap-8 overflow-hidden rounded-lg bg-grad-soft p-8 md:p-14">
        <div class="flex min-w-0 max-w-[540px] flex-[1_1_420px] flex-col gap-4">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-taupe">Kurasi berkelanjutan</p>
            <h1 class="font-serif text-[32px] font-medium italic leading-10 tracking-[-0.015em] md:text-5xl md:leading-[56px]">Catalog</h1>
            <p class="text-lg leading-7 text-taupe">Pakaian contoh untuk dicoba lewat Try On. Bila cocok, kunjungi tokonya.</p>
        </div>
        <div aria-hidden="true" class="hidden items-end gap-4 md:flex">
            <div class="flex aspect-[3/4] w-[140px] items-center justify-center rounded-md bg-linen">
                <img src="{{ asset('images/illustrations/dress.svg') }}" alt="" width="300" height="400" class="w-[84%]">
            </div>
            <div class="flex aspect-[3/4] w-[180px] items-center justify-center rounded-md bg-linen">
                <img src="{{ asset('images/illustrations/blazer.svg') }}" alt="" width="300" height="400" class="w-[84%]">
            </div>
            <div class="flex aspect-[3/4] w-[140px] items-center justify-center rounded-md bg-linen">
                <img src="{{ asset('images/illustrations/kulot.svg') }}" alt="" width="300" height="400" class="w-[84%]">
            </div>
        </div>
    </section>

    {{-- Form GET: filter tersimpan di URL sehingga bisa dibagikan/di-refresh --}}
    <form action="{{ route('catalog.index') }}" method="GET" role="search" data-loading class="mt-10 flex flex-wrap items-end gap-3">
        <div class="min-w-0 flex-[1_1_320px]">
            <x-input name="search" label="Cari pakaian" type="search" :value="$search" autocomplete="off" placeholder="Cari kemeja, kulot, rok…" />
        </div>
        @if ($category !== '')
            <input type="hidden" name="category" value="{{ $category }}">
        @endif
        <x-button label="Cari" />
        <x-loading message="Mencari…" :hidden="true" />
    </form>

    {{-- Kategori lingkaran adalah satu-satunya filter kategori (design system) --}}
    <nav aria-label="Kategori" class="mt-8 flex gap-6 overflow-x-auto pb-1">
        @php
            $categoryImages = ['' => 'try-on-hasil', 'top' => 'kemeja', 'bottom' => 'kulot', 'dress' => 'dress', 'outerwear' => 'blazer', 'shoes' => 'sepatu', 'accessory' => 'tas'];
            $categoryLinks = ['' => 'Semua'] + $categories;
        @endphp
        @foreach ($categoryLinks as $value => $label)
            @php
                $isActive = $category === $value;
            @endphp
            <a href="{{ route('catalog.index', array_filter(['search' => $search, 'category' => $value], fn ($v) => $v !== '')) }}" class="flex flex-none flex-col items-center gap-2.5 p-1" @if ($isActive) aria-current="true" @endif>
                <span @class(['flex h-[104px] w-[104px] items-start justify-center overflow-hidden rounded-full border-2 bg-rose-50 transition-shadow hover:shadow-sm', 'border-terracotta' => $isActive, 'border-transparent' => ! $isActive])>
                    <img src="{{ asset('images/illustrations/'.($categoryImages[$value] ?? 'kaos').'.svg') }}" alt="" width="300" height="400" class="mt-[18%] w-[78%]">
                </span>
                <span @class(['text-sm', 'font-bold text-charcoal' => $isActive, 'font-medium text-taupe' => ! $isActive])>{{ $label }}</span>
            </a>
        @endforeach
    </nav>

    @php
        $heading = $category !== '' ? $categories[$category] : ($search !== '' ? 'Hasil untuk "'.$search.'"' : 'Semua pakaian');
    @endphp

    {{-- Data dari GET /api/catalog lewat CatalogController; error FastAPI ditangani handler global --}}
    <section aria-labelledby="judul-hasil" class="mt-10 flex flex-col gap-6">
        <div class="flex items-baseline justify-between gap-4 border-b border-rose pb-4">
            <h2 id="judul-hasil" class="font-serif text-[22px] font-medium leading-[30px]">{{ $heading }}</h2>
            <span class="text-sm text-taupe">{{ count($items) }} pakaian</span>
        </div>

        @if (count($items) > 0)
            <div class="grid grid-cols-2 gap-x-6 gap-y-10 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($items as $item)
                    <x-product-card :item="$item" :href="route('catalog.show', $item['id'])" :try-on-href="route('try-on.index', ['catalog_item_id' => $item['id']])" />
                @endforeach
            </div>
        @elseif ($search !== '' || $category !== '')
            <x-empty-state title="Pakaian tidak ditemukan" message="Coba kata kunci atau kategori lain." action-label="Hapus Filter" :action-url="route('catalog.index')" />
        @else
            <x-empty-state title="Catalog masih kosong" message="Belum ada pakaian di Catalog. Coba lagi nanti, ya." />
        @endif
    </section>
@endsection
