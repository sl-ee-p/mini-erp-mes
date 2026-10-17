@use('App\Support\Format')

<x-layouts.app title="Production orders">
    <x-page-header title="Production orders" subtitle="Released automatically when a sales order is confirmed." />

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-form.input name="search" :value="request('search')" placeholder="PO, SO or product…" class="w-64" />
        <x-form.select name="status" :options="$statuses" :value="request('status')" placeholder="All statuses" class="w-44" />
        <x-form.checkbox name="late" label="Late only" :checked="request()->boolean('late')" class="pb-2" />
        <x-button variant="secondary">Filter</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">Number</th>
                <th class="px-4 py-3">Product</th>
                <th class="px-4 py-3">Sales order</th>
                <th class="px-4 py-3 text-right">Qty</th>
                <th class="px-4 py-3">Status</th>
                <th class="w-48 px-4 py-3">Progress</th>
                <th class="px-4 py-3 text-right">Planned / actual</th>
                <th class="px-4 py-3">Due</th>
            </tr>
        </x-slot:head>
        @forelse ($productionOrders as $productionOrder)
            <tr class="hover:bg-stone-50">
                <td class="px-4 py-3"><a href="{{ route('production-orders.show', $productionOrder) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $productionOrder->number }}</a></td>
                <td class="px-4 py-3">{{ $productionOrder->product->name }}</td>
                <td class="px-4 py-3 font-mono text-xs">{{ $productionOrder->salesOrder->number }}</td>
                <td class="px-4 py-3 text-right tabular-nums">{{ $productionOrder->quantity }}</td>
                <td class="px-4 py-3"><x-badge :color="$productionOrder->status->color()">{{ $productionOrder->status->label() }}</x-badge></td>
                <td class="px-4 py-3"><x-progress-bar :percent="$productionOrder->progressPercent()" /></td>
                <td class="px-4 py-3 text-right tabular-nums text-stone-600">{{ Format::minutes($productionOrder->planned_minutes) }} / {{ Format::minutes($productionOrder->actualMinutes()) }}</td>
                <td class="px-4 py-3">
                    <span @class(['font-semibold text-red-600' => $productionOrder->isLate()])>{{ $productionOrder->due_date->format('d M') }}</span>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-4 py-8 text-center text-stone-500">No production orders.</td></tr>
        @endforelse
        <x-slot:footer>{{ $productionOrders->links() }}</x-slot:footer>
    </x-table>
</x-layouts.app>
