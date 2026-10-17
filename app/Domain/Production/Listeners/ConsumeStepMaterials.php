<?php

declare(strict_types=1);

namespace App\Domain\Production\Listeners;

use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Production\Events\StepCompleted;

/**
 * Backflushes the bill of materials when the consuming step is completed. Scrapped units consumed material too,
 * so consumption is based on the units that entered the step.
 */
class ConsumeStepMaterials
{
    public function __construct(private readonly RecordStockMovement $recordStockMovement) {}

    public function handle(StepCompleted $event): void
    {
        $step = $event->step;

        if (! $step->consumes_materials) {
            return;
        }

        $productionOrder = $step->productionOrder;

        foreach ($productionOrder->materials()->with('material')->get() as $line) {
            $quantity = round($line->quantity_per_unit * (int) $step->input_qty, 3);

            if ($quantity <= 0) {
                continue;
            }

            $this->recordStockMovement->execute(
                $line->material,
                StockMovementType::Out,
                $quantity,
                "Consumed by {$productionOrder->number} · {$step->name}",
                $step,
                $event->user,
            );

            $line->increment('consumed_qty', $quantity);
        }
    }
}
