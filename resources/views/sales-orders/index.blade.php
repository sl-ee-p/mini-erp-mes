@use('App\Support\Format')

<x-layouts.app title="Sales orders">
    <x-page-header title="Sales orders">
        <x-slot:actions>
            @can('create', App\Models\SalesOrder::class)
                <x-button :href="route('sales-orders.create')">New order</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-form.input name="search" :value="request('search')" placeholder="Order number or customer…" class="w-64" />
        <x-form.select name="status" :options="$statuses" :value="request('status')" placeholder="All statuses" class="w-44" />
        <x-form.checkbox name="late" label="Late only" :checked="request()->boolean('late')" class="pb-2" />
        <x-button variant="secondary">Filter</x-button>
    </form>

    <x-table>
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">Number</th>
                <th class="px-4 py-3">Customer</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Ordered</th>
                <th class="px-4 py-3">Due</th>
                <th class="px-4 py-3 text-right">Lines</th>
                <th class="px-4 py-3 text-right">Total</th>
            </tr>
        </x-slot:head>
        @forelse ($orders as $order)
            <tr class="hover:bg-stone-50">
                <td class="px-4 py-3"><a href="{{ route('sales-orders.show', $order) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $order->number }}</a></td>
                <td class="px-4 py-3">{{ $order->customer->name }}</td>
                <td class="px-4 py-3"><x-badge :color="$order->status->color()">{{ $order->status->label() }}</x-badge></td>
                <td class="px-4 py-3 text-stone-500">{{ $order->order_date->format('d M Y') }}</td>
                <td class="px-4 py-3">
                    <span @class(['font-semibold text-red-600' => $order->isLate()])>{{ $order->due_date->format('d M Y') }}</span>
                    @if ($order->isLate())
                        <x-badge color="red" class="ml-1">Late</x-badge>
                    @endif
                </td>
                <td class="px-4 py-3 text-right tabular-nums">{{ $order->lines_count }}</td>
                <td class="px-4 py-3 text-right tabular-nums">{{ Format::money($order->total) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-4 py-8 text-center text-stone-500">No sales orders found.</td></tr>
        @endforelse
        <x-slot:footer>{{ $orders->links() }}</x-slot:footer>
    </x-table>
</x-layouts.app>
