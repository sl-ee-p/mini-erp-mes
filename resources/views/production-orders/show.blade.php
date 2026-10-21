@use('App\Support\Format')
@use('App\Domain\Production\Enums\StepStatus')

<x-layouts.app :title="$productionOrder->number">
    <x-page-header :title="$productionOrder->number" :subtitle="$productionOrder->quantity.' × '.$productionOrder->product->name">
        <x-slot:actions>
            @can('view', $productionOrder->salesOrder)
                <x-button variant="secondary" :href="route('sales-orders.show', $productionOrder->salesOrder)">Sales order {{ $productionOrder->salesOrder->number }}</x-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-card><p class="text-xs uppercase text-stone-500">Status</p><x-badge :color="$productionOrder->status->color()" class="mt-1">{{ $productionOrder->status->label() }}</x-badge></x-card>
        <x-card><p class="text-xs uppercase text-stone-500">Progress</p><x-progress-bar :percent="$productionOrder->progressPercent()" class="mt-2" /></x-card>
        <x-card><p class="text-xs uppercase text-stone-500">Due</p><p @class(['mt-1 font-semibold', 'text-red-600' => $productionOrder->isLate()])>{{ $productionOrder->due_date->format('d M Y') }}</p></x-card>
        <x-card><p class="text-xs uppercase text-stone-500">Planned / actual</p><p class="mt-1 font-semibold tabular-nums">{{ Format::minutes($productionOrder->planned_minutes) }} / {{ Format::minutes($productionOrder->actualMinutes()) }}</p></x-card>
        <x-card><p class="text-xs uppercase text-stone-500">Good / scrap</p><p class="mt-1 font-semibold tabular-nums">{{ $productionOrder->good_qty }} / <span @class(['text-red-600' => $productionOrder->scrap_qty > 0])>{{ $productionOrder->scrap_qty }}</span></p></x-card>
    </div>

    <x-table class="mb-6">
        <x-slot:head>
            <tr>
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">Step</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">In</th>
                <th class="px-4 py-3 text-right">Good</th>
                <th class="px-4 py-3 text-right">Scrap</th>
                <th class="px-4 py-3 text-right">Planned</th>
                <th class="px-4 py-3 text-right">Actual</th>
                <th class="px-4 py-3">Operator</th>
                @can('use-shop-floor')
                    <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                @endcan
            </tr>
        </x-slot:head>
        @php($previousCompleted = true)
        @foreach ($productionOrder->steps as $step)
            <tr>
                <td class="px-4 py-3 text-stone-500">{{ $step->sequence }}</td>
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $step->name }}</p>
                    <p class="text-xs text-stone-500">{{ $step->workCenter->name }} @if ($step->consumes_materials) · <span class="text-amber-700">consumes BOM</span> @endif</p>
                </td>
                <td class="px-4 py-3"><x-badge :color="$step->status->color()">{{ $step->status->label() }}</x-badge></td>
                <td class="px-4 py-3 text-right tabular-nums">{{ $step->input_qty ?? '—' }}</td>
                <td class="px-4 py-3 text-right tabular-nums">{{ $step->good_qty ?? '—' }}</td>
                <td @class(['px-4 py-3 text-right tabular-nums', 'text-red-600' => $step->scrap_qty > 0])>{{ $step->scrap_qty ?? '—' }}</td>
                <td class="px-4 py-3 text-right tabular-nums text-stone-500">{{ Format::minutes($step->planned_minutes) }}</td>
                <td class="px-4 py-3 text-right tabular-nums" x-data="stepTimer({{ $step->elapsedSeconds() }}, {{ $step->status === StepStatus::Running ? 'true' : 'false' }})" x-text="display">{{ \App\Support\Format::duration($step->elapsedSeconds()) }}</td>
                <td class="px-4 py-3 text-stone-600">{{ $step->operator?->name ?? '—' }}</td>
                @can('use-shop-floor')
                    <td class="px-4 py-3">
                        @if ($previousCompleted && $productionOrder->status->transitions() !== [])
                            @include('production-orders._step-actions', ['step' => $step])
                        @endif
                    </td>
                @endcan
            </tr>
            @php($previousCompleted = $step->status === StepStatus::Completed)
        @endforeach
    </x-table>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-card title="Materials (snapshot of BOM at release)" :padding="false">
            <table class="min-w-full text-sm">
                <thead class="bg-stone-50 text-left text-xs uppercase text-stone-500">
                    <tr><th class="px-5 py-2">Material</th><th class="px-5 py-2 text-right">Required</th><th class="px-5 py-2 text-right">Consumed</th><th class="px-5 py-2 text-right">On hand</th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($productionOrder->materials as $line)
                        <tr>
                            <td class="px-5 py-2"><a href="{{ route('materials.show', $line->material) }}" class="text-brand-700 hover:underline">{{ $line->material->name }}</a></td>
                            <td class="px-5 py-2 text-right tabular-nums">{{ Format::quantity($line->required_qty, $line->material->unit) }}</td>
                            <td class="px-5 py-2 text-right tabular-nums">{{ Format::quantity($line->consumed_qty) }}</td>
                            <td @class(['px-5 py-2 text-right tabular-nums', 'text-red-600 font-semibold' => $line->consumed_qty == 0 && $line->material->stock_qty < $line->required_qty])>{{ Format::quantity($line->material->stock_qty) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-stone-500">No materials.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>

        <x-card title="Time log" :padding="false">
            <ul class="divide-y divide-stone-100 text-sm">
                @forelse ($productionOrder->steps->flatMap->timeLogs->sortByDesc('started_at') as $log)
                    <li class="flex justify-between gap-4 px-5 py-2">
                        <span>{{ $productionOrder->steps->firstWhere('id', $log->production_order_step_id)?->name }} · {{ $log->user->name }}</span>
                        <span class="tabular-nums text-stone-500">{{ \App\Support\Format::dateTime($log->started_at, 'd M H:i') }} – {{ \App\Support\Format::dateTime($log->ended_at, 'H:i') ?? 'running' }} ({{ Format::minutes(($log->seconds ?? 0) / 60) }})</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-stone-500">No work logged yet.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</x-layouts.app>
