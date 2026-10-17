@props(['active' => false])

<a {{ $attributes->class([
    'flex items-center rounded-md px-3 py-2 font-medium transition',
    'bg-stone-800 text-white' => $active,
    'text-stone-300 hover:bg-stone-800 hover:text-white' => ! $active,
]) }} @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>
