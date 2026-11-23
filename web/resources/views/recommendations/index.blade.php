@extends('layouts.app')

@section('title', 'Rekomendasi Outfit')
@section('noindex', true)

@section('content')
    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Rekomendasi Outfit</h1>
            <p class="text-taupe">Pilih style, lalu StyloMate menyusun outfit dari isi Wardrobe kamu.</p>
        </div>

        {{-- Pilihan style lewat query ?style= supaya tanpa JavaScript --}}
        <nav aria-label="Pilih style" class="flex flex-wrap gap-2">
            @foreach ($styles as $key => $label)
                <x-chip :label="$label" :href="route('recommendations.index', ['style' => $key])" :active="$style === $key" />
            @endforeach
        </nav>

        @if ($outfits === null)
            <x-empty-state title="Pilih style dulu" message="Pilih salah satu style di atas untuk melihat rekomendasi outfit." />
        @elseif (count($outfits) === 0)
            <x-empty-state title="Belum ada rekomendasi" message="Belum ada outfit yang cocok untuk style ini. Tambahkan atasan dan bawahan ke Wardrobe supaya rekomendasi bisa dibuat." action-label="Tambah Pakaian" :action-url="route('wardrobe.create')" />
        @else
            <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($outfits as $index => $outfit)
                    <li class="flex flex-col gap-4 rounded-md bg-white p-4 shadow-xs">
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($outfit['items'] ?? [] as $piece)
                                <div class="flex flex-col gap-2">
                                    <div class="flex aspect-[3/4] items-center justify-center overflow-hidden rounded-sm bg-grad-photo">
                                        @if (!empty($piece['image_url']))
                                            <img src="{{ $piece['thumbnail_url'] ?? $piece['image_url'] }}" alt="{{ $piece['name'] ?? 'Pakaian' }}" width="300" height="400" loading="lazy" class="h-full w-full object-cover">
                                        @else
                                            <span class="text-xs text-taupe">Belum ada foto</span>
                                        @endif
                                    </div>
                                    <span class="px-1 text-sm font-semibold">{{ $piece['name'] ?? '-' }}</span>
                                </div>
                            @endforeach
                        </div>
                        <h2 class="font-serif text-xl font-medium italic">Pilihan {{ $index + 1 }} · {{ $styles[$style] ?? ucfirst($style) }}</h2>
                        {{-- Simpan hasil rekomendasi sebagai outfit lewat endpoint POST /outfits yang sama --}}
                        @php
                            // Backend mengirim wardrobe_item_ids dan items[]; nama outfit dibuat dari style dan nomor pilihan
                            $ids = array_values(array_filter($outfit['wardrobe_item_ids'] ?? [], 'is_int'));
                        @endphp
                        @if (count($ids) >= 2)
                            <form action="{{ route('outfits.store') }}" method="POST" class="flex flex-col gap-3">
                                @csrf
                                @foreach ($ids as $id)
                                    <input type="hidden" name="wardrobe_item_ids[]" value="{{ $id }}">
                                @endforeach
                                <input type="hidden" name="name" value="{{ ($styles[$style] ?? ucfirst($style)).' pilihan '.($index + 1) }}">
                                <x-button label="Simpan sebagai Outfit" variant="secondary" />
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
