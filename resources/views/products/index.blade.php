@use('App\Support\Format')

<x-layouts.app title="Products">
    <x-page-header title="Products" subtitle="Furniture we build, with bill of materials and routing.">
        <x-slot:actions>
            @can('create', App\Models\Product::class)
                <x-button :href="route('products.create')">New product</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-form.input name="search" :value="request('search')" placeholder="Search SKU or name…" class="w-64" />
        <x-form.select name="active" :options="['1' => 'Active', '0' => 'Inactive']" :value="request('active')" placeholder="All" class="w-36" />
        <x-button variant="secondary">Filter</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">SKU</th>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3 text-right">Price</th>
                <th class="px-4 py-3 text-right">BOM lines</th>
                <th class="px-4 py-3 text-right">Steps</th>
                <th class="px-4 py-3 text-right">Work / unit</th>
                <th class="px-4 py-3 text-right">In stock</th>
                <th class="px-4 py-3">Status</th>
            </tr>
        </x-slot:head>
        @forelse ($products as $product)
            <tr class="hover:bg-stone-50">
                <td class="px-4 py-3 font-mono text-xs">{{ $product->sku }}</td>
                <td class="px-4 py-3"><a href="{{ route('products.show', $product) }}" class="font-medium text-brand-700 hover:underline">{{ $product->name }}</a></td>
                <td class="px-4 py-3 text-right tabular-nums">{{ Format::money($product->price) }}</td>
                <td class="px-4 py-3 text-right tabular-nums">{{ $product->bom_items_count }}</td>
                <td class="px-4 py-3 text-right tabular-nums">{{ $product->routing_steps_count }}</td>
                <td class="px-4 py-3 text-right tabular-nums">{{ Format::minutes($product->routing_steps_sum_planned_minutes_per_unit) }}</td>
                <td class="px-4 py-3 text-right tabular-nums">{{ Format::quantity($product->material->stock_qty) }}</td>
                <td class="px-4 py-3">
                    @if ($product->is_active)
                        <x-badge color="emerald">Active</x-badge>
                    @else
                        <x-badge>Inactive</x-badge>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-4 py-8 text-center text-stone-500">No products found.</td></tr>
        @endforelse
        <x-slot:footer>{{ $products->links() }}</x-slot:footer>
    </x-table>
</x-layouts.app>
