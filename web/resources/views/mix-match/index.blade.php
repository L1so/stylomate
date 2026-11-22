@extends('layouts.app')

@section('title', 'Mix & Match')
@section('noindex', true)

@section('content')
    <div class="flex flex-col gap-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-2">
                <h1 class="font-serif text-[32px] italic leading-10 md:text-5xl md:leading-[56px]">Mix &amp; Match</h1>
                <p class="text-taupe">Pilih minimal satu atasan dan satu bawahan dari Wardrobe, lalu simpan sebagai outfit.</p>
            </div>
            <x-button label="Lihat Rekomendasi" :href="route('recommendations.index')" variant="secondary" type="button" />
        </div>

        @if ($totalCount === 0)
            <x-empty-state title="Wardrobe kamu masih kosong" message="Tambahkan atasan dan bawahan dulu supaya bisa dipadukan." action-label="Tambah Pakaian" :action-url="route('wardrobe.create')" />
        @else
            <form action="{{ route('outfits.store') }}" method="POST" class="flex flex-col gap-8" novalidate>
                @csrf

                {{-- Error pilihan (kurang atasan/bawahan) dari Laravel atau FastAPI --}}
                @if ($errors->has('wardrobe_item_ids') || $errors->has('wardrobe_item_ids.*'))
                    <div role="alert" class="flex items-start gap-3 rounded-md bg-white px-4 py-3 text-sm text-error shadow-xs">
                        <svg class="mt-0.5 flex-none" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
                        <span>{{ $errors->first('wardrobe_item_ids') ?: $errors->first('wardrobe_item_ids.*') }}</span>
                    </div>
                @endif

                @foreach ($groups as $key => $group)
                    <fieldset class="flex flex-col gap-4">
                        <legend class="font-serif text-2xl font-medium italic">{{ $group['label'] }}</legend>
                        <ul class="grid grid-cols-2 gap-6 md:grid-cols-3 lg:grid-cols-4">
                            @foreach ($group['items'] as $item)
                                <li>
                                    {{-- Checkbox disembunyikan secara visual; kartu menjadi area klik dan menampilkan ring saat dipilih --}}
                                    <label class="relative block cursor-pointer">
                                        <input type="checkbox" name="wardrobe_item_ids[]" value="{{ $item['id'] }}" class="peer sr-only" @checked(in_array((string) $item['id'], array_map('strval', (array) old('wardrobe_item_ids', [])), true))>
                                        <span class="block rounded-md peer-checked:ring-2 peer-checked:ring-terracotta peer-focus-visible:ring-2 peer-focus-visible:ring-charcoal">
                                            <x-clothing-card :item="$item" />
                                        </span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </fieldset>
                @endforeach

                <div class="flex max-w-xl flex-col gap-6 rounded-lg bg-white p-6 shadow-xs md:p-8">
                    <x-input name="name" label="Nama outfit" :required="true" placeholder="Contoh: Santai ke Kampus" />
                    <div class="flex flex-wrap items-center gap-3">
                        <x-button label="Simpan Outfit" />
                        <x-button label="Batal" :href="route('wardrobe.index')" variant="ghost" type="button" />
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection
