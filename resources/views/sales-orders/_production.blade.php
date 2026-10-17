@if ($order->productionOrders->isNotEmpty())
    <x-card title="Production" :padding="false">
        <ul class="divide-y divide-stone-100 text-sm">
            @foreach ($order->productionOrders as $productionOrder)
                <li class="flex flex-wrap items-center gap-4 px-5 py-3">
                    <a href="{{ route('production-orders.show', $productionOrder) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $productionOrder->number }}</a>
                    <span class="flex-1">{{ $productionOrder->quantity }} × {{ $productionOrder->product->name }}</span>
                    <x-badge :color="$productionOrder->status->color()">{{ $productionOrder->status->label() }}</x-badge>
                    <x-progress-bar :percent="$productionOrder->progressPercent()" class="w-40" />
                </li>
            @endforeach
        </ul>
    </x-card>
@endif
