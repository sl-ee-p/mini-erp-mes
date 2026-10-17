<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Production;

use App\Domain\Sales\Actions\ConfirmSalesOrder;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\SalesOrder;

trait ProductionTestHelpers
{
    /**
     * Confirms a fresh sales order for the given product (BOM: 2.5 + 8 per unit, 3 steps) and returns its production order.
     */
    protected function releaseProductionOrder(int $quantity = 4, float $rawStock = 1000, ?Product $product = null): ProductionOrder
    {
        $product ??= Product::factory()->withBomAndRouting($rawStock)->create();
        $salesOrder = SalesOrder::factory()->withLines([$product], $quantity)->create();

        app(ConfirmSalesOrder::class)->execute($salesOrder);

        return $salesOrder->productionOrders()->with('steps')->sole();
    }
}
