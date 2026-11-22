@extends('layouts.app')

@section('title', 'Detail Pakaian')
@section('noindex', true)

@section('content')
    <a href="{{ route('wardrobe.index') }}" class="inline-flex min-h-11 items-center text-sm font-semibold text-taupe hover:text-terracotta">&larr; Kembali ke Wardrobe</a>

    <div class="mt-6 grid items-start gap-12 md:grid-cols-2">
        <div class="relative mx-auto flex aspect-[3/4] w-full max-w-[520px] items-center justify-center overflow-hidden rounded-md bg-grad-photo">
            @if (!empty($item['image_url']))
                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" width="600" height="800" class="h-full w-full object-contain">
            @else
                <span class="text-sm text-taupe">Belum ada foto</span>
            @endif
        </div>

        <div class="flex flex-col gap-4">
            <h1 class="font-serif text-[32px] font-medium italic leading-10 tracking-[-0.015em] md:text-5xl md:leading-[56px]">{{ $item['name'] }}</h1>

            <div class="flex flex-wrap gap-2">
                <x-chip :label="$item['category_label'] ?? '-'" />
                <x-chip :label="ucfirst($item['color'] ?? '-')" />
                <x-chip :label="ucfirst($item['style'] ?? '-')" />
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                @if (($item['category'] ?? null) === 'top')
                    <x-button label="Coba dengan Try On" :href="route('try-on.index', ['wardrobe_item_id' => $item['id']])" type="button" />
                @endif
                <x-button label="Edit" :href="route('wardrobe.edit', ['id' => $item['id']])" variant="secondary" type="button" />
                <x-confirm-delete :action="route('wardrobe.destroy', ['id' => $item['id']])" message="Hapus pakaian ini dari Wardrobe? Tindakan ini tidak bisa dibatalkan." />
            </div>
        </div>
    </div>
@endsection
