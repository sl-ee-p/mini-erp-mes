@use('App\Support\Format')

<x-layouts.app :title="$product->name">
    <x-page-header :title="$product->name" :subtitle="$product->sku.' · '.Format::money($product->price)">
        <x-slot:actions>
            @unless ($product->is_active)
                <x-badge>Inactive</x-badge>
            @endunless
            <x-button variant="secondary" :href="route('materials.show', $product->material)">Finished stock: {{ Format::quantity($product->material->stock_qty) }} pcs</x-button>
            @can('update', $product)
                <x-button :href="route('products.edit', $product)">Edit</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    @if ($product->description)
        <p class="mb-6 max-w-3xl text-sm text-stone-600">{{ $product->description }}</p>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Bill of materials (per unit)" :padding="false">
            <table class="min-w-full divide-y divide-stone-100 text-sm">
                <tbody class="divide-y divide-stone-100">
                    @forelse ($product->bomItems as $item)
                        <tr>
                            <td class="px-5 py-2.5"><a href="{{ route('materials.show', $item->material) }}" class="text-brand-700 hover:underline">{{ $item->material->name }}</a>
                                <span class="block font-mono text-xs text-stone-500">{{ $item->material->sku }}</span></td>
                            <td class="px-5 py-2.5 text-right tabular-nums">{{ Format::quantity($item->quantity, $item->material->unit) }}</td>
                            <td class="px-5 py-2.5 text-right tabular-nums text-stone-500">{{ Format::money($item->quantity * $item->material->unit_cost) }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-6 text-center text-stone-500">No materials defined.</td></tr>
                    @endforelse
                </tbody>
                @if ($product->bomItems->isNotEmpty())
                    <tfoot class="bg-stone-50 text-sm font-medium">
                        <tr>
                            <td class="px-5 py-2.5" colspan="2">Material cost per unit</td>
                            <td class="px-5 py-2.5 text-right tabular-nums">{{ Format::money($product->bomItems->sum(fn ($item) => $item->quantity * $item->material->unit_cost)) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </x-card>

        <x-card title="Routing" :padding="false">
            <ol class="divide-y divide-stone-100 text-sm">
                @foreach ($product->routingSteps as $step)
                    <li class="flex items-center gap-4 px-5 py-2.5">
                        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-800">{{ $step->sequence }}</span>
                        <div class="flex-1">
                            <p class="font-medium">{{ $step->name }}</p>
                            <p class="text-xs text-stone-500">{{ $step->workCenter->name }}</p>
                        </div>
                        @if ($step->consumes_materials)
                            <x-badge color="amber">Consumes materials</x-badge>
                        @endif
                        <span class="tabular-nums text-stone-600">{{ Format::minutes($step->planned_minutes_per_unit) }} / unit</span>
                    </li>
                @endforeach
            </ol>
            <div class="border-t border-stone-200 bg-stone-50 px-5 py-2.5 text-sm font-medium">
                Total work content: {{ Format::minutes($product->plannedMinutesPerUnit()) }} per unit
            </div>
        </x-card>
    </div>
</x-layouts.app>
