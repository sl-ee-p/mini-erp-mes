@use('App\Support\Format')
@use('App\Domain\Reporting\DashboardSummary')

<x-layouts.app title="Dashboard">
    <x-page-header title="Dashboard" :subtitle="'Welcome back, '.auth()->user()->name.'. Here is where the workshop stands today, '.today()->format('l j F').'.'" />

    <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-3 2xl:grid-cols-6">
        <x-stat label="Open sales orders" :value="$openOrders['count']" :hint="Format::money($openOrders['value']).' to deliver · '.$draftCount.' '.str('draft')->plural($draftCount)"
                :href="auth()->user()->can('viewAny', App\Models\SalesOrder::class) ? route('sales-orders.index') : null" />
        <x-stat label="Late orders" :value="$lateOrderCount" :tone="$lateOrderCount > 0 ? 'danger' : 'default'"
                :hint="$lateOrderCount > 0 ? 'Past their delivery date' : 'Everything on schedule'"
                :href="auth()->user()->can('viewAny', App\Models\SalesOrder::class) ? route('sales-orders.index', ['late' => 1]) : null" />
        <x-stat label="Ready to ship" :value="$readyToShipCount" :tone="$readyToShipCount > 0 ? 'good' : 'default'"
                hint="Production finished"
                :href="auth()->user()->can('viewAny', App\Models\SalesOrder::class) ? route('sales-orders.index', ['status' => 'completed']) : null" />
        <x-stat label="Production orders" :value="$production['planned'] + $production['in_progress']"
                :hint="$production['in_progress'].' in progress · '.$production['running_steps'].' '.str('step')->plural($production['running_steps']).' running'"
                :href="route('production-orders.index')" />
        <x-stat label="Material shortages" :value="$shortages->count()" :tone="$shortages->isNotEmpty() ? 'danger' : 'default'"
                :hint="$lowStockCount.' below minimum stock'"
                :href="route('materials.index', ['shortage' => 1])" />
        <x-stat label="Scrap rate" :value="$scrapRate === null ? '—' : $scrapRate.'%'" :tone="$scrapRate > 5 ? 'warning' : 'default'"
                :hint="'Completed orders, last '.DashboardSummary::SCRAP_WINDOW_DAYS.' days'" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="grid gap-6 lg:grid-cols-2">
                <x-card title="Late orders" :padding="false">
                    <ul class="divide-y divide-stone-100 text-sm">
                        @forelse ($lateOrders as $order)
                            <li class="flex items-center justify-between gap-4 px-5 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('sales-orders.show', $order) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $order->number }}</a>
                                    <p class="truncate text-stone-600">{{ $order->customer->name }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <x-badge :color="$order->status->color()">{{ $order->status->label() }}</x-badge>
                                    <p class="mt-1 text-xs font-medium text-red-600">Due {{ $order->due_date->format('d M') }} · {{ (int) $order->due_date->diffInDays(today()) }}d late</p>
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-stone-500">No late orders.</li>
                        @endforelse
                    </ul>
                </x-card>

                <x-card title="Ready to ship" :padding="false">
                    <ul class="divide-y divide-stone-100 text-sm">
                        @forelse ($readyToShip as $order)
                            <li class="flex items-center justify-between gap-4 px-5 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('sales-orders.show', $order) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $order->number }}</a>
                                    <p class="truncate text-stone-600">{{ $order->customer->name }}</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="tabular-nums">{{ Format::money($order->total) }}</p>
                                    <p @class(['mt-1 text-xs', 'font-medium text-red-600' => $order->isLate(), 'text-stone-500' => ! $order->isLate()])>Due {{ $order->due_date->format('d M') }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-stone-500">Nothing waiting to ship.</li>
                        @endforelse
                    </ul>
                </x-card>
            </div>

            <x-card title="Work center load">
                <x-slot:headerActions>
                    <span class="hidden text-xs text-stone-500 sm:inline">Remaining planned work on open steps</span>
                </x-slot:headerActions>
                @php($maxLoad = max(1, $workCenterLoad->max('remaining_minutes')))
                <ul class="space-y-4">
                    @foreach ($workCenterLoad as $row)
                        @php($summary = $row['open_steps'].' open '.str('step')->plural($row['open_steps']).($row['running'] > 0 ? ', '.$row['running'].' running' : ''))
                        <li class="text-sm" title="{{ $row['work_center']->name }}: {{ Format::minutes($row['remaining_minutes']) }} remaining · {{ $summary }}">
                            <div class="mb-1.5 flex items-baseline justify-between gap-3">
                                <a href="{{ auth()->user()->can('use-shop-floor') ? route('shop-floor.index', ['work_center' => $row['work_center']->id]) : route('production-orders.index') }}"
                                   class="truncate font-medium text-stone-700 hover:text-brand-700">{{ $row['work_center']->name }}</a>
                                <p class="shrink-0 text-right">
                                    <span class="font-semibold text-stone-900">{{ Format::minutes($row['remaining_minutes']) }}</span>
                                    <span class="text-xs text-stone-500">· {{ $summary }}</span>
                                </p>
                            </div>
                            <div class="h-2.5 overflow-hidden rounded bg-brand-100" aria-hidden="true">
                                <div class="h-full rounded bg-brand-500" style="width: {{ round($row['remaining_minutes'] / $maxLoad * 100, 1) }}%"></div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-card>

            <x-card title="Recently completed production" :padding="false">
                <ul class="divide-y divide-stone-100 text-sm">
                    @forelse ($recentlyCompleted as $productionOrder)
                        <li class="flex items-center justify-between gap-4 px-5 py-3">
                            <div class="min-w-0">
                                <a href="{{ route('production-orders.show', $productionOrder) }}" class="font-mono text-xs font-semibold text-brand-700 hover:underline">{{ $productionOrder->number }}</a>
                                <p class="truncate text-stone-600">{{ $productionOrder->quantity }} × {{ $productionOrder->product->name }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p>{{ $productionOrder->good_qty }} good @if ($productionOrder->scrap_qty > 0)<span class="text-red-600">· {{ $productionOrder->scrap_qty }} scrap</span>@endif</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $productionOrder->completed_at?->diffForHumans() }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-stone-500">No production completed yet.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>

        <div class="space-y-6">
            <x-card title="Short for released production" :padding="false">
                <ul class="divide-y divide-stone-100 text-sm">
                    @forelse ($shortages->take(DashboardSummary::LIST_LIMIT) as $row)
                        <li class="px-5 py-3">
                            <div class="flex items-baseline justify-between gap-4">
                                <a href="{{ route('materials.show', $row['material']) }}" class="truncate font-medium text-brand-700 hover:underline">{{ $row['material']->name }}</a>
                                <span class="shrink-0 font-semibold tabular-nums text-red-600">−{{ Format::quantity($row['shortage'], $row['material']->unit) }}</span>
                            </div>
                            <p class="text-xs text-stone-500">Needs {{ Format::quantity($row['allocated'], $row['material']->unit) }} · on hand {{ Format::quantity($row['material']->stock_qty, $row['material']->unit) }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-stone-500">Released production is fully covered by stock.</li>
                    @endforelse
                </ul>
            </x-card>

            <x-card title="Below minimum stock" :padding="false">
                <x-slot:headerActions>
                    @if ($lowStockCount > 0)
                        <a href="{{ route('materials.index', ['low_stock' => 1]) }}" class="text-xs font-medium text-brand-700 hover:underline">View all {{ $lowStockCount }}</a>
                    @endif
                </x-slot:headerActions>
                <ul class="divide-y divide-stone-100 text-sm">
                    @forelse ($lowStock as $material)
                        <li class="px-5 py-3">
                            <div class="flex items-baseline justify-between gap-4">
                                <a href="{{ route('materials.show', $material) }}" class="truncate font-medium text-brand-700 hover:underline">{{ $material->name }}</a>
                                <span class="shrink-0 tabular-nums">{{ Format::quantity($material->stock_qty, $material->unit) }}</span>
                            </div>
                            <p class="text-xs text-stone-500">Minimum {{ Format::quantity($material->min_stock, $material->unit) }} · reorder {{ Format::quantity($material->shortage(), $material->unit) }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-stone-500">All materials above minimum.</li>
                    @endforelse
                </ul>
            </x-card>
        </div>
    </div>
</x-layouts.app>
