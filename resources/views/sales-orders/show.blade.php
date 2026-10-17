@use('App\Support\Format')
@use('App\Domain\Sales\Enums\SalesOrderStatus')

<x-layouts.app :title="$order->number">
    <x-page-header :title="$order->number">
        <x-slot:actions>
            @if ($order->hasCurrentPdf())
                <x-button variant="secondary" :href="route('sales-orders.pdf.show', $order)">Download PDF</x-button>
            @else
                <form method="POST" action="{{ route('sales-orders.pdf.store', $order) }}">
                    @csrf
                    <x-button variant="secondary">Generate PDF</x-button>
                </form>
            @endif

            @if ($order->isEditable())
                @can('update', $order)
                    <x-button variant="secondary" :href="route('sales-orders.edit', $order)">Edit</x-button>
                @endcan
            @endif

            @if ($order->status->canTransitionTo(SalesOrderStatus::Cancelled))
                @can('cancel', $order)
                    <form method="POST" action="{{ route('sales-orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')">
                        @csrf
                        <x-button variant="danger">Cancel order</x-button>
                    </form>
                @endcan
            @endif

            @if ($order->status->canTransitionTo(SalesOrderStatus::Confirmed))
                @can('confirm', $order)
                    <form method="POST" action="{{ route('sales-orders.confirm', $order) }}">
                        @csrf
                        <x-button>Confirm order</x-button>
                    </form>
                @endcan
            @endif

            @if ($order->status->canTransitionTo(SalesOrderStatus::Shipped))
                @can('ship', $order)
                    <form method="POST" action="{{ route('sales-orders.ship', $order) }}" onsubmit="return confirm('Ship this order and issue finished goods from stock?')">
                        @csrf
                        <x-button variant="success">Ship order</x-button>
                    </form>
                @endcan
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 flex flex-wrap items-center gap-3 text-sm">
        <x-badge :color="$order->status->color()" class="text-sm">{{ $order->status->label() }}</x-badge>
        @if ($order->isLate())
            <x-badge color="red">Late — due {{ $order->due_date->diffForHumans() }}</x-badge>
        @endif
        <span class="text-stone-500">Created by {{ $order->creator?->name ?? 'System' }} on {{ $order->created_at->format('d M Y') }}</span>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-table>
                <x-slot:head>
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3 text-right">Qty</th>
                        <th class="px-4 py-3 text-right">In stock</th>
                        <th class="px-4 py-3 text-right">Unit price</th>
                        <th class="px-4 py-3 text-right">Amount</th>
                    </tr>
                </x-slot:head>
                @foreach ($order->lines as $line)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('products.show', $line->product) }}" class="font-medium text-brand-700 hover:underline">{{ $line->product->name }}</a>
                            <span class="block font-mono text-xs text-stone-500">{{ $line->product->sku }}</span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $line->quantity }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-stone-500">{{ Format::quantity($line->product->material->stock_qty) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::money($line->unit_price) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ Format::money($line->line_total) }}</td>
                    </tr>
                @endforeach
                <tr class="bg-stone-50 font-semibold">
                    <td class="px-4 py-3" colspan="4">Total (excl. VAT)</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ Format::money($order->total) }}</td>
                </tr>
            </x-table>

            @include('sales-orders._production')
        </div>

        <div class="space-y-6">
            <x-card title="Customer">
                <a href="{{ route('customers.show', $order->customer) }}" class="font-medium text-brand-700 hover:underline">{{ $order->customer->name }}</a>
                <p class="text-sm text-stone-500">{{ $order->customer->code }} · {{ $order->customer->city }} ({{ $order->customer->country }})</p>
            </x-card>

            <x-card title="Timeline">
                <ol class="space-y-3 text-sm">
                    @foreach ([
                        'Ordered' => $order->order_date,
                        'Requested delivery' => $order->due_date,
                        'Confirmed' => $order->confirmed_at,
                        'Production completed' => $order->completed_at,
                        'Shipped' => $order->shipped_at,
                        'Cancelled' => $order->cancelled_at,
                    ] as $label => $date)
                        @if ($date || in_array($label, ['Ordered', 'Requested delivery'], true))
                            <li class="flex justify-between gap-4">
                                <span class="text-stone-500">{{ $label }}</span>
                                <span class="tabular-nums">{{ $date?->format('d M Y') ?? '—' }}</span>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </x-card>

            @if ($order->notes)
                <x-card title="Notes">
                    <p class="whitespace-pre-line text-sm">{{ $order->notes }}</p>
                </x-card>
            @endif
        </div>
    </div>
</x-layouts.app>
