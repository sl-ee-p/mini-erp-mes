<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Exceptions\ProductionException;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Models\ProductionOrder;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

/**
 * Cancels an order and its production orders, as long as no work has started on the shop floor.
 */
class CancelSalesOrder
{
    public function execute(SalesOrder $order): SalesOrder
    {
        return DB::transaction(function () use ($order): SalesOrder {
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);

            $order->status->assertCanTransitionTo(SalesOrderStatus::Cancelled, "Sales order {$order->number}");

            $productionOrders = $order->productionOrders()->lockForUpdate()->get();

            if ($productionOrders->contains(fn (ProductionOrder $productionOrder) => $productionOrder->status !== ProductionOrderStatus::Planned)) {
                throw ProductionException::productionAlreadyStarted($order);
            }

            $productionOrders->each(fn (ProductionOrder $productionOrder) => $productionOrder->transitionTo(ProductionOrderStatus::Cancelled));

            $order->transitionTo(SalesOrderStatus::Cancelled);

            return $order;
        });
    }
}
