<?php

declare(strict_types=1);

namespace App\Domain\Production\Listeners;

use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Events\ProductionOrderCompleted;
use App\Domain\Production\Events\StepCompleted;

/**
 * Accumulates scrap on the production order and, after the last step, books finished goods into stock
 * and completes the order.
 */
class AdvanceProductionOrder
{
    public function __construct(private readonly RecordStockMovement $recordStockMovement) {}

    public function handle(StepCompleted $event): void
    {
        $step = $event->step;
        $productionOrder = $step->productionOrder;

        $productionOrder->scrap_qty += (int) $step->scrap_qty;

        if (! $step->isLastStep()) {
            $productionOrder->save();

            return;
        }

        $productionOrder->good_qty = (int) $step->good_qty;

        if ($productionOrder->good_qty > 0) {
            $this->recordStockMovement->execute(
                $productionOrder->product->material,
                StockMovementType::In,
                $productionOrder->good_qty,
                "Produced by {$productionOrder->number}",
                $productionOrder,
                $event->user,
            );
        }

        $productionOrder->transitionTo(ProductionOrderStatus::Completed);

        ProductionOrderCompleted::dispatch($productionOrder);
    }
}
