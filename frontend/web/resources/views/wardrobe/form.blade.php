@extends('layouts.app')

{{-- Wireframe "Wardrobe item.png". Satu view untuk Add (item = null) dan Edit (item = array). --}}
@php
    $isEdit = $item !== null;
    $options = \App\Support\FashionOptions::class;
    $inputClass = 'h-9 w-full border border-black bg-white px-2 text-sm';
@endphp
@section('title', ($isEdit ? 'Edit Item' : 'Add Item') . ' · StyloMate')

@section('content')
<div class="mx-auto grid max-w-[1100px] items-center gap-12 px-6 py-16 lg:grid-cols-[466px_1fr] lg:py-[100px]"
     x-data="itemForm(@js(['preview' => $item['imageUrl'] ?? null]))">

    {{-- Kiri: kotak gambar = area pilih foto --}}
    <div>
        <button type="button" x-on:click="$refs.file.click()" aria-label="Choose item photo" class="block w-full">
            <div class="relative aspect-[466/342] w-full overflow-hidden border border-black bg-[#e0e0e0]">
                <img x-show="preview" x-cloak x-bind:src="preview" alt="" class="absolute inset-0 h-full w-full object-contain">
                <template x-if="!preview">
                    <div>@include('partials.placeholder-x')</div>
                </template>
            </div>
        </button>
        <p class="mt-2 text-xs">Click the box to choose a JPEG or PNG, up to 10 MB.</p>
        <p x-show="clientError" x-cloak x-text="clientError" class="notice notice-error mt-2" role="alert"></p>
    </div>

    {{-- Kanan: form (POST biasa + CSRF; validasi dilakukan server) --}}
    <form method="POST"
          action="{{ $isEdit ? route('wardrobe.update', $item['id']) : route('wardrobe.store') }}"
          enctype="multipart/form-data" novalidate class="flex max-w-[443px] flex-col gap-4">
        @csrf
        @if ($isEdit)
            @method('PATCH')
        @endif

        <x-label as="h1" left class="w-[167px]">{{ $isEdit ? 'Edit Item' : 'Add Item' }}</x-label>

        {{-- Input file tersembunyi; dipicu kotak gambar atau tombol "Choose photo" --}}
        <input type="file" name="image" x-ref="file" accept="image/jpeg,image/png" class="sr-only" tabindex="-1" x-on:change="pick($event)">

        <label class="flex flex-col gap-1 text-xs">Name
            <input type="text" name="name" maxlength="80" value="{{ old('name', $item['name'] ?? '') }}" class="{{ $inputClass }}">
        </label>

        <div class="grid grid-cols-2 gap-x-[29px] gap-y-4">
            <label class="flex flex-col gap-1 text-xs">Category
                <select name="category" class="{{ $inputClass }}">
                    @foreach ($options::CATEGORIES as $o)
                        <option value="{{ $o['value'] }}" @selected(old('category', $item['category'] ?? 'top') === $o['value'])>{{ $o['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs">Color
                <select name="color" class="{{ $inputClass }}">
                    @foreach ($options::COLORS as $o)
                        <option value="{{ $o['value'] }}" @selected(old('color', $item['color'] ?? 'black') === $o['value'])>{{ $o['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-col gap-1 text-xs">Style
                <select name="style" class="{{ $inputClass }}">
                    @foreach ($options::STYLES as $o)
                        <option value="{{ $o['value'] }}" @selected(old('style', $item['style'] ?? 'casual') === $o['value'])>{{ $o['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex flex-col gap-1 text-xs">Photo
                <x-button class="!h-9 w-full !text-xs" x-on:click="$refs.file.click()">
                    <span x-text="picked ? 'Change photo' : 'Choose photo'">Choose photo</span>
                </x-button>
            </div>
        </div>

        @if ($errors->any())
            <x-notice tone="error">
                @foreach ($errors->all() as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </x-notice>
        @endif

        <div class="mt-6 grid grid-cols-2 gap-x-[29px]">
            <x-button-link href="{{ $isEdit ? route('wardrobe.show', $item['id']) : route('wardrobe.index') }}">Cancel</x-button-link>
            <x-button type="submit">Save</x-button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    // Pratinjau foto + pemeriksaan awal di browser (server tetap memeriksa ulang).
    function itemForm(cfg) {
        return {
            preview: cfg.preview,
            picked: false,
            clientError: '',
            pick(event) {
                const file = event.target.files && event.target.files[0];
                if (!file) { return; }
                this.clientError = '';
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    this.clientError = 'Use a JPEG or PNG image.';
                    event.target.value = '';
                    return;
                }
                if (file.size > 10 * 1024 * 1024) {
                    this.clientError = 'The image is larger than 10 MB. Choose a smaller one.';
                    event.target.value = '';
                    return;
                }
                if (this.preview && this.preview.startsWith('blob:')) { URL.revokeObjectURL(this.preview); }
                this.preview = URL.createObjectURL(file);
                this.picked = true;
            },
        };
    }
</script>
@endpush
