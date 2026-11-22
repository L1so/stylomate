@extends('layouts.app')
@section('title', 'Mix & Match · StyloMate')

{{-- Wireframe "Mix & Match.png": daftar Wardrobe (kiri), pratinjau (tengah), panel Outfit (kanan).
     Interaksi dijalankan Alpine.js: lihat fungsi mixMatch() di bagian bawah. --}}
@section('content')
@php
    $options = \App\Support\FashionOptions::class;
    $config = [
        'candidates'     => $candidates,
        'preselect'      => $preselect,
        'notice'         => $notice,
        'csrf'           => csrf_token(),
        'urls'           => ['suggestions' => route('recommendations'), 'save' => route('outfits.store')],
        'styleLabels'    => array_column($options::STYLES, 'label', 'value'),
        'categoryLabels' => array_column($options::CATEGORIES, 'label', 'value'),
    ];
@endphp
<div class="mx-auto grid max-w-[1440px] gap-10 px-6 py-12 lg:grid-cols-[300px_minmax(0,466px)_346px] lg:justify-between lg:px-[69px] lg:py-[90px]"
     x-data="mixMatch(@js($config))">

    {{-- Kiri: daftar atasan & bawahan dari wardrobe --}}
    <section>
        <x-label>Wardrobe</x-label>
        <div class="mt-[26px] h-[410px] overflow-y-auto border-[3px] border-black p-4">
            <p x-show="candidates.length === 0" x-cloak class="notice">
                No tops or bottoms yet. <a href="{{ route('wardrobe.create') }}" class="underline">Add Item</a>
            </p>
            <ul class="flex flex-col gap-3">
                <template x-for="item in candidates" x-bind:key="item.id">
                    <li>
                        <button type="button" x-on:click="toggle(item)" x-bind:aria-pressed="isSelected(item)"
                                x-bind:class="{ 'is-selected': isSelected(item) }"
                                class="flex w-full items-center gap-3 p-2 text-left text-sm">
                            <span class="relative h-12 w-12 shrink-0 overflow-hidden border border-black bg-white">
                                <img x-bind:src="item.imageUrl" alt="" class="absolute inset-0 h-full w-full object-contain">
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate font-bold" x-text="item.name"></span>
                                <span class="block text-xs" x-text="categoryLabel(item.category)"></span>
                            </span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </section>

    {{-- Tengah: pratinjau atasan & bawahan berdampingan (PRD 6.5) --}}
    <section aria-label="Outfit preview">
        <div class="grid aspect-square w-full grid-cols-2 border border-black">
            <div class="relative overflow-hidden border-r border-black bg-[#e0e0e0]">
                <img x-show="selection.top" x-cloak x-bind:src="selection.top ? selection.top.imageUrl : null" alt="" class="absolute inset-0 h-full w-full object-contain">
                <div x-show="!selection.top">@include('partials.placeholder-x')</div>
                <span x-show="selection.top" x-cloak x-text="selection.top ? selection.top.name : ''" class="absolute inset-x-0 bottom-0 truncate bg-black/80 px-2 py-1 text-xs text-white"></span>
            </div>
            <div class="relative overflow-hidden bg-[#e0e0e0]">
                <img x-show="selection.bottom" x-cloak x-bind:src="selection.bottom ? selection.bottom.imageUrl : null" alt="" class="absolute inset-0 h-full w-full object-contain">
                <div x-show="!selection.bottom">@include('partials.placeholder-x')</div>
                <span x-show="selection.bottom" x-cloak x-text="selection.bottom ? selection.bottom.name : ''" class="absolute inset-x-0 bottom-0 truncate bg-black/80 px-2 py-1 text-xs text-white"></span>
            </div>
        </div>
    </section>

    {{-- Kanan: nama, style, tombol --}}
    <section class="flex flex-col gap-5">
        <x-label>Outfit</x-label>
        <input type="text" x-model="name" maxlength="60" placeholder="Outfit name" aria-label="Outfit name"
               class="h-11 w-full border border-black bg-white px-3 text-sm">
        <select x-model="style" aria-label="Style" class="h-11 w-full border border-black bg-white px-3 text-sm">
            @foreach ($options::STYLES as $s)
                <option value="{{ $s['value'] }}">{{ $s['label'] }}</option>
            @endforeach
        </select>

        <div class="mt-4 flex flex-col gap-[21px]">
            <x-button class="w-full" x-on:click="generate()" x-bind:disabled="busy !== null">
                <span x-text="busy === 'generate' ? 'Generating…' : 'Generate Suggestion'">Generate Suggestion</span>
            </x-button>
            <x-button class="w-full" x-on:click="save()" x-bind:disabled="busy !== null">
                <span x-text="busy === 'save' ? 'Saving…' : 'Save Outfit'">Save Outfit</span>
            </x-button>
        </div>

        <div x-show="message" x-cloak class="notice" x-bind:class="{ 'notice-error': message && message.tone === 'error' }" role="status">
            <span x-text="message ? message.text : ''"></span>
            <a x-show="message && message.link === 'profile'" href="{{ route('profile') }}" class="underline">View in Profile</a>
            <a x-show="message && message.link === 'add'" href="{{ route('wardrobe.create') }}" class="underline">Add Item</a>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    function mixMatch(cfg) {
        const pick = (item, category) => (item && item.category === category ? item : null);
        return {
            candidates: cfg.candidates,
            selection: { top: pick(cfg.preselect, 'top'), bottom: pick(cfg.preselect, 'bottom') }, // dari ?item=ID
            name: '',
            style: 'casual',
            suggestion: null,   // { style, outfits, index } = hasil rekomendasi terakhir
            message: cfg.notice ? { tone: 'info', text: cfg.notice } : null,
            busy: null,         // null | 'generate' | 'save'

            categoryLabel(category) { return cfg.categoryLabels[category] || category; },
            isSelected(item) {
                const chosen = this.selection[item.category];
                return !!chosen && String(chosen.id) === String(item.id);
            },
            // Klik item: masuk slot sesuai kategorinya; klik lagi = lepas.
            toggle(item) {
                this.selection[item.category] = this.isSelected(item) ? null : item;
                this.message = null;
            },

            // Pembungkus fetch: selalu minta JSON, kirim CSRF, ubah error jadi teks.
            async call(url, options) {
                const res = await fetch(url, Object.assign({
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                }, options || {}));
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
                    throw new Error(firstError || data.message || ('Request failed (' + res.status + ')'));
                }
                return data;
            },

            showSuggestion(outfits, index, forStyle) {
                const outfit = outfits[index];
                const next = { top: null, bottom: null };
                outfit.items.forEach((i) => { if (i.category === 'top' || i.category === 'bottom') { next[i.category] = i; } });
                this.selection = next;
                this.suggestion = { style: forStyle, outfits: outfits, index: index };
                this.message = { tone: 'info', text: 'Suggestion ' + (index + 1) + ' of ' + outfits.length + ': ' + outfit.reason };
            },

            async generate() {
                this.message = null;
                // Style sama dengan hasil sebelumnya -> saran berikutnya, tanpa memanggil server lagi.
                if (this.suggestion && this.suggestion.style === this.style) {
                    const s = this.suggestion;
                    return this.showSuggestion(s.outfits, (s.index + 1) % s.outfits.length, this.style);
                }
                this.busy = 'generate';
                try {
                    const data = await this.call(cfg.urls.suggestions + '?style=' + encodeURIComponent(this.style));
                    if (!data.outfits || data.outfits.length === 0) {
                        this.suggestion = null;
                        this.message = { tone: 'info', text: data.message || 'No suggestions found.', link: 'add' };
                    } else {
                        this.showSuggestion(data.outfits, 0, this.style);
                    }
                } catch (err) {
                    this.message = { tone: 'error', text: err.message };
                } finally {
                    this.busy = null;
                }
            },

            async save() {
                if (!this.selection.top || !this.selection.bottom) {
                    this.message = { tone: 'error', text: 'Pick at least one top and one bottom first.' };
                    return;
                }
                this.busy = 'save';
                try {
                    const name = this.name.trim() || ((cfg.styleLabels[this.style] || this.style) + ' outfit');
                    const outfit = await this.call(cfg.urls.save, {
                        method: 'POST',
                        body: JSON.stringify({ name: name, item_ids: [this.selection.top.id, this.selection.bottom.id] }),
                    });
                    this.message = { tone: 'info', text: 'Saved "' + outfit.name + '".', link: 'profile' };
                } catch (err) {
                    this.message = { tone: 'error', text: err.message };
                } finally {
                    this.busy = null;
                }
            },
        };
    }
</script>
@endpush
