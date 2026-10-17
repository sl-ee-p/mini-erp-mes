<?php

declare(strict_types=1);

namespace App\Domain\Production\Actions;

use App\Domain\Production\Enums\StepStatus;
use App\Domain\Production\Events\StepCompleted;
use App\Domain\Production\Exceptions\ProductionException;
use App\Models\ProductionOrderStep;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Completes a running step, recording scrap. Material consumption, finished-goods booking and order status
 * updates happen in StepCompleted listeners within the same transaction.
 */
class CompleteStep
{
    public function execute(ProductionOrderStep $step, User $operator, int $scrapQty = 0): ProductionOrderStep
    {
        return DB::transaction(function () use ($step, $operator, $scrapQty): ProductionOrderStep {
            $step = ProductionOrderStep::query()->lockForUpdate()->findOrFail($step->id);
            $step->status->assertCanTransitionTo(StepStatus::Completed, "Step “{$step->name}”");

            if ($scrapQty < 0 || $scrapQty > (int) $step->input_qty) {
                throw ProductionException::scrapExceedsInput($step, $scrapQty);
            }

            $step->actual_seconds += $step->openTimeLog?->close() ?? 0;
            $step->scrap_qty = $scrapQty;
            $step->good_qty = (int) $step->input_qty - $scrapQty;
            $step->status = StepStatus::Completed;
            $step->completed_at = now();
            $step->operator_id = $operator->id;
            $step->save();

            StepCompleted::dispatch($step, $operator);

            return $step;
        });
    }
}
