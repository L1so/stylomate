@extends('layouts.app')

@section('title', 'Daftar')
@section('description', 'Buat akun StyloMate untuk menyimpan pakaian, outfit, dan hasil Try On kamu.')

@section('content')
    <div class="flex flex-wrap items-stretch gap-12">
        <div class="hidden min-h-[640px] min-w-0 flex-[1_1_480px] flex-col justify-between gap-8 rounded-lg bg-grad-vertical p-12 md:flex">
            <div class="flex max-w-[440px] flex-col gap-3">
                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-taupe">Akun StyloMate</p>
                <p class="font-serif text-[28px] font-medium italic leading-9 tracking-[-0.01em] md:text-4xl md:leading-[44px]">Lemari digitalmu, tersimpan aman.</p>
                <p class="text-taupe">Satu akun untuk menyimpan pakaian, outfit, dan hasil Try On kamu.</p>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="flex aspect-[3/4] items-center justify-center rounded-md bg-linen">
                    <img src="{{ asset('images/illustrations/kemeja.svg') }}" alt="" width="300" height="400" class="w-[84%]">
                </div>
                <div class="-translate-y-6 flex aspect-[3/4] items-center justify-center rounded-md bg-linen">
                    <img src="{{ asset('images/illustrations/try-on-hasil.svg') }}" alt="" width="300" height="400" class="w-[84%]">
                </div>
                <div class="flex aspect-[3/4] items-center justify-center rounded-md bg-linen">
                    <img src="{{ asset('images/illustrations/kulot.svg') }}" alt="" width="300" height="400" class="w-[84%]">
                </div>
            </div>
        </div>

        <section aria-labelledby="judul-daftar" class="flex min-w-0 flex-[1_1_440px] items-center justify-center">
            {{-- Form registrasi: nama, email, kata sandi + konfirmasi (rule confirmed) --}}
            <form action="{{ route('register') }}" method="POST" data-loading class="flex w-full max-w-[460px] flex-col gap-6 rounded-lg bg-white p-8 shadow-xs md:p-10">
                @csrf
                <div class="flex flex-col gap-2">
                    <h1 id="judul-daftar" class="font-serif text-[32px] font-medium italic leading-10 tracking-[-0.015em] md:text-5xl md:leading-[56px]">Buat akun</h1>
                    <p class="text-taupe">Isi data di bawah untuk mulai menyimpan lemarimu.</p>
                </div>

                @if ($errors->any())
                    <div role="alert" class="flex items-start gap-2.5 rounded-md border border-error bg-linen px-4 py-3 text-sm text-error">
                        <svg class="mt-0.5 flex-none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                        <span>Periksa lagi isian yang ditandai.</span>
                    </div>
                @endif

                <div class="flex flex-col gap-5">
                    <x-input name="name" label="Nama lengkap" :required="true" autocomplete="name" placeholder="Nama kamu" />
                    <x-input name="email" label="Email" type="email" :required="true" autocomplete="email" placeholder="nama@email.com" />
                    <x-input name="password" label="Kata sandi" type="password" :required="true" autocomplete="new-password" placeholder="Minimal 8 karakter" hint="Gabungkan huruf dan angka." />
                    <x-input name="password_confirmation" label="Ulangi kata sandi" type="password" :required="true" autocomplete="new-password" placeholder="Ketik ulang kata sandi" />
                </div>

                <div class="flex flex-col gap-3 *:w-full">
                    <x-button label="Buat Akun" />
                    <x-loading message="Sedang membuat akun…" :hidden="true" />
                </div>

                <p class="text-center text-sm text-taupe">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-charcoal hover:underline">Masuk</a></p>
            </form>
        </section>
    </div>
@endsection
