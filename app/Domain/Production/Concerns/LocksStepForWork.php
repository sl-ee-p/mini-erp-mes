<?php

declare(strict_types=1);

namespace App\Domain\Production\Concerns;

use App\Models\ProductionOrder;
use App\Models\ProductionOrderStep;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

/**
 * Runs a shop-floor action on a step while holding row locks on its sales order, production order and the step.
 *
 * Locks are always taken top-down (sales order → production order → step), the same order the sales order
 * actions use (e.g. CancelSalesOrder), so a step action and an order action on the same sales order serialise
 * instead of overwriting each other's status changes or deadlocking. Serialising on the sales order also means
 * two production orders of one sales order completing at the same time see each other's result, so the last
 * one moves the sales order to completed.
 *
 * The locks are the first statements of the transaction: InnoDB takes its read snapshot at the first plain
 * SELECT, so every later read sees what was committed by whoever held these locks before.
 */
trait LocksStepForWork
{
    /**
     * @template TResult
     *
     * @param  callable(ProductionOrderStep): TResult  $callback  receives the locked step, with its locked
     *                                                            production order and sales order set as relations
     * @return TResult
     */
    protected function withLockedStep(ProductionOrderStep $step, callable $callback): mixed
    {
        // Foreign keys never change, so reading this before the transaction is safe and keeps the snapshot fresh.
        $salesOrderId = ProductionOrder::query()->whereKey($step->production_order_id)->value('sales_order_id');

        return DB::transaction(function () use ($step, $salesOrderId, $callback): mixed {
            $salesOrder = SalesOrder::query()->lockForUpdate()->findOrFail($salesOrderId);
            $productionOrder = ProductionOrder::query()->lockForUpdate()->findOrFail($step->production_order_id);
            $locked = ProductionOrderStep::query()->lockForUpdate()->findOrFail($step->id);

            $productionOrder->setRelation('salesOrder', $salesOrder);
            $locked->setRelation('productionOrder', $productionOrder);

            return $callback($locked);
        });
    }
}
