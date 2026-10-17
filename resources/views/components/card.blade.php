@props(['title' => null, 'padding' => true])

<section {{ $attributes->class(['rounded-xl bg-white shadow-sm ring-1 ring-stone-200']) }}>
    @if ($title)
        <header class="flex items-center justify-between border-b border-stone-200 px-5 py-3">
            <h2 class="text-sm font-semibold text-stone-900">{{ $title }}</h2>
            {{ $headerActions ?? '' }}
        </header>
    @endif
    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>
</section>
