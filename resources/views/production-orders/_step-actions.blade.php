@use('App\Domain\Production\Enums\StepStatus')

{{-- Start / pause / complete controls for one step. $size: 'sm' for tables, 'xl' for the tablet screen. --}}
@php($size ??= 'sm')

<div class="flex flex-wrap items-center gap-2" x-data="{ completing: false }">
    @if ($step->status->canTransitionTo(StepStatus::Running))
        <form method="POST" action="{{ route('production-steps.start', $step) }}">
            @csrf
            <x-button variant="success" :size="$size">{{ $step->status === StepStatus::Paused ? 'Resume' : 'Start' }}</x-button>
        </form>
    @endif

    @if ($step->status === StepStatus::Running)
        <form method="POST" action="{{ route('production-steps.pause', $step) }}">
            @csrf
            <x-button variant="warning" :size="$size">Pause</x-button>
        </form>
        <x-button type="button" :size="$size" @click="completing = true; $dispatch('dialog-open')">Complete</x-button>

        <div x-cloak x-show="completing" class="fixed inset-0 z-50 flex items-center justify-center bg-stone-900/60 p-4" @keydown.escape.window="completing = false; $dispatch('dialog-close')">
            <form method="POST" action="{{ route('production-steps.complete', $step) }}" class="w-full max-w-md space-y-5 rounded-2xl bg-white p-6 shadow-xl"
                  x-data="{ scrap: 0 }" @click.outside="completing = false; $dispatch('dialog-close')">
                @csrf
                <div>
                    <h3 class="text-lg font-semibold">Complete “{{ $step->name }}”</h3>
                    <p class="text-sm text-stone-500">{{ $step->productionOrder->number }} · {{ $step->input_qty }} units processed</p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-stone-700" for="scrap-{{ $step->id }}">Scrapped units</label>
                    <div class="flex items-center gap-3">
                        <button type="button" class="size-14 rounded-xl bg-stone-100 text-2xl font-bold hover:bg-stone-200" @click="scrap = Math.max(0, scrap - 1)">−</button>
                        <input id="scrap-{{ $step->id }}" type="number" name="scrap_qty" min="0" max="{{ $step->input_qty }}" x-model.number="scrap"
                               class="h-14 w-24 rounded-xl border-stone-300 text-center text-2xl font-semibold tabular-nums focus:border-brand-500 focus:ring-brand-500">
                        <button type="button" class="size-14 rounded-xl bg-stone-100 text-2xl font-bold hover:bg-stone-200" @click="scrap = Math.min({{ (int) $step->input_qty }}, scrap + 1)">+</button>
                    </div>
                    <p class="mt-2 text-sm text-stone-600">Good units: <strong x-text="{{ (int) $step->input_qty }} - scrap"></strong></p>
                    @if ($step->consumes_materials)
                        <p class="mt-2 rounded-md bg-amber-50 p-2 text-xs text-amber-800">Completing this step consumes the bill of materials from stock.</p>
                    @endif
                </div>
                <div class="flex gap-3">
                    <x-button size="xl" class="flex-1">Confirm</x-button>
                    <x-button type="button" variant="secondary" size="xl" @click="completing = false; $dispatch('dialog-close')">Cancel</x-button>
                </div>
            </form>
        </div>
    @endif
</div>
