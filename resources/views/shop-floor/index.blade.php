@use('App\Support\Format')
@use('App\Domain\Production\Enums\StepStatus')

<x-layouts.app title="Shop floor">
    <div x-data="autoRefresh(30)" @dialog-open.window="busy = true" @dialog-close.window="busy = false">
        <x-page-header title="Shop floor" subtitle="Pick a work center, start a step, complete it with the scrap count. Refreshes every 30 seconds." />

        <nav class="mb-6 flex flex-wrap gap-2" aria-label="Work centers">
            <a href="{{ route('shop-floor.index') }}"
               @class(['rounded-xl px-5 py-3 text-base font-semibold transition', 'bg-stone-900 text-white' => ! $selectedWorkCenter, 'bg-white text-stone-700 ring-1 ring-stone-200 hover:bg-stone-50' => $selectedWorkCenter])>
                All <span class="ml-1 opacity-60">{{ $counts->sum() }}</span>
            </a>
            @foreach ($workCenters as $workCenter)
                <a href="{{ route('shop-floor.index', ['work_center' => $workCenter->id]) }}"
                   @class(['rounded-xl px-5 py-3 text-base font-semibold transition', 'bg-stone-900 text-white' => $selectedWorkCenter === $workCenter->id, 'bg-white text-stone-700 ring-1 ring-stone-200 hover:bg-stone-50' => $selectedWorkCenter !== $workCenter->id])>
                    {{ $workCenter->name }} <span class="ml-1 opacity-60">{{ $counts[$workCenter->id] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        @if ($myStep)
            <section class="mb-8 rounded-2xl bg-emerald-600 p-6 text-white shadow-lg">
                <p class="text-sm font-medium uppercase tracking-wide text-emerald-100">You are working on</p>
                <div class="mt-2 flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p class="text-3xl font-bold">{{ $myStep->name }}</p>
                        <p class="mt-1 text-lg text-emerald-50">{{ $myStep->productionOrder->number }} · {{ $myStep->input_qty }} × {{ $myStep->productionOrder->product->name }}</p>
                    </div>
                    <p class="font-mono text-5xl font-bold tabular-nums" x-data="stepTimer({{ $myStep->elapsedSeconds() }}, true)" x-text="display">{{ gmdate('H:i:s', $myStep->elapsedSeconds()) }}</p>
                </div>
            </section>
        @endif

        <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
            @forelse ($queue as $step)
                @php($order = $step->productionOrder)
                <article @class([
                    'flex flex-col justify-between gap-5 rounded-2xl bg-white p-5 shadow-sm ring-2',
                    'ring-emerald-400' => $step->status === StepStatus::Running,
                    'ring-amber-300' => $step->status === StepStatus::Paused,
                    'ring-stone-200' => $step->status === StepStatus::Pending,
                ])>
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-mono text-sm font-semibold text-stone-500">{{ $order->number }} · step {{ $step->sequence }}</p>
                                <h2 class="mt-1 text-2xl font-bold text-stone-900">{{ $step->name }}</h2>
                                <p class="text-base text-stone-600">{{ $step->workCenter->name }}</p>
                            </div>
                            <x-badge :color="$step->status->color()" class="text-sm">{{ $step->status->label() }}</x-badge>
                        </div>

                        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-base">
                            <div class="col-span-2">
                                <dt class="text-xs uppercase text-stone-500">Product</dt>
                                <dd class="font-medium">{{ $step->input_qty ?? $order->quantity }} × {{ $order->product->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-stone-500">Due</dt>
                                <dd @class(['font-medium', 'text-red-600' => $order->isLate()])>{{ $order->due_date->format('d M') }}@if ($order->isLate()) · late @endif</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase text-stone-500">Time (planned {{ Format::minutes($step->planned_minutes) }})</dt>
                                <dd class="font-mono font-semibold tabular-nums" x-data="stepTimer({{ $step->elapsedSeconds() }}, {{ $step->status === StepStatus::Running ? 'true' : 'false' }})" x-text="display">{{ gmdate('H:i:s', $step->elapsedSeconds()) }}</dd>
                            </div>
                            @if ($step->status !== StepStatus::Pending && $step->operator)
                                <div class="col-span-2 text-sm text-stone-500">
                                    {{ $step->status === StepStatus::Running ? 'In progress by' : 'Last worked by' }} {{ $step->openTimeLog?->user->name ?? $step->operator->name }}
                                </div>
                            @endif
                        </dl>
                    </div>

                    @include('production-orders._step-actions', ['step' => $step, 'size' => 'xl'])
                </article>
            @empty
                <div class="col-span-full rounded-2xl border-2 border-dashed border-stone-300 p-12 text-center text-lg text-stone-500">
                    Nothing to do here right now. 🎉
                </div>
            @endforelse
        </div>
    </div>
</x-layouts.app>
