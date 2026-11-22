<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'StyloMate')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">

    {{-- Tailwind + Vite (PRD 8). Jika aset Vite belum dibuild/dijalankan (npm run build / composer run dev),
         dipakai Tailwind CDN sebagai cadangan agar halaman tetap bergaya saat pengembangan. --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    {{-- Alpine.js: interaktivitas Mix & Match dan Try-On. --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Roboto Mono', ui-monospace, SFMono-Regular, Menlo, monospace; }
        [x-cloak] { display: none !important; }
        /* Kelas ini diaktifkan Alpine (x-bind:class), jadi ditulis sebagai CSS biasa, bukan Tailwind. */
        .is-selected { background: #000; color: #fff; }
        .is-active { outline: 3px solid #000; outline-offset: 2px; }
        .notice { border: 1px solid #000; background: #f2f2f2; padding: .5rem .75rem; font-size: .875rem; }
        .notice-error { border-color: #b91c1c; background: #fef2f2; color: #7f1d1d; }
    </style>
</head>
<body class="flex min-h-screen flex-col bg-white text-black">
    @include('partials.navbar')

    @php($mocks = \App\Services\Fashion\Fashion::mockStatus())
    @if (count($mocks) > 0)
        {{-- Penanda dev: hilang otomatis bila semua mock dimatikan --}}
        <div class="flex items-center justify-center gap-3 bg-[#fff3b0] px-4 py-1 text-xs">
            <span>Mock mode: {{ implode(', ', $mocks) }}</span>
            @if (in_array('data', $mocks, true) || in_array('tryOn', $mocks, true))
                <form method="POST" action="{{ route('dev.reset') }}" onsubmit="return confirm('Delete all mock data and start over?')">
                    @csrf
                    <button type="submit" class="underline">Reset mock data</button>
                </form>
            @endif
        </div>
    @endif

    @if (session('status') || session('error'))
        <div class="mx-auto mt-4 w-full max-w-[1100px] px-6">
            @if (session('status'))
                <x-notice>{{ session('status') }}</x-notice>
            @endif
            @if (session('error'))
                <x-notice tone="error">{{ session('error') }}</x-notice>
            @endif
        </div>
    @endif

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="flex h-[93px] items-center justify-center bg-[#d4d4d4] px-4 text-center text-xs">
        © 2026 - StyloMate, Inc. All rights reserved. Address Address
    </footer>

    @stack('scripts')
</body>
</html>
