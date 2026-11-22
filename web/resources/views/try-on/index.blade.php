@extends('layouts.app')

@section('title', 'Try On')
@section('noindex', true)

@section('content')
    @php
    // Satu grup radio "item" berisi "wardrobe:ID" atau "catalog:ID" supaya hanya satu item yang terkirim
    $sources = [
        'wardrobe' => ['label' => 'Dari Wardrobe', 'items' => $wardrobeTops, 'empty' => 'Belum ada atasan di Wardrobe.'],
        'catalog' => ['label' => 'Dari Catalog', 'items' => $catalogTops, 'empty' => 'Belum ada atasan di Catalog.'],
    ];
@endphp

    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Try On</h1>
            <p class="text-taupe">Pilih satu atasan, unggah fotomu, lalu lihat preview-nya. Saat ini Try On baru mendukung atasan.</p>
        </div>

        @if ($notice)
            <div role="alert" class="flex items-start gap-3 rounded-md bg-white px-4 py-3 text-sm text-error shadow-xs"><span>{{ $notice }}</span></div>
        @endif

        {{-- Semua error (termasuk error FastAPI untuk field tanpa input sendiri) dirangkum di atas form --}}
        @if ($errors->any())
            <div role="alert" class="flex flex-col gap-1 rounded-md bg-white px-4 py-3 text-sm text-error shadow-xs">
                <strong>Periksa lagi isian kamu:</strong>
                <ul class="list-disc pl-5">
                    @foreach (array_unique($errors->all()) as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($result)
            <section aria-labelledby="hasil-try-on" class="grid items-start gap-8 rounded-lg bg-white p-6 shadow-xs md:grid-cols-[minmax(0,360px)_1fr] md:p-8">
                <img src="{{ $result['image_url'] }}" alt="Hasil Try On" width="360" height="480" class="aspect-[3/4] w-full rounded-md bg-grad-photo object-cover">
                <div class="flex flex-col gap-4">
                    <h2 id="hasil-try-on" class="font-serif text-[28px] font-medium italic leading-9">Hasil Try On</h2>
                    <p class="text-taupe">Preview ini belum tersimpan. Simpan ke profil bila kamu suka, atau coba lagi dengan foto atau pakaian lain.</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <form action="{{ route('try-on.save') }}" method="POST">
                            @csrf
                            <input type="hidden" name="generation_id" value="{{ $result['id'] }}">
                            <x-button label="Simpan Hasil" />
                        </form>
                        <x-button label="Coba Lagi" :href="route('try-on.index')" variant="secondary" type="button" />
                    </div>
                </div>
            </section>
        @endif

        <form action="{{ route('try-on.generate') }}" method="POST" enctype="multipart/form-data" data-loading class="flex flex-col gap-8" novalidate>
            @csrf

            @foreach ($sources as $source => $group)
                <fieldset class="flex flex-col gap-4">
                    <legend class="font-serif text-2xl font-medium italic">{{ $group['label'] }}</legend>
                    @if (count($group['items']) === 0)
                        <p class="text-sm text-taupe">{{ $group['empty'] }}@if ($source === 'wardrobe') <a href="{{ route('wardrobe.create') }}" class="font-semibold text-terracotta underline">Tambah pakaian</a>@endif</p>
                    @else
                        <ul class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                            @foreach ($group['items'] as $item)
                                @php $value = $source.':'.$item['id']; @endphp
                                <li>
                                    <label class="relative block cursor-pointer">
                                        <input type="radio" name="item" value="{{ $value }}" class="peer sr-only" @checked($selected === $value)>
                                        <span class="block rounded-md peer-checked:ring-2 peer-checked:ring-terracotta peer-focus-visible:ring-2 peer-focus-visible:ring-charcoal">
                                            <x-clothing-card :item="$item" />
                                        </span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </fieldset>
            @endforeach
            <x-field-error name="item" />

            <div class="flex max-w-xl flex-col gap-6 rounded-lg bg-white p-6 shadow-xs md:p-8">
                <x-file-input name="person_image" label="Foto kamu" :required="true" hint="Foto berdiri menghadap depan. JPEG atau PNG, maksimal 10 MB." />
                <div class="flex flex-wrap items-center gap-3">
                    <x-button label="Buat Preview" />
                    <x-loading message="AI sedang membuat preview. Ini bisa memakan waktu hingga 1–2 menit, jangan tutup halaman ini." :hidden="true" />
                </div>
            </div>
        </form>
    </div>
@endsection
