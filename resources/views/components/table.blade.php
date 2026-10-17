<div {{ $attributes->class(['overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-stone-200']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-stone-200 text-sm">
            <thead class="bg-stone-50 text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                {{ $head }}
            </thead>
            <tbody class="divide-y divide-stone-100">
                {{ $slot }}
            </tbody>
        </table>
    </div>
    @isset($footer)
        <div class="border-t border-stone-200 px-4 py-3">{{ $footer }}</div>
    @endisset
</div>
