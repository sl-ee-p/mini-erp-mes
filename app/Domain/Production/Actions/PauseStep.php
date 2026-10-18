<?php

declare(strict_types=1);

namespace App\Domain\Production\Actions;

use App\Domain\Production\Concerns\LocksStepForWork;
use App\Domain\Production\Enums\StepStatus;
use App\Models\ProductionOrderStep;

/**
 * Stops the clock on a running step (break, missing material, shift change…).
 */
class PauseStep
{
    use LocksStepForWork;

    public function execute(ProductionOrderStep $step): ProductionOrderStep
    {
        return $this->withLockedStep($step, function (ProductionOrderStep $step): ProductionOrderStep {
            $step->status->assertCanTransitionTo(StepStatus::Paused, "Step “{$step->name}”");

            $step->actual_seconds += $step->openTimeLog?->close() ?? 0;
            $step->status = StepStatus::Paused;
            $step->save();

            return $step;
        });
    }
}
