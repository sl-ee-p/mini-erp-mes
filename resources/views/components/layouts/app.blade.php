@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-stone-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-stone-800 antialiased" x-data="{ sidebarOpen: false }">
<div class="min-h-full">
    {{-- Mobile sidebar backdrop --}}
    <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-stone-900/50 lg:hidden" @click="sidebarOpen = false"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-stone-900 text-stone-300 transition-transform lg:translate-x-0"
           :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }">
        <a href="{{ route('home') }}" class="flex h-16 items-center gap-2 px-6 text-lg font-semibold text-white">
            <span class="inline-flex size-8 items-center justify-center rounded-md bg-brand-500 text-sm font-bold">OW</span>
            Oakline ERP
        </a>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4 text-sm">
            @include('layouts.partials.navigation')
        </nav>

        <div class="border-t border-stone-800 p-4 text-sm">
            <p class="font-medium text-white">{{ auth()->user()->name }}</p>
            <p class="text-xs text-stone-400">{{ auth()->user()->role->label() }}</p>
            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="text-xs text-stone-400 hover:text-white">Sign out</button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-stone-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:hidden">
            <button type="button" class="-m-2 p-2 text-stone-600" @click="sidebarOpen = true" aria-label="Open navigation">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
            <span class="font-semibold">{{ $title ?? config('app.name') }}</span>
        </header>

        <main class="px-4 py-8 sm:px-6 lg:px-8">
            <x-flash />
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
