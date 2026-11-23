@extends('layouts.app')

@section('title', 'Profil')
@section('noindex', true)

@section('content')
    <div class="flex flex-col gap-10">
        <section aria-labelledby="pf-nama" class="flex flex-wrap items-center justify-between gap-6 rounded-lg bg-grad-soft p-8 md:p-10">
            <div class="flex flex-wrap items-center gap-6">
                <span class="flex h-24 w-24 items-center justify-center rounded-full bg-white text-rose-700 ring-2 ring-terracotta">
                    <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="5"></circle><path d="M20 21a8 8 0 0 0-16 0"></path></svg>
                </span>
                <div class="flex flex-col gap-1">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-terracotta">Profil</p>
                    <h1 id="pf-nama" class="font-serif text-[32px] font-medium italic leading-10 tracking-[-0.015em] md:text-5xl md:leading-[56px]">{{ $user['name'] ?? '-' }}</h1>
                    <span class="text-taupe">{{ $user['email'] ?? '-' }}</span>
                    @if (!empty($user['created_at']))
                        <span class="text-sm text-taupe">Bergabung sejak <x-date :value="$user['created_at']" /></span>
                    @endif
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                {{-- Logout wajib POST + @csrf (PAWL P7) --}}
                @csrf
                <x-button label="Keluar" variant="secondary" />
            </form>
        </section>

        <section aria-label="Koleksi tersimpan" class="flex flex-col gap-6">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-rose">
                {{-- Tab lewat query ?tab= supaya tanpa JavaScript --}}
                <nav aria-label="Koleksi" class="flex gap-8">
                    <a href="{{ route('profile.index') }}" @class(['-mb-px inline-flex min-h-14 items-baseline gap-2 border-b-2 font-serif text-2xl font-medium italic', 'border-terracotta text-charcoal' => $tab === 'outfit', 'border-transparent text-taupe hover:text-charcoal' => $tab !== 'outfit']) @if ($tab === 'outfit') aria-current="page" @endif>Outfit tersimpan<span class="font-sans text-sm font-semibold not-italic text-taupe">{{ count($outfits) }}</span></a>
                    <a href="{{ route('profile.index', ['tab' => 'try-on']) }}" @class(['-mb-px inline-flex min-h-14 items-baseline gap-2 border-b-2 font-serif text-2xl font-medium italic', 'border-terracotta text-charcoal' => $tab === 'try-on', 'border-transparent text-taupe hover:text-charcoal' => $tab !== 'try-on']) @if ($tab === 'try-on') aria-current="page" @endif>Try On tersimpan<span class="font-sans text-sm font-semibold not-italic text-taupe">{{ count($tryOns) }}</span></a>
                </nav>
                @if ($tab === 'outfit')
                    <x-button label="+ Buat Outfit" :href="route('mix-match.index')" variant="ghost" type="button" />
                @else
                    <x-button label="+ Try On Baru" :href="route('try-on.index')" variant="ghost" type="button" />
                @endif
            </div>

            @if ($tab === 'outfit')
                @if (count($outfits) > 0)
                    <ul class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($outfits as $outfit)
                            <li class="flex flex-col gap-3 rounded-md bg-white p-3 shadow-xs">
                                <div class="flex flex-col overflow-hidden rounded-sm bg-grad-photo">
                                    {{-- Outfit hanya membawa wardrobe_item_ids; gambar diambil lewat proxy /media/wardrobe/{id} --}}
                                    @forelse ($outfit['wardrobe_images'] ?? [] as $imageUrl)
                                        <div class="flex aspect-[4/3] items-center justify-center overflow-hidden">
                                            <img src="{{ $imageUrl }}" alt="Pakaian {{ $loop->iteration }} dari outfit {{ $outfit['name'] ?? '' }}" width="300" height="225" loading="lazy" class="h-full w-full bg-white/70 object-contain">
                                        </div>
                                    @empty
                                        <div class="flex aspect-[4/3] items-center justify-center">
                                            <span class="text-xs text-taupe">Belum ada foto</span>
                                        </div>
                                    @endforelse
                                </div>
                                <div class="flex flex-col gap-0.5 px-1">
                                    <h3 class="font-serif text-lg font-medium leading-[26px]">{{ $outfit['name'] ?? 'Outfit tanpa nama' }}</h3>
                                    <span class="text-[13px] leading-[18px] text-taupe">{{ count($outfit['wardrobe_item_ids'] ?? []) }} pakaian</span>
                                    @if (!empty($outfit['created_at']))
                                        <span class="text-xs text-taupe">Disimpan <x-date :value="$outfit['created_at']" /></span>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2 px-1 pb-1">
                                    <x-button label="Try On" :href="route('try-on.index', ['outfit_id' => $outfit['id']])" type="button" />
                                    <x-confirm-delete :action="route('outfits.destroy', $outfit['id'])" message="Hapus outfit ini? Tindakan ini tidak bisa dibatalkan." />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-empty-state title="Belum ada outfit tersimpan" message="Buat outfit di Mix & Match atau simpan dari Rekomendasi." action-label="Buka Mix & Match" :action-url="route('mix-match.index')" />
                @endif
            @else
                {{-- Gambar hasil Try On lewat proxy /media/try-on/{id}, bukan URL FastAPI langsung --}}
                @if (count($tryOns) > 0)
                    <ul class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                        @foreach ($tryOns as $tryOn)
                            <li class="flex flex-col gap-3 rounded-md bg-white p-3 shadow-xs">
                                <div class="flex aspect-[3/4] items-end justify-center overflow-hidden rounded-sm bg-grad-photo">
                                    @if (!empty($tryOn['image_url']))
                                        <img src="{{ $tryOn['image_url'] }}" alt="Hasil Try On" width="300" height="400" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="text-xs text-taupe">Belum ada foto</span>
                                    @endif
                                </div>
                                <div class="flex flex-col gap-0.5 px-1">
                                    <h3 class="font-serif text-lg font-medium leading-[26px]">Hasil Try On</h3>
                                    <span class="text-[13px] text-taupe">
                                        @if (!empty($tryOn['catalog_item_id']))
                                            Dari Catalog
                                        @elseif (!empty($tryOn['wardrobe_item_id']))
                                            Dari Wardrobe
                                        @elseif (!empty($tryOn['outfit_id']))
                                            Dari Mix &amp; Match
                                        @else
                                            Sumber tidak diketahui
                                        @endif
                                        @if (!empty($tryOn['created_at']))
                                            · <x-date :value="$tryOn['created_at']" />
                                        @endif
                                    </span>
                                </div>
                                <div class="flex flex-wrap gap-2 px-1 pb-1">
                                    @if (!empty($tryOn['download_url']))
                                        <x-button label="Unduh" :href="$tryOn['download_url']" variant="secondary" type="button" />
                                    @endif
                                    <x-confirm-delete :action="route('try-on.destroy', $tryOn['id'])" message="Hapus hasil Try On ini? Tindakan ini tidak bisa dibatalkan." />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <x-empty-state title="Belum ada hasil Try On" message="Coba pakaian dari Catalog, lalu simpan hasilnya supaya muncul di sini." action-label="Lihat Catalog" :action-url="route('catalog.index')" />
                @endif
            @endif
        </section>
    </div>
@endsection
