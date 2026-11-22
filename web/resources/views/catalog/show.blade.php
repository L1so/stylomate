@extends('layouts.app')

@section('title', $item['name'])
@section('description', ($item['brand'] ?? 'StyloMate').' - '.$item['name'].'. Lihat detail dan coba secara virtual dengan Try On StyloMate.')

@section('content')
    <a href="{{ route('catalog.index') }}" class="inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-taupe hover:text-terracotta"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"></path><path d="m12 19-7-7 7-7"></path></svg>Kembali ke Catalog</a>

    <div class="mt-6 grid items-center gap-12 md:grid-cols-2">
        <div class="relative mx-auto w-full max-w-[520px] pb-6 pr-6 md:pb-14 md:pr-14">
            <div aria-hidden="true" class="absolute bottom-0 right-0 h-[84%] w-4/5 rounded-md bg-rose"></div>
            <div class="relative flex aspect-[3/4] items-center justify-center overflow-hidden rounded-md bg-grad-photo">
                @if (!empty($item['image_url']))
                    <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" width="600" height="800" class="h-full w-full object-contain">
                @else
                    <span class="text-sm text-taupe">Belum ada foto</span>
                @endif
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-taupe">{{ $item['brand'] ?? '-' }}</p>
            <h1 class="font-serif text-[32px] font-medium italic leading-10 tracking-[-0.015em] md:text-5xl md:leading-[56px]">{{ $item['name'] }}</h1>

            <div class="flex flex-wrap gap-2">
                @foreach (['category_label', 'color', 'style'] as $field)
                    @if (!empty($item[$field]))
                        <x-chip :label="ucfirst($item[$field])" />
                    @endif
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                {{-- Try On membawa id item katalog ke halaman Try On --}}
                <x-button label="Coba dengan Try On" :href="route('try-on.index', ['catalog_item_id' => $item['id']])" type="button" />
                {{-- Tombol toko hanya dirender bila product_url ada --}}
                @if (!empty($item['product_url']))
                    <x-button label="Lihat di toko" :href="$item['product_url']" variant="secondary" type="button" :external="true" />
                @endif
            </div>

            <p class="text-xs font-medium text-taupe">Produk contoh untuk demo. StyloMate tidak menjual produk ini.</p>
        </div>
    </div>
@endsection
