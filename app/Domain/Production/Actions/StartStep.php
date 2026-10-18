<?php

declare(strict_types=1);

namespace App\Domain\Production\Actions;

use App\Domain\Production\Concerns\LocksStepForWork;
use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Enums\StepStatus;
use App\Domain\Production\Events\StepStarted;
use App\Domain\Production\Exceptions\ProductionException;
use App\Models\ProductionOrderStep;
use App\Models\StepTimeLog;
use App\Models\User;

/**
 * Starts or resumes a step and opens a time interval for the operator.
 */
class StartStep
{
    use LocksStepForWork;

    public function execute(ProductionOrderStep $step, User $operator): ProductionOrderStep
    {
        return $this->withLockedStep($step, function (ProductionOrderStep $step) use ($operator): ProductionOrderStep {
            $step->status->assertCanTransitionTo(StepStatus::Running, "Step “{$step->name}”");

            if (! in_array($step->productionOrder->status, ProductionOrderStatus::active(), true)) {
                throw ProductionException::orderNotActive($step);
            }

            $this->ensureOperatorIsFree($operator);

            if ($step->status === StepStatus::Pending) {
                $step->input_qty = $this->inputQuantity($step);
                $step->started_at = now();
            }

            $step->status = StepStatus::Running;
            $step->operator_id = $operator->id;
            $step->save();

            $step->timeLogs()->create(['user_id' => $operator->id, 'started_at' => now()]);

            StepStarted::dispatch($step, $operator);

            return $step;
        });
    }

    /**
     * Units entering the step: the order quantity for the first step, otherwise the previous step's good output.
     */
    private function inputQuantity(ProductionOrderStep $step): int
    {
        $previous = $step->previousStep();

        if ($previous === null) {
            return $step->productionOrder->quantity;
        }

        if ($previous->status !== StepStatus::Completed) {
            throw ProductionException::previousStepNotCompleted($step, $previous);
        }

        return (int) $previous->good_qty;
    }

    /**
     * The operator's row is locked first, so two steps started at the same time by one operator cannot both pass.
     */
    private function ensureOperatorIsFree(User $operator): void
    {
        User::query()->whereKey($operator->id)->lockForUpdate()->first();

        $openLog = StepTimeLog::query()
            ->with('step.productionOrder')
            ->where('user_id', $operator->id)
            ->whereNull('ended_at')
            ->first();

        if ($openLog !== null) {
            throw ProductionException::operatorBusy($openLog->step);
        }
    }
}
