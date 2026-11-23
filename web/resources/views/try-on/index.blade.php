@extends('layouts.app')

@section('title', 'Try On')
@section('noindex', true)

@section('content')
    @php
    // Satu grup radio "item" memastikan hanya satu sumber yang dikirim: outfit, wardrobe, atau catalog.
    $sources = [
        'wardrobe' => ['label' => 'Pakaian dari Wardrobe', 'items' => $wardrobeItems, 'empty' => 'Belum ada atasan atau bawahan di Wardrobe.'],
        'catalog' => ['label' => 'Pakaian dari Catalog', 'items' => $catalogItems, 'empty' => 'Belum ada atasan atau bawahan di Catalog.'],
    ];
@endphp

    <div class="flex flex-col gap-8">
        <div class="flex flex-col gap-2">
            <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Try On</h1>
            <p class="max-w-3xl text-taupe">Pilih outfit hasil Mix &amp; Match, atau satu atasan maupun bawahan. Untuk outfit, AI menerapkan atasan terlebih dahulu lalu bawahan pada hasil tahap pertama.</p>
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
                            <input type="hidden" name="generation_id" value="{{ $result['generation_id'] ?? $result['id'] }}">
                            <x-button label="Simpan Hasil" />
                        </form>
                        <x-button label="Coba Lagi" :href="route('try-on.index')" variant="secondary" type="button" />
                    </div>
                </div>
            </section>
        @endif

        <form action="{{ route('try-on.generate') }}" method="POST" enctype="multipart/form-data" data-loading class="flex flex-col gap-8" novalidate>
            @csrf

            <fieldset class="flex flex-col gap-4">
                <legend class="font-serif text-2xl font-medium italic">Outfit dari Mix &amp; Match</legend>
                <div class="flex justify-end">
                    <a href="{{ route('mix-match.index') }}" class="text-sm font-semibold text-terracotta underline">Buat outfit baru</a>
                </div>
                @if (count($outfits) === 0)
                    <p class="text-sm text-taupe">Belum ada outfit dengan tepat satu atasan dan satu bawahan.</p>
                @else
                    <ul class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($outfits as $outfit)
                            @php $value = 'outfit:'.$outfit['id']; @endphp
                            <li>
                                <label class="relative block h-full cursor-pointer">
                                    <input type="radio" name="item" value="{{ $value }}" class="peer sr-only" @checked($selected === $value)>
                                    <span class="flex h-full flex-col overflow-hidden rounded-md bg-white p-3 shadow-xs peer-checked:ring-2 peer-checked:ring-terracotta peer-focus-visible:ring-2 peer-focus-visible:ring-charcoal">
                                        <span class="flex flex-col overflow-hidden rounded-sm bg-grad-photo">
                                            @foreach ($outfit['try_on_items'] as $item)
                                                <span class="flex aspect-[4/3] items-center justify-center overflow-hidden bg-white/70">
                                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['category_label'] ?? 'Pakaian' }} untuk outfit {{ $outfit['name'] ?? '' }}" width="300" height="225" loading="lazy" class="h-full w-full object-contain">
                                                </span>
                                            @endforeach
                                        </span>
                                        <strong class="mt-3 font-serif text-lg">{{ $outfit['name'] ?? 'Outfit' }}</strong>
                                        <span class="text-xs text-taupe">Atasan + bawahan</span>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </fieldset>

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
                <x-file-input name="person_image" label="Foto seluruh badan" :required="true" hint="Gunakan foto berdiri menghadap depan dengan tubuh dan kaki terlihat jelas. JPEG atau PNG, maksimal 10 MB." />
                <div class="flex flex-wrap items-center gap-3">
                    <x-button label="Buat Preview" />
                    <x-loading message="AI sedang membuat preview. Outfit penuh diproses dalam dua tahap dan dapat memakan waktu beberapa menit. Jangan tutup halaman ini." :hidden="true" />
                </div>
            </div>
        </form>
    </div>
@endsection
