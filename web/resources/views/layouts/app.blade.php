<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if (session()->has('api_token') && config('services.fastapi.ai_warmup') && ! config('services.fastapi.use_mock'))
        <meta name="ai-warmup-url" content="{{ route('try-on.warmup', absolute: false) }}">
    @endif
    <title>@hasSection('title')@yield('title') · @endif StyloMate</title>
    <meta name="description" content="@yield('description', 'StyloMate membantu kamu mencoba pakaian secara virtual, memadukan isi lemari, dan mendapat rekomendasi outfit.')">
    {{-- Halaman privat memakai @section('noindex', true) agar tidak diindex mesin pencari --}}
    @hasSection('noindex')
        <meta name="robots" content="noindex, nofollow">
    @else
        <link rel="canonical" href="@yield('canonical', url()->current())">
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="StyloMate">
        <meta property="og:title" content="@yield('title', 'StyloMate')">
        <meta property="og:description" content="@yield('description', 'StyloMate membantu kamu mencoba pakaian secara virtual, memadukan isi lemari, dan mendapat rekomendasi outfit.')">
        <meta property="og:url" content="@yield('canonical', url()->current())">
        <meta name="twitter:card" content="summary">
    @endif
    {{-- Font dari design system: Fraunces (judul) dan Plus Jakarta Sans (teks) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;1,9..144,400;1,9..144,500&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-linen font-sans text-charcoal antialiased">
    @php
        // Sudah login = ada JWT dari FastAPI di session (padanan @auth di PAWL P7)
        $isLoggedIn = session()->has('api_token');
        // Navbar 3 kolom sesuai design system: Catalog, Wardrobe | logo | Mix & Match, Try On
        $leftNav = [['catalog.index', 'Catalog', 'catalog.*'], ['wardrobe.index', 'Wardrobe', 'wardrobe.*']];
        $rightNav = [['mix-match.index', 'Mix & Match', 'mix-match.*'], ['try-on.index', 'Try On', 'try-on.*']];
        $container = 'mx-auto w-full max-w-7xl px-5 md:px-10 xl:px-0';
    @endphp

    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-sm focus:bg-white focus:px-3 focus:py-2">Lewati ke konten</a>

    <header class="border-b border-rose bg-linen">
        <nav aria-label="Navigasi utama" class="{{ $container }} grid h-16 grid-cols-[1fr_auto_1fr] items-center gap-6 md:h-20">
            <div class="flex items-center">
                {{-- Menu mobile memakai <details> supaya tetap jalan tanpa JavaScript --}}
                <details class="relative md:hidden">
                    <summary aria-label="Buka menu" class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-full hover:bg-rose-50 [&::-webkit-details-marker]:hidden">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </summary>
                    <ul class="absolute left-0 top-12 z-20 w-60 rounded-md bg-white p-2 shadow-md">
                        @foreach (array_merge($leftNav, $rightNav) as [$routeName, $navLabel, $activePattern])
                            <li><a href="{{ route($routeName) }}" @class(['block rounded-sm px-3 py-3 text-sm font-semibold hover:bg-rose-50', 'text-terracotta' => request()->routeIs($activePattern)]) @if (request()->routeIs($activePattern)) aria-current="page" @endif>{{ $navLabel }}</a></li>
                        @endforeach
                        @if ($isLoggedIn)
                            <li><a href="{{ route('profile.index') }}" class="block rounded-sm px-3 py-3 text-sm font-semibold hover:bg-rose-50">Profil</a></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="block w-full rounded-sm px-3 py-3 text-left text-sm font-semibold hover:bg-rose-50">Keluar</button>
                                </form>
                            </li>
                        @endif
                    </ul>
                </details>
                <ul class="hidden items-center gap-10 md:flex">
                    @foreach ($leftNav as [$routeName, $navLabel, $activePattern])
                        <li><a href="{{ route($routeName) }}" @class(['block border-b-2 py-7 text-[13px] font-semibold uppercase leading-4 tracking-[0.12em] transition-colors hover:text-terracotta', 'border-terracotta' => request()->routeIs($activePattern), 'border-transparent' => ! request()->routeIs($activePattern)]) @if (request()->routeIs($activePattern)) aria-current="page" @endif>{{ $navLabel }}</a></li>
                    @endforeach
                </ul>
            </div>

            <a href="{{ route('home') }}" class="font-serif text-[28px] font-medium italic leading-10 tracking-[-0.01em] md:text-[32px]">StyloMate</a>

            <div class="flex items-center justify-end gap-10">
                <ul class="hidden items-center gap-10 md:flex">
                    @foreach ($rightNav as [$routeName, $navLabel, $activePattern])
                        <li><a href="{{ route($routeName) }}" @class(['block border-b-2 py-7 text-[13px] font-semibold uppercase leading-4 tracking-[0.12em] transition-colors hover:text-terracotta', 'border-terracotta' => request()->routeIs($activePattern), 'border-transparent' => ! request()->routeIs($activePattern)]) @if (request()->routeIs($activePattern)) aria-current="page" @endif>{{ $navLabel }}</a></li>
                    @endforeach
                </ul>
                @if ($isLoggedIn)
                    <a href="{{ route('profile.index') }}" aria-label="Profil" @class(['inline-flex h-11 w-11 items-center justify-center rounded-full bg-rose-50', 'ring-2 ring-terracotta' => request()->routeIs('profile.*')]) @if (request()->routeIs('profile.*')) aria-current="page" @endif>
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"></circle><path d="M20 21a8 8 0 0 0-16 0"></path></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center text-sm font-semibold hover:text-terracotta">Masuk</a>
                @endif
            </div>
        </nav>
    </header>

    {{-- Flash message dari redirect()->with() (pola PRG, PAWL P4); selalu ada ikon + teks, bukan warna saja --}}
    @if (session('success') || session('error'))
        <div class="{{ $container }} mt-6 flex flex-col gap-3">
            @if (session('success'))
                <div role="status" class="flex items-start gap-3 rounded-md bg-white px-4 py-3 text-sm shadow-xs">
                    <svg class="mt-0.5 flex-none text-sage" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div role="alert" class="flex items-start gap-3 rounded-md bg-white px-4 py-3 text-sm text-error shadow-xs">
                    <svg class="mt-0.5 flex-none" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
        </div>
    @endif

    {{-- Halaman dengan section selebar layar (misalnya Homepage) memakai @section('full_width', true) --}}
    @hasSection('full_width')
        <main id="konten" class="flex-1">@yield('content')</main>
    @else
        <main id="konten" class="{{ $container }} flex-1 py-10 md:py-14">@yield('content')</main>
    @endif

    <footer class="on-dark bg-charcoal text-linen">
        <div class="{{ $container }} flex flex-col gap-8 pb-10 pt-14">
            <div class="flex flex-wrap justify-between gap-8">
                <div class="flex max-w-sm flex-col gap-3">
                    <span class="font-serif text-[28px] font-medium italic">StyloMate</span>
                    <p class="text-sm text-rose">Coba dulu, pakai lebih lama. Kurasi gaya berkelanjutan untuk mahasiswa dan dewasa muda.</p>
                </div>
                <nav aria-label="Footer" class="flex flex-wrap gap-16">
                    <div class="flex flex-col gap-3">
                        <span class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Fitur</span>
                        @foreach (array_merge($leftNav, $rightNav) as [$routeName, $navLabel])
                            <a href="{{ route($routeName) }}" class="text-sm hover:underline">{{ $navLabel }}</a>
                        @endforeach
                    </div>
                    <div class="flex flex-col gap-3">
                        <span class="text-xs font-semibold uppercase tracking-[0.12em] text-rose">Akun</span>
                        @if ($isLoggedIn)
                            <a href="{{ route('profile.index') }}" class="text-sm hover:underline">Profil</a>
                            {{-- Logout wajib POST + @csrf (PAWL P7) --}}
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="text-sm hover:underline">Keluar</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="text-sm hover:underline">Masuk</a>
                            <a href="{{ route('register') }}" class="text-sm hover:underline">Daftar</a>
                        @endif
                    </div>
                </nav>
            </div>
            <div class="flex flex-wrap justify-between gap-3 border-t border-taupe pt-6 text-xs text-rose">
                <span>&copy; {{ date('Y') }} StyloMate. Hak cipta dilindungi.</span>
                <span>Produk di Catalog adalah data contoh, bukan dari mitra resmi.</span>
            </div>
        </div>
    </footer>
</body>
</html>
