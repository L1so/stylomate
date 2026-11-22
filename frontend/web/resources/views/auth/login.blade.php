@extends('layouts.app')
@section('title', 'Login · StyloMate')
@section('content')
<div class="mx-auto max-w-[420px] px-6 py-16">
    <h1 class="mb-1 text-xl font-bold">Login</h1>
    <p class="mb-6 text-xs">Placeholder milik anggota 2. Kredensial dikirim Laravel ke FastAPI; token disimpan di session server.</p>
    <form method="POST" action="{{ route('login.store') }}" novalidate class="flex flex-col gap-4">
        @csrf
        <label class="flex flex-col gap-1 text-xs">Email
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" class="h-9 w-full border border-black px-2 text-sm">
        </label>
        <label class="flex flex-col gap-1 text-xs">Password
            <input type="password" name="password" autocomplete="current-password" class="h-9 w-full border border-black px-2 text-sm">
        </label>
        @if ($errors->any())
            <x-notice tone="error">
                @foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach
            </x-notice>
        @endif
        <x-button type="submit" class="w-full">Login</x-button>
    </form>
    <p class="mt-4 text-sm">No account? <a href="{{ route('register') }}" class="underline">Register</a></p>
</div>
@endsection
