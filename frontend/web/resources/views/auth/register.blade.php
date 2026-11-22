@extends('layouts.app')
@section('title', 'Register · StyloMate')
@section('content')
<div class="mx-auto max-w-[420px] px-6 py-16">
    <h1 class="mb-1 text-xl font-bold">Register</h1>
    <p class="mb-6 text-xs">Placeholder milik anggota 2.</p>
    <form method="POST" action="{{ route('register.store') }}" novalidate class="flex flex-col gap-4">
        @csrf
        <label class="flex flex-col gap-1 text-xs">Name
            <input type="text" name="name" value="{{ old('name') }}" class="h-9 w-full border border-black px-2 text-sm">
        </label>
        <label class="flex flex-col gap-1 text-xs">Email
            <input type="email" name="email" value="{{ old('email') }}" autocomplete="username" class="h-9 w-full border border-black px-2 text-sm">
        </label>
        <label class="flex flex-col gap-1 text-xs">Password (min 8 characters)
            <input type="password" name="password" autocomplete="new-password" class="h-9 w-full border border-black px-2 text-sm">
        </label>
        <label class="flex flex-col gap-1 text-xs">Confirm password
            <input type="password" name="password_confirmation" autocomplete="new-password" class="h-9 w-full border border-black px-2 text-sm">
        </label>
        @if ($errors->any())
            <x-notice tone="error">
                @foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach
            </x-notice>
        @endif
        <x-button type="submit" class="w-full">Create account</x-button>
    </form>
    <p class="mt-4 text-sm">Already registered? <a href="{{ route('login') }}" class="underline">Login</a></p>
</div>
@endsection
