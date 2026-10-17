@props(['percent' => 0, 'color' => 'brand'])

<div {{ $attributes->class(['flex items-center gap-2']) }}>
    <div class="h-2 flex-1 overflow-hidden rounded-full bg-stone-200" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
        <div @class([
            'h-full rounded-full',
            'bg-brand-500' => $color === 'brand',
            'bg-emerald-500' => $color === 'emerald',
            'bg-red-500' => $color === 'red',
        ]) style="width: {{ max(0, min(100, $percent)) }}%"></div>
    </div>
    <span class="w-10 text-right text-xs tabular-nums text-stone-500">{{ $percent }}%</span>
</div>
