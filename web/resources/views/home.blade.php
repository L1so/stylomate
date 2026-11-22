@extends('layouts.app')

@section('title', 'Sustainable fashion berbasis AI')
@section('description', 'Unggah fotomu, padukan isi lemarimu, lalu lihat hasilnya sebelum memutuskan. Coba pakaian secara virtual dengan StyloMate.')
@section('full_width', true)

@section('content')
    @php
        $isLoggedIn = session()->has('api_token');
        $container = 'mx-auto w-full max-w-7xl px-5 md:px-10 xl:px-0';
        $features = [
            ['Try On', 'Lihat dulu. Baru putuskan.', 'Unggah fotomu dan coba pakaian secara virtual sebelum membeli atau memakainya.', 'Coba Try On', 'try-on.index', 'try-on-dasar', 'try-on-hasil'],
            ['Smart Outfit Curation', 'Outfit sesuai gayamu.', 'Pilih gaya casual, formal, semi-formal, atau sporty, lalu dapatkan rekomendasi dari isi lemarimu.', 'Lihat Rekomendasi', 'recommendations.index', 'kaos', 'rok'],
            ['Mix & Match', 'Baju lama, look baru.', 'Padukan atasan dan bawahan dari lemarimu jadi outfit yang belum pernah kamu coba.', 'Mulai Mix & Match', 'mix-match.index', 'kemeja', 'kulot'],
            ['Wardrobe', 'Semua pakaianmu di satu tempat.', 'Foto pakaianmu, beri nama dan kategori, lalu temukan lagi kapan pun kamu butuh.', 'Buka Wardrobe', 'wardrobe.index', 'sweater', 'jeans'],
        ];
        $steps = [
            ['Simpan pakaianmu', 'Foto pakaian yang kamu punya dan simpan ke Wardrobe.'],
            ['Padukan jadi outfit', 'Pilih sendiri di Mix & Match, atau minta rekomendasi sesuai gayamu.'],
            ['Coba lewat Try On', 'Lihat outfit di fotomu sendiri, lalu simpan yang paling cocok.'],
        ];
    @endphp

    <section class="bg-linen pb-20 pt-16 md:pb-28 md:pt-24">
        <div class="{{ $container }} flex flex-wrap items-center gap-16">
            <div class="flex min-w-0 flex-[1_1_440px] justify-center">
                <div class="relative w-full max-w-[470px] pb-6 pr-6 md:pb-14 md:pr-14">
                    <div aria-hidden="true" class="absolute bottom-0 right-0 h-[84%] w-4/5 rounded-md bg-rose"></div>
                    <div class="relative flex aspect-[3/4] items-end justify-center overflow-hidden rounded-md bg-grad-photo">
                        <img src="{{ asset('images/illustrations/try-on-hasil.svg') }}" alt="Ilustrasi hasil Try On: figur memakai blazer dan celana kulot" width="300" height="400" class="w-[86%]">
                        <span class="absolute left-4 top-4 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-taupe">Ilustrasi</span>
                        <div class="absolute bottom-6 left-4 flex items-center gap-2.5 rounded-md bg-white px-4 py-3 shadow-md">
                            <svg class="text-sage" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
                            <span class="text-sm font-semibold">Hasil Try On siap</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex min-w-0 flex-[1_1_440px] flex-col items-center gap-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-terracotta">Sustainable fashion berbasis AI</p>
                <h1 class="font-serif text-[40px] font-normal italic leading-[44px] tracking-[-0.02em] md:text-[64px] md:leading-[72px] max-w-[520px]">Coba dulu, pakai lebih lama.</h1>
                <p class="text-lg leading-7 text-taupe max-w-[460px]">Unggah fotomu, padukan isi lemarimu, lalu lihat hasilnya sebelum memutuskan. Lebih yakin memilih, lebih jarang membeli.</p>
                {{-- Tombol Get Started (Mulai Sekarang), Masuk, Daftar sesuai PRD 6.1 --}}
                <div class="mt-2 flex flex-wrap justify-center gap-3">
                    <x-button label="Mulai Sekarang" :href="$isLoggedIn ? route('wardrobe.index') : route('register')" type="button" />
                    <x-button label="Lihat Catalog" :href="route('catalog.index')" variant="secondary" type="button" />
                </div>
                @unless ($isLoggedIn)
                    <p class="text-sm text-taupe">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-charcoal underline-offset-4 hover:underline">Masuk</a></p>
                @endunless
            </div>
        </div>
    </section>

    <section aria-label="Sorotan" class="on-dark bg-terracotta">
        <div class="{{ $container }} flex flex-wrap items-center justify-between gap-5 py-9">
            <p class="font-serif text-[28px] italic leading-9 text-linen md:text-[44px] md:leading-[52px]">baju lama, look baru.</p>
            <x-button label="Mulai Mix & Match" :href="route('mix-match.index')" variant="outline-light" type="button" />
        </div>
    </section>

    <section aria-labelledby="fitur-heading" class="bg-rose-50 py-16 md:py-24">
        <div class="{{ $container }}">
            <div class="flex flex-col gap-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-taupe">Fitur</p>
                <h2 id="fitur-heading" class="font-serif text-[28px] font-medium italic leading-9 tracking-[-0.01em] md:text-4xl md:leading-[44px]">Cara memakai lemarimu lebih lama.</h2>
            </div>
            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                @foreach ($features as [$over, $title, $desc, $cta, $routeName, $imgA, $imgB])
                    <article class="flex flex-col overflow-hidden rounded-md bg-white shadow-xs transition-shadow hover:shadow-sm">
                        <div class="flex h-44 items-end justify-center gap-2 bg-grad-photo px-6 pt-6">
                            <img src="{{ asset('images/illustrations/'.$imgA.'.svg') }}" alt="" width="300" height="400" loading="lazy" class="h-full w-auto">
                            <img src="{{ asset('images/illustrations/'.$imgB.'.svg') }}" alt="" width="300" height="400" loading="lazy" class="h-full w-auto">
                        </div>
                        <div class="flex flex-1 flex-col gap-3 p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-terracotta">{{ $over }}</p>
                            <h3 class="font-serif text-[22px] font-medium leading-[30px]">{{ $title }}</h3>
                            <p class="text-taupe">{{ $desc }}</p>
                            <div class="mt-auto pt-2">
                                <x-button :label="$cta" :href="route($routeName)" variant="ghost" type="button" />
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section aria-labelledby="cara-heading" class="bg-white py-16 md:py-24">
        <div class="{{ $container }}">
            <div class="flex flex-col gap-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-taupe">Cara kerja</p>
                <h2 id="cara-heading" class="font-serif text-[28px] font-medium italic leading-9 tracking-[-0.01em] md:text-4xl md:leading-[44px]">Lihat dulu. Baru putuskan.</h2>
            </div>
            <ol class="mt-10 grid gap-6 md:grid-cols-3">
                @foreach ($steps as $index => [$stepTitle, $stepDesc])
                    <li class="flex flex-col gap-4 rounded-md bg-linen p-8">
                        <span aria-hidden="true" class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-terracotta font-serif text-xl font-semibold text-linen">{{ $index + 1 }}</span>
                        <h3 class="font-serif text-[22px] font-medium leading-[30px]">{{ $stepTitle }}</h3>
                        <p class="text-taupe">{{ $stepDesc }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    @if (count($products) > 0)
        <section aria-labelledby="catalog-heading" class="bg-linen py-16 md:py-24">
            <div class="{{ $container }}">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="flex flex-col gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-terracotta">Dari Catalog</p>
                        <h2 id="catalog-heading" class="font-serif text-[28px] font-medium italic leading-9 tracking-[-0.01em] md:text-4xl md:leading-[44px]">Coba pakaian dari Catalog</h2>
                    </div>
                    <x-button label="Lihat Semua" :href="route('catalog.index')" variant="secondary" type="button" />
                </div>
                <div class="mt-10 grid grid-cols-2 gap-x-6 gap-y-10 md:grid-cols-3 lg:grid-cols-4">
                    @foreach ($products as $product)
                        <x-product-card :item="$product" :href="route('catalog.show', $product['id'])" :try-on-href="route('try-on.index', ['catalog_item_id' => $product['id']])" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="bg-linen pb-16 md:pb-24">
        <div class="{{ $container }}">
            <div class="on-dark flex flex-wrap items-center justify-between gap-8 rounded-lg bg-grad-cta px-8 py-14 md:px-12 md:py-[72px]">
                <div class="flex max-w-xl flex-col gap-3">
                    <h2 class="font-serif text-[28px] font-medium italic leading-9 tracking-[-0.01em] md:text-4xl md:leading-[44px] text-linen">Lemari yang sama, gaya yang baru.</h2>
                    <p class="text-lg leading-7 text-linen">Simpan isi lemarimu sekali, lalu biarkan kami bantu memadukannya.</p>
                </div>
                <x-button :label="$isLoggedIn ? 'Buka Wardrobe' : 'Buat Akun'" :href="$isLoggedIn ? route('wardrobe.index') : route('register')" variant="outline-light" type="button" />
            </div>
        </div>
    </section>
@endsection
