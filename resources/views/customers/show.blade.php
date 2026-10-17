<x-layouts.app :title="$customer->name">
    <x-page-header :title="$customer->name" :subtitle="$customer->code">
        <x-slot:actions>
            @can('update', $customer)
                <x-button variant="secondary" :href="route('customers.edit', $customer)">Edit</x-button>
            @endcan
            @can('delete', $customer)
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer?')">
                    @csrf
                    @method('DELETE')
                    <x-button variant="danger">Delete</x-button>
                </form>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card title="Details">
            <dl class="space-y-3 text-sm">
                @foreach (['Email' => $customer->email, 'Phone' => $customer->phone, 'VAT number' => $customer->vat_number, 'Address' => trim($customer->address.', '.$customer->city.' ('.$customer->country.')', ', ')] as $label => $value)
                    <div>
                        <dt class="text-xs font-medium uppercase text-stone-500">{{ $label }}</dt>
                        <dd>{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
                @if ($customer->notes)
                    <div>
                        <dt class="text-xs font-medium uppercase text-stone-500">Notes</dt>
                        <dd class="whitespace-pre-line">{{ $customer->notes }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <div class="lg:col-span-2">
            <x-card title="Recent sales orders" :padding="false">
                <x-slot:headerActions>
                    @can('create', App\Models\SalesOrder::class)
                        <x-button size="sm" :href="route('sales-orders.create', ['customer_id' => $customer->id])">New order</x-button>
                    @endcan
                </x-slot:headerActions>
                <ul class="divide-y divide-stone-100 text-sm">
                    @forelse ($orders as $order)
                        <li class="flex items-center justify-between gap-4 px-5 py-3">
                            <a href="{{ route('sales-orders.show', $order) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $order->number }}</a>
                            <x-badge :color="$order->status->color()">{{ $order->status->label() }}</x-badge>
                            <span class="text-stone-500">Due {{ $order->due_date->format('d M Y') }}</span>
                            <span class="tabular-nums">{{ App\Support\Format::money($order->total) }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-stone-500">No orders yet.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
</x-layouts.app>
