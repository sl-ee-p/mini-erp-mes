@use('App\Support\Format')

<x-layouts.app title="Materials">
    <x-page-header title="Materials" subtitle="Raw materials and finished goods on hand.">
        <x-slot:actions>
            @can('create', App\Models\Material::class)
                <x-button :href="route('materials.create')">New raw material</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-form.input name="search" :value="request('search')" placeholder="Search SKU or name…" class="w-64" />
        <x-form.select name="type" :options="$types" :value="request('type')" placeholder="All types" class="w-48" />
        <x-form.checkbox name="low_stock" label="Below minimum only" :checked="request()->boolean('low_stock')" class="pb-2" />
        <x-form.checkbox name="shortage" label="Short for production" :checked="request()->boolean('shortage')" class="pb-2" />
        <x-button variant="secondary">Filter</x-button>
        <x-button variant="secondary" :href="route('materials.export', request()->query())" class="ml-auto">
            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v11m0 0-4-4m4 4 4-4M5 19h14"/></svg>
            Export CSV
        </x-button>
    </form>

    <x-table>
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">SKU</th>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3 text-right">On hand</th>
                <th class="px-4 py-3 text-right"><abbr title="Still to be consumed by released production orders" class="no-underline">Allocated</abbr></th>
                <th class="px-4 py-3 text-right">Available</th>
                <th class="px-4 py-3 text-right">Minimum</th>
                <th class="px-4 py-3 text-right">Unit cost</th>
            </tr>
        </x-slot:head>
        @forelse ($materials as $material)
            <tr class="hover:bg-stone-50">
                <td class="px-4 py-3 font-mono text-xs">{{ $material->sku }}</td>
                <td class="px-4 py-3"><a href="{{ route('materials.show', $material) }}" class="font-medium text-brand-700 hover:underline">{{ $material->name }}</a></td>
                <td class="px-4 py-3"><x-badge :color="$material->type === App\Domain\Inventory\Enums\MaterialType::Raw ? 'stone' : 'violet'">{{ $material->type->label() }}</x-badge></td>
                <td class="px-4 py-3 text-right tabular-nums">
                    <span @class(['font-semibold text-red-600' => $material->isBelowMinimum()])>{{ Format::quantity($material->stock_qty, $material->unit) }}</span>
                    @if ($material->isBelowMinimum())
                        <x-badge color="red" class="ml-1">Low</x-badge>
                    @endif
                </td>
                @php($materialAllocated = $allocated->get($material->id, 0.0))
                @php($available = round($material->stock_qty - $materialAllocated, 3))
                <td class="px-4 py-3 text-right tabular-nums text-stone-500">{{ $materialAllocated > 0 ? Format::quantity($materialAllocated) : '—' }}</td>
                <td class="px-4 py-3 text-right tabular-nums">
                    <span @class(['font-semibold text-red-600' => $available < 0])>{{ Format::quantity($available) }}</span>
                    @if ($available < 0)
                        <x-badge color="red" class="ml-1">Short</x-badge>
                    @endif
                </td>
                <td class="px-4 py-3 text-right tabular-nums text-stone-500">{{ Format::quantity($material->min_stock, $material->unit) }}</td>
                <td class="px-4 py-3 text-right tabular-nums">{{ Format::money($material->unit_cost) }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-4 py-8 text-center text-stone-500">No materials found.</td></tr>
        @endforelse
        <x-slot:footer>{{ $materials->links() }}</x-slot:footer>
    </x-table>
</x-layouts.app>
