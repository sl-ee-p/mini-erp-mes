@use('App\Support\Format')

<x-layouts.app :title="$material->name">
    <x-page-header :title="$material->name" :subtitle="$material->sku.' · '.$material->type->label()">
        <x-slot:actions>
            @if ($material->product)
                <x-button variant="secondary" :href="route('products.show', $material->product)">View product</x-button>
            @endif
            @can('update', $material)
                <x-button variant="secondary" :href="route('materials.edit', $material)">Edit</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
            <x-card title="Stock level">
                <p @class(['text-3xl font-semibold tabular-nums', 'text-red-600' => $material->isBelowMinimum()])>
                    {{ Format::quantity($material->stock_qty, $material->unit) }}
                </p>
                <p class="mt-1 text-sm text-stone-500">Minimum {{ Format::quantity($material->min_stock, $material->unit) }} · {{ Format::money($material->unit_cost) }} / {{ $material->unit->label() }}</p>
                @if ($material->isBelowMinimum())
                    <p class="mt-3 rounded-md bg-red-50 p-2 text-sm text-red-700">Below minimum — reorder at least {{ Format::quantity($material->shortage(), $material->unit) }}.</p>
                @endif
            </x-card>

            @can('manage-stock')
                <x-card title="Book stock movement">
                    <form method="POST" action="{{ route('materials.stock-movements.store', $material) }}" class="space-y-4"
                          x-data="{ type: @js(old('type', 'in')) }">
                        @csrf
                        <x-form.select name="type" label="Movement" :options="$movementTypes" x-model="type" />
                        <x-form.input name="quantity" type="number" step="0.001" min="0" required
                                      :label="'Quantity ('.$material->unit->label().')'" />
                        <p class="-mt-2 text-xs text-stone-500" x-show="type === 'adjustment'" x-cloak>Enter the physically counted quantity; the difference is booked.</p>
                        <x-form.input name="reason" label="Reason / reference" placeholder="e.g. Supplier delivery DN-4471" />
                        <x-button class="w-full">Record movement</x-button>
                    </form>
                </x-card>
            @endcan
        </div>

        <div class="lg:col-span-2">
            <x-table>
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3 text-right">Qty</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">By</th>
                    </tr>
                </x-slot:head>
                @include('stock-movements._rows', ['movements' => $movements])
                <x-slot:footer>{{ $movements->links() }}</x-slot:footer>
            </x-table>
        </div>
    </div>
</x-layouts.app>
