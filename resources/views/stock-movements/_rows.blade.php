@use('App\Support\Format')

@forelse ($movements as $movement)
    <tr>
        <td class="whitespace-nowrap px-4 py-3 text-stone-500">{{ $movement->created_at->format('d M Y H:i') }}</td>
        @if ($showMaterial ?? false)
            <td class="px-4 py-3"><a href="{{ route('materials.show', $movement->material) }}" class="font-medium text-brand-700 hover:underline">{{ $movement->material->name }}</a></td>
        @endif
        <td class="px-4 py-3"><x-badge :color="$movement->type->color()">{{ $movement->type->label() }}</x-badge></td>
        <td @class(['px-4 py-3 text-right tabular-nums font-medium', 'text-emerald-700' => $movement->quantity > 0, 'text-amber-700' => $movement->quantity < 0])>
            {{ $movement->quantity > 0 ? '+' : '' }}{{ Format::quantity($movement->quantity) }}
        </td>
        <td class="px-4 py-3 text-right tabular-nums">{{ Format::quantity($movement->balance_after) }}</td>
        <td class="px-4 py-3">
            {{ $movement->reason ?? '—' }}
            @if ($movement->reference)
                <span class="block text-xs text-stone-500">{{ str($movement->reference_type)->headline() }} {{ $movement->reference->number ?? '#'.$movement->reference_id }}</span>
            @endif
        </td>
        <td class="px-4 py-3 text-stone-500">{{ $movement->user?->name ?? 'System' }}</td>
    </tr>
@empty
    <tr><td colspan="7" class="px-4 py-8 text-center text-stone-500">No movements recorded.</td></tr>
@endforelse
