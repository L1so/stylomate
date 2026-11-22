@extends('layouts.app')
@section('title', 'Error · StyloMate')
@section('content')
    <div class="mx-auto max-w-[600px] px-6 py-24">
        <x-notice tone="error">{{ $message }}</x-notice>
        <p class="mt-3 text-xs">Status {{ $code }}.</p>
        <div class="mt-6 flex gap-3">
            <x-button-link href="{{ url()->previous() }}" class="w-[172px]">Go back</x-button-link>
            <x-button-link href="{{ route('wardrobe.index') }}" class="w-[172px]">Wardrobe</x-button-link>
        </div>
    </div>
@endsection
