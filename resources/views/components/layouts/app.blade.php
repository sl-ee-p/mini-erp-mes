@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-stone-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1c1917">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-stone-800 antialiased" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
<a href="#main" class="sr-only z-50 rounded-md bg-white px-3 py-2 text-sm font-semibold text-stone-900 shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
<div class="min-h-full">
    {{-- Mobile sidebar backdrop --}}
    <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-stone-900/50 lg:hidden" @click="sidebarOpen = false"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-stone-900 text-stone-300 transition-transform lg:translate-x-0"
           :class="{ 'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen }">
        <a href="{{ route('home') }}" class="flex h-16 items-center gap-2 px-6 text-lg font-semibold text-white">
            <span class="inline-flex size-8 items-center justify-center rounded-md bg-brand-600 text-sm font-bold" aria-hidden="true">OW</span>
            Oakline ERP
        </a>

        <form method="GET" action="{{ route('search') }}" class="px-3 pt-2" role="search" aria-label="Global">
            <label for="global-search" class="sr-only">Search orders, customers, products and materials</label>
            <div class="relative">
                <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                <input id="global-search" type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Search…" autocomplete="off"
                       class="block w-full rounded-md border-0 bg-stone-800 py-2 pl-9 pr-8 text-sm text-white placeholder:text-stone-400 focus:ring-2 focus:ring-brand-500">
                <kbd class="pointer-events-none absolute right-2 top-1/2 hidden -translate-y-1/2 rounded border border-stone-600 px-1.5 text-xs text-stone-400 lg:block" aria-hidden="true">/</kbd>
            </div>
        </form>

        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4 text-sm" aria-label="Main">
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
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
            </button>
            <span class="flex-1 truncate font-semibold">{{ $title ?? config('app.name') }}</span>
            <a href="{{ route('search') }}" class="-m-2 p-2 text-stone-600" aria-label="Search">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
            </a>
        </header>

        <main id="main" class="px-4 py-8 sm:px-6 lg:px-8">
            <x-flash />
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
