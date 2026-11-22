{{-- Input form reusable; nama field harus sama dengan key validasi di controller. Opsional: hint (teks bantuan) --}}
@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'autocomplete' => null, 'placeholder' => null, 'hint' => null])
@php
    // Password tidak pernah diisi ulang dari old() demi keamanan
    $inputValue = $type === 'password' ? null : old($name, $value);
    $errorId = $name.'-error';
    $hintId = $name.'-hint';
    $describedBy = trim((!empty($hint) ? $hintId : '').' '.($errors->has($name) ? $errorId : ''));
@endphp
<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="text-sm font-semibold">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $inputValue }}"
        @required($required)
        @if (!empty($autocomplete)) autocomplete="{{ $autocomplete }}" @endif
        @if (!empty($placeholder)) placeholder="{{ $placeholder }}" @endif
        @error($name) aria-invalid="true" @enderror
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        class="h-12 w-full rounded-md border bg-white px-4 text-base text-charcoal @error($name) border-error @else border-rose-700 @enderror"
    >
    @if (!empty($hint))
        <span id="{{ $hintId }}" class="text-xs font-medium text-taupe">{{ $hint }}</span>
    @endif
    {{-- Pesan error validasi dari $request->validate() atau dari FastAPI (422); ikon + teks, bukan warna saja --}}
    @error($name)
        <p id="{{ $errorId }}" class="flex items-start gap-1.5 text-sm text-error">
            <svg class="mt-0.5 flex-none" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>
            <span>{{ $message }}</span>
        </p>
    @enderror
</div>
