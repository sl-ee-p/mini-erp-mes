<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Domain\Production\Actions\GenerateProductionOrders;
use App\Domain\Production\Exceptions\ProductionException;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Domain\Sales\Events\SalesOrderConfirmed;
use App\Domain\Sales\Exceptions\SalesOrderException;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Illuminate\Support\Facades\DB;

/**
 * Confirms a draft and releases its production orders in the same transaction: an order is never confirmed
 * without the work needed to fulfil it.
 */
class ConfirmSalesOrder
{
    public function __construct(private readonly GenerateProductionOrders $generateProductionOrders) {}

    /**
     * @throws SalesOrderException when the order is empty or contains discontinued products
     * @throws ProductionException when a product has no routing
     */
    public function execute(SalesOrder $order): SalesOrder
    {
        return DB::transaction(function () use ($order): SalesOrder {
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            $order->status->assertCanTransitionTo(SalesOrderStatus::Confirmed, "Sales order {$order->number}");

            $lines = $order->lines()->with('product')->get();

            if ($lines->isEmpty()) {
                throw SalesOrderException::hasNoLines($order);
            }

            $inactive = $lines->reject(fn (SalesOrderLine $line) => $line->product->is_active);

            if ($inactive->isNotEmpty()) {
                throw SalesOrderException::containsInactiveProducts($order, $inactive->map(fn (SalesOrderLine $line) => $line->product->name)->values()->all());
            }

            $order->transitionTo(SalesOrderStatus::Confirmed);

            $this->generateProductionOrders->execute($order);

            SalesOrderConfirmed::dispatch($order);

            return $order;
        });
    }
}
