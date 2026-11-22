{{-- Form Wardrobe dipakai halaman tambah (foto wajib) dan edit (tanpa foto). $item null = tambah --}}
@php $isEdit = $item !== null; @endphp
<form action="{{ $action }}" method="POST" enctype="multipart/form-data" data-loading class="flex max-w-xl flex-col gap-6 rounded-lg bg-white p-6 shadow-xs md:p-8" novalidate>
    @csrf
    @if ($isEdit)
        @method('PATCH')
    @endif

    @if ($isEdit)
        <x-file-input name="image" label="Ganti foto (opsional)" hint="Kosongkan bila foto tidak diganti. JPEG atau PNG, maksimal 10 MB." />
    @else
        <x-file-input name="image" label="Foto pakaian" :required="true" />
    @endif

    <x-input name="name" label="Nama pakaian" :value="$item['name'] ?? null" :required="true" placeholder="Contoh: Kemeja Putih" />
    <x-select name="category" label="Kategori" :options="$categories" :value="$item['category'] ?? null" :required="true" />
    <x-input name="color" label="Warna" :value="$item['color'] ?? null" :required="true" placeholder="Contoh: putih" />
    <x-select name="style" label="Style" :options="$styles" :value="$item['style'] ?? null" :required="true" />

    <div class="flex flex-wrap items-center gap-3">
        <x-button :label="$submitLabel" />
        <x-button label="Batal" :href="$cancelUrl" variant="ghost" type="button" />
        <x-loading message="Mengunggah foto…" :hidden="true" />
    </div>
</form>
