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
                @php($available = round($material->stock_qty - $allocated, 3))
                <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-stone-100 pt-4 text-sm">
                    <div>
                        <dt class="text-xs uppercase text-stone-500">Allocated to production</dt>
                        <dd class="font-medium tabular-nums">{{ Format::quantity($allocated, $material->unit) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase text-stone-500">Available</dt>
                        <dd @class(['font-medium tabular-nums', 'text-red-600' => $available < 0])>{{ Format::quantity($available, $material->unit) }}</dd>
                    </div>
                </dl>
                @if ($available < 0)
                    <p class="mt-3 rounded-md bg-red-50 p-2 text-sm text-red-700">Released production needs {{ Format::quantity(-$available, $material->unit) }} more than is on hand.</p>
                @endif
                @if ($material->isBelowMinimum())
                    <p class="mt-3 rounded-md bg-red-50 p-2 text-sm text-red-700">Below minimum — reorder at least {{ Format::quantity($material->shortage(), $material->unit) }}.</p>
                @endif
            </x-card>

            @if ($demand->isNotEmpty())
                <x-card title="Open demand" :padding="false">
                    <ul class="divide-y divide-stone-100 text-sm">
                        @foreach ($demand as $line)
                            <li class="flex items-center justify-between gap-4 px-5 py-2.5">
                                <div class="min-w-0">
                                    <a href="{{ route('production-orders.show', $line->productionOrder) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $line->productionOrder->number }}</a>
                                    <p class="truncate text-xs text-stone-500">{{ $line->productionOrder->product->name }} · due {{ $line->productionOrder->due_date->format('d M') }}</p>
                                </div>
                                <span class="shrink-0 tabular-nums">{{ Format::quantity($line->required_qty, $material->unit) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif

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
