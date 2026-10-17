<?php

declare(strict_types=1);

namespace App\Domain\Production\Actions;

use App\Domain\Production\Enums\StepStatus;
use App\Models\ProductionOrderStep;
use Illuminate\Support\Facades\DB;

/**
 * Stops the clock on a running step (break, missing material, shift change…).
 */
class PauseStep
{
    public function execute(ProductionOrderStep $step): ProductionOrderStep
    {
        return DB::transaction(function () use ($step): ProductionOrderStep {
            $step = ProductionOrderStep::query()->lockForUpdate()->findOrFail($step->id);
            $step->status->assertCanTransitionTo(StepStatus::Paused, "Step “{$step->name}”");

            $step->actual_seconds += $step->openTimeLog?->close() ?? 0;
            $step->status = StepStatus::Paused;
            $step->save();

            return $step;
        });
    }
}
