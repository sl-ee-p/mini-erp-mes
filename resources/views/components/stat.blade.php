@props(['label', 'value', 'hint' => null, 'tone' => 'default', 'href' => null])

{{-- KPI tile. The tone colours the value only when it calls for action; the label always says what it means. --}}
@php($tag = $href ? 'a' : 'div')

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class([
    'block rounded-xl bg-white p-4 shadow-sm ring-1 ring-stone-200 sm:p-5',
    'transition hover:ring-brand-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500' => $href,
]) }}>
    <p class="text-sm font-medium text-stone-500">{{ $label }}</p>
    <p @class([
        'mt-1 text-2xl font-semibold sm:mt-2 sm:text-3xl',
        'text-stone-900' => $tone === 'default',
        'text-red-600' => $tone === 'danger',
        'text-amber-700' => $tone === 'warning',
        'text-emerald-700' => $tone === 'good',
    ])>{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-stone-500 sm:text-sm">{{ $hint }}</p>
    @endif
</{{ $tag }}>
