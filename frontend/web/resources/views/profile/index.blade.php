@extends('layouts.app')
@section('title', 'Profile · StyloMate')
@section('content')
<div class="mx-auto max-w-[1000px] px-6 py-12">
    <h1 class="mb-2 text-xl font-bold">Profile (placeholder)</h1>
    @if (!empty($user['name']) || !empty($user['email']))
        <p class="mb-6 text-sm">{{ $user['name'] ?? '' }} {{ $user['email'] ?? '' }}</p>
    @endif

    <h2 class="mb-2 font-bold">Saved outfits</h2>
    @if (count($outfits) === 0)
        <x-notice class="mb-8">No saved outfits yet.</x-notice>
    @endif
    <ul class="mb-8 flex flex-col gap-3">
        @foreach ($outfits as $outfit)
            <li class="flex items-center gap-3 border border-black p-2 text-sm">
                @foreach ($outfit['items'] as $item)
                    <x-image-box :src="$item['imageUrl']" :alt="$item['name']" fit="contain" class="h-16 w-16" />
                @endforeach
                <span>{{ $outfit['name'] }}</span>
            </li>
        @endforeach
    </ul>

    <h2 class="mb-2 font-bold">Saved try-on results</h2>
    @if (count($history) === 0)
        <x-notice>No saved try-on results yet.</x-notice>
    @endif
    <ul class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        @foreach ($history as $result)
            <li><x-image-box :src="$result['imageUrl']" alt="Saved try-on" fit="contain" class="aspect-[3/4] w-full" /></li>
        @endforeach
    </ul>
</div>
@endsection
