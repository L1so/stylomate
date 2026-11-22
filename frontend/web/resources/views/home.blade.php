@extends('layouts.app')
@section('title', 'StyloMate')
@section('content')
<div class="mx-auto max-w-[1000px] px-6 py-12">
    <h1 class="mb-6 text-xl font-bold">StyloMate (placeholder Homepage)</h1>
    <div class="flex flex-wrap gap-3">
        <x-button-link href="{{ route('wardrobe.index') }}" class="w-[172px]">Wardrobe</x-button-link>
        <x-button-link href="{{ route('mixmatch') }}" class="w-[172px]">Mix &amp; Match</x-button-link>
        <x-button-link href="{{ route('tryon') }}" class="w-[172px]">Try on</x-button-link>
    </div>
</div>
@endsection
