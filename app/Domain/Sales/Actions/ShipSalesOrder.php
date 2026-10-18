<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Domain\Sales\Events\SalesOrderShipped;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Ships a completed order: finished goods leave the warehouse in the same transaction as the status change.
 */
class ShipSalesOrder
{
    public function __construct(private readonly RecordStockMovement $recordStockMovement) {}

    public function execute(SalesOrder $order, ?User $user = null): SalesOrder
    {
        return DB::transaction(function () use ($order, $user): SalesOrder {
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);
            $order->status->assertCanTransitionTo(SalesOrderStatus::Shipped, "Sales order {$order->number}");

            // Finished goods are locked in material id order, so concurrent shipments cannot deadlock.
            $lines = $order->lines()->with('product.material')->get()->sortBy(fn (SalesOrderLine $line): int => $line->product->material_id);

            foreach ($lines as $line) {
                $this->recordStockMovement->execute(
                    $line->product->material,
                    StockMovementType::Out,
                    $line->quantity,
                    "Shipped on {$order->number}",
                    $order,
                    $user,
                );
            }

            $order->transitionTo(SalesOrderStatus::Shipped);

            SalesOrderShipped::dispatch($order);

            return $order;
        });
    }
}
