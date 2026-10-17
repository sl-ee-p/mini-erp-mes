@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-stone-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-full items-center justify-center font-sans text-stone-800 antialiased">
    <div class="w-full max-w-sm px-4">
        <div class="mb-8 flex flex-col items-center gap-3">
            <span class="inline-flex size-12 items-center justify-center rounded-lg bg-brand-500 text-lg font-bold text-white">OW</span>
            <h1 class="text-xl font-semibold">Oakline Woodworks ERP</h1>
        </div>
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
