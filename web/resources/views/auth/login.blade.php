@extends('layouts.app')

@section('title', 'Masuk')
@section('description', 'Masuk ke akun StyloMate untuk membuka Wardrobe, outfit, dan hasil Try On yang sudah kamu simpan.')

@section('content')
    <div class="flex flex-wrap items-stretch gap-12">
        <div class="hidden min-h-[640px] min-w-0 flex-[1_1_480px] flex-col justify-between gap-8 rounded-lg bg-grad-vertical p-12 md:flex">
            <div class="flex max-w-[440px] flex-col gap-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-taupe">Selamat datang kembali</p>
                <p class="font-serif text-[28px] font-medium italic leading-9 tracking-[-0.01em] md:text-4xl md:leading-[44px]">Baju lama, look baru.</p>
                <p class="text-taupe">Lanjutkan dari lemari dan outfit yang sudah kamu simpan.</p>
            </div>
            <div class="flex items-end gap-4">
                <div class="flex aspect-[3/4] flex-1 items-center justify-center rounded-md bg-linen">
                    <img src="{{ asset('images/illustrations/sweater.svg') }}" alt="" width="300" height="400" class="w-[82%]">
                </div>
                <div class="flex aspect-[3/4] flex-[1.4] items-end justify-center overflow-hidden rounded-md bg-linen">
                    <img src="{{ asset('images/illustrations/try-on-dasar.svg') }}" alt="" width="300" height="400" class="w-[86%]">
                </div>
                <div class="flex aspect-[3/4] flex-1 items-center justify-center rounded-md bg-linen">
                    <img src="{{ asset('images/illustrations/jeans.svg') }}" alt="" width="300" height="400" class="w-[82%]">
                </div>
            </div>
        </div>

        <section aria-labelledby="judul-masuk" class="flex min-w-0 flex-[1_1_440px] items-center justify-center">
            {{-- Form login: @csrf wajib; data-loading menampilkan indikator saat dikirim --}}
            <form action="{{ route('login') }}" method="POST" data-loading class="flex w-full max-w-[460px] flex-col gap-6 rounded-lg bg-white p-8 shadow-xs md:p-10">
                @csrf
                <div class="flex flex-col gap-2">
                    <h1 id="judul-masuk" class="font-serif text-[32px] font-medium italic leading-10 tracking-[-0.015em] md:text-5xl md:leading-[56px]">Masuk</h1>
                    <p class="text-taupe">Masuk dengan email yang kamu daftarkan.</p>
                </div>

                @if ($errors->any())
                    <div role="alert" class="flex items-start gap-2.5 rounded-md border border-error bg-linen px-4 py-3 text-sm text-error">
                        <svg class="mt-0.5 flex-none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                        <span>Periksa lagi isian yang ditandai.</span>
                    </div>
                @endif

                <div class="flex flex-col gap-5">
                    <x-input name="email" label="Email" type="email" :required="true" autocomplete="email" placeholder="nama@email.com" />
                    <x-input name="password" label="Kata sandi" type="password" :required="true" autocomplete="current-password" placeholder="Kata sandi kamu" />
                </div>

                <div class="flex flex-col gap-3 *:w-full">
                    <x-button label="Masuk" />
                    <x-loading message="Sedang masuk…" :hidden="true" />
                </div>

                <p class="text-center text-sm text-taupe">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-charcoal hover:underline">Daftar</a></p>

                {{-- Info akun demo hanya muncul di mode mock --}}
                @if (config('services.fastapi.use_mock') && ! app()->isProduction())
                    <p class="rounded-md bg-rose-50 px-3 py-2 text-xs text-charcoal">Mode demo aktif. Akun contoh: {{ \App\Services\MockFastApiClient::DEMO_EMAIL }} / {{ \App\Services\MockFastApiClient::DEMO_PASSWORD }}</p>
                @endif
            </form>
        </section>
    </div>
@endsection
