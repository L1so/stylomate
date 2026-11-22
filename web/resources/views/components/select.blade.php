{{-- Dropdown reusable (padanan x-input). options = [nilai => label]; nama field sama dengan key validasi --}}
@props(['name', 'label', 'options', 'value' => null, 'required' => false, 'placeholder' => 'Pilih salah satu'])
@php
    $selected = old($name, $value);
    $errorId = $name.'-error';
@endphp
<div class="flex flex-col gap-2">
    <label for="{{ $name }}" class="text-sm font-semibold">{{ $label }}</label>
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @required($required)
        @error($name) aria-invalid="true" aria-describedby="{{ $errorId }}" @enderror
        class="h-12 w-full rounded-md border bg-white px-4 text-base text-charcoal @error($name) border-error @else border-rose-700 @enderror"
    >
        <option value="">{{ $placeholder }}</option>
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    <x-field-error :name="$name" />
</div>
