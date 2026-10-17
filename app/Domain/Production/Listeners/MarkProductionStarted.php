<?php

declare(strict_types=1);

namespace App\Domain\Production\Listeners;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Events\StepStarted;
use App\Domain\Sales\Enums\SalesOrderStatus;

/**
 * The first step started moves the production order, and its sales order, into production.
 */
class MarkProductionStarted
{
    public function handle(StepStarted $event): void
    {
        $productionOrder = $event->step->productionOrder;

        if ($productionOrder->status === ProductionOrderStatus::Planned) {
            $productionOrder->transitionTo(ProductionOrderStatus::InProgress);
        }

        $salesOrder = $productionOrder->salesOrder;

        if ($salesOrder->status === SalesOrderStatus::Confirmed) {
            $salesOrder->transitionTo(SalesOrderStatus::InProduction);
        }
    }
}
