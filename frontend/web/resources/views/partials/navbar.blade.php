{{-- Navbar sesuai wireframe. STUB milik anggota 2: ganti saat digabung. --}}
@php
    $links = [
        ['route' => 'catalog',        'match' => 'catalog',    'label' => 'Catalog'],
        ['route' => 'wardrobe.index', 'match' => 'wardrobe.*', 'label' => 'Wardrobe'],
        ['route' => 'mixmatch',       'match' => 'mixmatch*',  'label' => 'Mix & Match'],
        ['route' => 'tryon',          'match' => 'tryon*',     'label' => 'Try on'],
    ];
@endphp
<header class="flex h-[70px] items-center justify-between bg-[#616161] px-6 text-[15px] text-white lg:px-[30px]">
    <a href="{{ route('home') }}" class="border border-[#444] bg-[#e0e0e0] px-6 py-1 text-sm font-bold text-black">StyloMate</a>
    <nav aria-label="Main" class="flex items-center gap-6 lg:gap-[44px]">
        @foreach ($links as $link)
            <a href="{{ route($link['route']) }}" class="{{ request()->routeIs($link['match']) ? 'font-bold' : '' }}">{{ $link['label'] }}</a>
        @endforeach
        @if (session('access_token'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm underline">Logout</button>
            </form>
        @elseif (!in_array('data', \App\Services\Fashion\Fashion::mockStatus(), true))
            <a href="{{ route('login') }}" class="text-sm underline">Login</a>
        @endif
        <a href="{{ route('profile') }}" aria-label="Profile" class="relative block h-[30px] w-[30px] border border-[#444] bg-[#e0e0e0]">
            <svg class="absolute inset-0 h-full w-full" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                <line x1="0" y1="0" x2="100" y2="100" stroke="#444" stroke-width="1" vector-effect="non-scaling-stroke"/>
                <line x1="100" y1="0" x2="0" y2="100" stroke="#444" stroke-width="1" vector-effect="non-scaling-stroke"/>
            </svg>
        </a>
    </nav>
</header>
