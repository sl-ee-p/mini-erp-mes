<?php

declare(strict_types=1);

namespace App\Domain\Production\Listeners;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Events\ProductionOrderCompleted;
use App\Domain\Sales\Enums\SalesOrderStatus;

/**
 * A sales order is complete (ready to ship) once every production order released for it is completed.
 */
class UpdateSalesOrderProgress
{
    public function handle(ProductionOrderCompleted $event): void
    {
        $salesOrder = $event->productionOrder->salesOrder;

        $hasOpenProduction = $salesOrder->productionOrders()
            ->where('status', '!=', ProductionOrderStatus::Completed)
            ->exists();

        if (! $hasOpenProduction && $salesOrder->status === SalesOrderStatus::InProduction) {
            $salesOrder->transitionTo(SalesOrderStatus::Completed);
        }
    }
}
