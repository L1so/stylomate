@extends('layouts.app')
@section('title', 'Try on · StyloMate')

{{-- Wireframe "Try On.png". Alur (PRD 7.2): pilih pakaian -> upload foto -> Generate -> lihat hasil -> Save.
     Tombol "Generate" ditambahkan karena wireframe belum punya pemicu generate. --}}
@section('content')
@php
    $config = [
        'wardrobe' => $wardrobe,
        'catalog'  => $catalog,
        'preload'  => $preload,
        'csrf'     => csrf_token(),
        'urls'     => ['generate' => route('tryon.generate'), 'save' => route('tryon.save')],
    ];
@endphp
<div class="mx-auto grid max-w-[1440px] gap-10 px-6 py-8 lg:grid-cols-[minmax(0,703px)_minmax(0,424px)] lg:justify-between lg:px-[69px]"
     x-data="tryOn(@js($config))">

    <div class="flex flex-col gap-5">
        <div class="grid gap-6 sm:grid-cols-[minmax(0,419px)_207px] sm:items-center sm:justify-between">
            {{-- My Photo --}}
            <div>
                <x-label>My Photo</x-label>
                <button type="button" x-on:click="$refs.photo.click()" aria-label="Choose your photo" class="mt-[15px] block w-full">
                    <div class="relative aspect-[419/297] w-full overflow-hidden border border-black bg-[#e0e0e0]">
                        <img x-show="personUrl" x-cloak x-bind:src="personUrl" alt="Your photo" class="absolute inset-0 h-full w-full object-contain">
                        <div x-show="!personUrl">@include('partials.placeholder-x')</div>
                    </div>
                </button>
                <input type="file" x-ref="photo" accept="image/jpeg,image/png" class="sr-only" tabindex="-1" x-on:change="onPhoto($event)">
                <p class="mt-2 text-xs">Click the box to choose a JPEG or PNG, up to 10 MB. A clear, well-lit photo works best.</p>
                <p x-show="photoError" x-cloak x-text="photoError" class="notice notice-error mt-2" role="alert"></p>
            </div>

            {{-- Tombol aksi (tengah) --}}
            <div class="flex flex-col gap-[22px]">
                <x-button class="w-full" x-on:click="generate()" x-bind:disabled="!canGenerate">
                    <span x-text="status === 'loading' ? 'Generating…' : 'Generate'">Generate</span>
                </x-button>
                <x-button class="w-full" x-on:click="save()" x-bind:disabled="!result || saved">
                    <span x-text="saved ? 'Saved' : 'Save'">Save</span>
                </x-button>
                <x-button class="w-full" x-on:click="share()" x-bind:disabled="!result">Share</x-button>
            </div>
        </div>

        {{-- My Outfit: 3 slot --}}
        <div>
            <x-label>My Outfit</x-label>
            <ul class="mt-[29px] grid grid-cols-3 gap-6 lg:gap-[57px]">
                @foreach ([0, 1, 2] as $i)
                    <li class="relative">
                        <button type="button" x-on:click="slots[{{ $i }}] ? (active = {{ $i }}) : (pickerSlot = {{ $i }})"
                                x-bind:aria-label="slots[{{ $i }}] ? 'Try on ' + slots[{{ $i }}].name : 'Choose an item for slot {{ $i + 1 }}'"
                                x-bind:class="{ 'is-active': slots[{{ $i }}] && active === {{ $i }} }"
                                class="block w-full">
                            <div class="relative aspect-square w-full overflow-hidden border border-black bg-[#e0e0e0]">
                                <img x-show="slots[{{ $i }}]" x-cloak x-bind:src="slots[{{ $i }}] ? slots[{{ $i }}].imageUrl : null" alt="" class="absolute inset-0 h-full w-full object-contain">
                                <div x-show="!slots[{{ $i }}]">@include('partials.placeholder-x')</div>
                                <span x-show="!slots[{{ $i }}]" class="absolute inset-0 grid place-items-center">
                                    <span class="bg-white px-2 py-1 text-xs">+ Add item</span>
                                </span>
                            </div>
                        </button>
                        <button type="button" x-show="slots[{{ $i }}]" x-cloak x-on:click="remove({{ $i }})"
                                x-bind:aria-label="slots[{{ $i }}] ? 'Remove ' + slots[{{ $i }}].name : 'Remove'"
                                class="absolute right-1 top-1 h-6 w-6 bg-black text-sm text-white">×</button>
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-xs">Click a filled slot to choose which item to try on. One item is processed at a time.</p>
        </div>
    </div>

    {{-- Result --}}
    <div class="lg:mt-[35px]">
        <x-label>Result</x-label>
        <div class="relative mt-[17px] aspect-[424/520] w-full overflow-hidden border border-black bg-[#e0e0e0]">
            <img x-show="result" x-cloak x-bind:src="result ? result.imageUrl : null" alt="Try-on result" class="absolute inset-0 h-full w-full object-contain">
            <div x-show="!result">@include('partials.placeholder-x')</div>
            <div x-show="status === 'loading'" x-cloak role="status" aria-live="polite"
                 class="absolute inset-0 grid place-items-center bg-white/85 p-6 text-center text-sm">
                <div>
                    <div class="mx-auto mb-4 h-1 w-40 animate-pulse bg-black"></div>
                    Generating your try-on…<br>This can take up to a minute.
                </div>
            </div>
        </div>
        <div x-show="message" x-cloak class="notice mt-3" x-bind:class="{ 'notice-error': message && message.tone === 'error' }" role="status">
            <span x-text="message ? message.text : ''"></span>
            <a x-show="message && message.link" href="{{ route('profile') }}" class="underline">View in Profile</a>
        </div>
    </div>

    {{-- Jendela pilih pakaian (Wardrobe / Catalog) --}}
    <div x-show="pickerSlot !== null" x-cloak class="fixed inset-0 z-50 grid place-items-center bg-black/60 p-4"
         x-on:click.self="pickerSlot = null" x-on:keydown.escape.window="pickerSlot = null">
        <div role="dialog" aria-modal="true" aria-label="Choose an item" class="max-h-[85vh] w-full max-w-[720px] overflow-y-auto bg-white p-5">
            <div class="mb-4 flex items-center justify-between">
                <div role="tablist" class="flex gap-2">
                    <button type="button" role="tab" x-on:click="tab = 'wardrobe'" x-bind:class="{ 'is-selected': tab === 'wardrobe' }" class="border border-black px-3 py-1 text-sm">Wardrobe</button>
                    <button type="button" role="tab" x-on:click="tab = 'catalog'" x-bind:class="{ 'is-selected': tab === 'catalog' }" class="border border-black px-3 py-1 text-sm">Catalog</button>
                </div>
                <button type="button" x-on:click="pickerSlot = null" class="border border-black px-3 py-1 text-sm">Close</button>
            </div>
            <p x-show="pickerItems.length === 0" class="notice">Nothing here yet.</p>
            <ul class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <template x-for="item in pickerItems" x-bind:key="item.source + '-' + item.id">
                    <li>
                        <button type="button" x-on:click="choose(item)" class="block w-full text-left">
                            <div class="relative aspect-square w-full overflow-hidden border border-black bg-[#e0e0e0]">
                                <img x-bind:src="item.imageUrl" alt="" class="absolute inset-0 h-full w-full object-contain">
                            </div>
                            <span class="mt-1 block truncate text-xs" x-text="item.name"></span>
                        </button>
                    </li>
                </template>
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function tryOn(cfg) {
        return {
            // Foto pengguna
            personFile: null, personUrl: null, photoError: '',
            // Slot "My Outfit" (3 kotak) + slot yang dipilih untuk dicoba
            slots: [null, null, null], active: null, pickerSlot: null, tab: 'wardrobe',
            // Hasil
            status: 'idle',            // idle | loading | done | error
            result: null,              // { id, imageUrl }
            resultGarment: null,       // pakaian yang dipakai saat generate (untuk Save)
            saved: false, message: null,

            init() {
                // Datang dari Catalog/Wardrobe (?catalog=ID / ?wardrobe=ID): slot pertama terisi.
                if (cfg.preload) { this.slots[0] = cfg.preload; this.active = 0; }
            },

            get garment() { return this.active === null ? null : this.slots[this.active]; },
            get canGenerate() { return !!(this.personFile && this.garment) && this.status !== 'loading'; },
            get pickerItems() { return this.tab === 'wardrobe' ? cfg.wardrobe : cfg.catalog; },

            // Pemeriksaan foto: tipe, ukuran, dan benar-benar bisa dibuka (PRD NFR-03).
            checkPhoto(file) {
                return new Promise((resolve) => {
                    if (!['image/jpeg', 'image/png'].includes(file.type)) { return resolve('Use a JPEG or PNG image.'); }
                    if (file.size > 10 * 1024 * 1024) { return resolve('The image is larger than 10 MB. Choose a smaller one.'); }
                    const url = URL.createObjectURL(file);
                    const img = new Image();
                    img.onload = () => { URL.revokeObjectURL(url); resolve(null); };
                    img.onerror = () => { URL.revokeObjectURL(url); resolve('This file is not a readable image. Choose another one.'); };
                    img.src = url;
                });
            },
            async onPhoto(event) {
                const file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (!file) { return; }
                const problem = await this.checkPhoto(file);
                if (problem) { this.photoError = problem; return; }
                this.photoError = '';
                if (this.personUrl) { URL.revokeObjectURL(this.personUrl); }
                this.personFile = file;
                this.personUrl = URL.createObjectURL(file);
                // Foto berganti -> hasil lama tidak relevan lagi.
                this.result = null; this.saved = false; this.status = 'idle'; this.message = null;
            },

            choose(item) {
                const i = this.pickerSlot;
                this.slots[i] = item;
                if (this.active === null) { this.active = i; }
                this.pickerSlot = null;
            },
            remove(i) {
                this.slots[i] = null;
                if (this.active === i) {
                    const firstFilled = this.slots.findIndex((s) => s !== null);
                    this.active = firstFilled === -1 ? null : firstFilled;
                }
            },

            errorText(data, res) {
                const first = data.errors ? Object.values(data.errors)[0][0] : null;
                return first || data.message || ('Request failed (' + res.status + ')');
            },

            // >>> TITIK INTEGRASI AI di sisi browser: memanggil Laravel (cfg.urls.generate).
            // Laravel yang memanggil Main Backend/AI: lihat app/Services/Fashion/Api/ApiTryOn.php
            async generate() {
                if (!this.canGenerate) { return; }
                const g = this.garment;
                this.status = 'loading'; this.message = null; this.saved = false; this.result = null;

                const form = new FormData();
                form.append('person_image', this.personFile);
                form.append('source', g.source);
                form.append('garment_id', g.id);

                const controller = new AbortController();
                const timer = setTimeout(() => controller.abort(), 190000);
                try {
                    const res = await fetch(cfg.urls.generate, {
                        method: 'POST', body: form, signal: controller.signal,
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) { throw new Error(this.errorText(data, res)); }
                    this.result = data;
                    this.resultGarment = g;
                    this.status = 'done';
                } catch (err) {
                    // Kegagalan AI tidak boleh membuat halaman berhenti (PRD NFR-09).
                    this.status = 'error';
                    this.message = {
                        tone: 'error',
                        text: err.name === 'AbortError' ? 'The request timed out. Please try again.' : (err.message || 'Generation failed. Please try again.'),
                    };
                } finally {
                    clearTimeout(timer);
                }
            },

            async save() {
                try {
                    const g = this.resultGarment;
                    const res = await fetch(cfg.urls.save, {
                        method: 'POST',
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                        body: JSON.stringify({ id: this.result.id, source: g.source, garment_id: g.id }),
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok) { throw new Error(this.errorText(data, res)); }
                    this.saved = true;
                    this.message = { tone: 'info', text: 'Saved.', link: true };
                } catch (err) {
                    this.message = { tone: 'error', text: err.message };
                }
            },

            // Web Share bila ada (umumnya HP); jika tidak, gambar diunduh.
            async share() {
                try {
                    const blob = await (await fetch(this.result.imageUrl)).blob();
                    const file = new File([blob], 'stylomate-try-on.jpg', { type: blob.type || 'image/jpeg' });
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        await navigator.share({ files: [file], title: 'My StyloMate try-on' });
                    } else {
                        const a = document.createElement('a');
                        a.href = URL.createObjectURL(blob);
                        a.download = 'stylomate-try-on.jpg';
                        a.click();
                        setTimeout(() => URL.revokeObjectURL(a.href), 1000);
                    }
                } catch (err) {
                    if (err.name !== 'AbortError') {
                        this.message = { tone: 'error', text: 'Could not share this image. Try saving it instead.' };
                    }
                }
            },
        };
    }
</script>
@endpush
