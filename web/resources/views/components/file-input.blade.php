{{-- Input foto JPEG/PNG maks 10 MB. accept hanya membantu pengguna; validasi sebenarnya di server (Laravel dan FastAPI) --}}
@props(['name', 'label', 'required' => false, 'hint' => 'JPEG atau PNG, maksimal 10 MB.'])
@php
    $errorId = $name.'-error';
    $hintId = $name.'-hint';
@endphp
<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="text-sm font-semibold">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="file"
        accept="image/jpeg,image/png"
        @required($required)
        aria-describedby="{{ $hintId }}@error($name) {{ $errorId }}@enderror"
        @error($name) aria-invalid="true" @enderror
        class="w-full rounded-md border bg-white p-3 text-sm text-charcoal file:mr-4 file:min-h-10 file:cursor-pointer file:rounded-full file:border-0 file:bg-rose-50 file:px-4 file:text-sm file:font-semibold @error($name) border-error @else border-rose-700 @enderror"
    >
    <span id="{{ $hintId }}" class="text-xs font-medium text-taupe">{{ $hint }}</span>
    <x-field-error :name="$name" />
</div>
