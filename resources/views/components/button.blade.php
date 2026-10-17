@props(['variant' => 'primary', 'href' => null, 'size' => 'md'])

@php
    $classes = \Illuminate\Support\Arr::toCssClasses([
        'inline-flex items-center justify-center gap-2 rounded-md font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
        'px-3 py-2 text-sm' => $size === 'md',
        'px-2.5 py-1.5 text-xs' => $size === 'sm',
        'px-6 py-5 text-xl' => $size === 'xl',
        'bg-brand-600 text-white hover:bg-brand-700 focus-visible:ring-brand-500' => $variant === 'primary',
        'bg-white text-stone-700 ring-1 ring-inset ring-stone-300 hover:bg-stone-50 focus-visible:ring-stone-400' => $variant === 'secondary',
        'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-500' => $variant === 'danger',
        'bg-emerald-600 text-white hover:bg-emerald-700 focus-visible:ring-emerald-500' => $variant === 'success',
        'bg-amber-500 text-white hover:bg-amber-600 focus-visible:ring-amber-400' => $variant === 'warning',
    ]);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>{{ $slot }}</button>
@endif
