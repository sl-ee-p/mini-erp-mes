<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Models\Product;
use App\Models\SalesOrder;

/**
 * Replaces the lines of an order and recalculates its total. Unit prices default to the product list price.
 *
 * @phpstan-type OrderLineInput array{product_id: int, quantity: int, unit_price?: float|null}
 */
class SyncSalesOrderLines
{
    /**
     * @param  list<OrderLineInput>  $lines
     */
    public function execute(SalesOrder $order, array $lines): void
    {
        $products = Product::query()->findMany(array_column($lines, 'product_id'))->keyBy('id');

        $order->lines()->delete();

        $total = 0.0;

        foreach ($lines as $line) {
            $unitPrice = round($line['unit_price'] ?? $products[$line['product_id']]->price, 2);
            $lineTotal = round($unitPrice * $line['quantity'], 2);
            $total += $lineTotal;

            $order->lines()->create([
                'product_id' => $line['product_id'],
                'quantity' => $line['quantity'],
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
        }

        $order->update(['total' => round($total, 2)]);
    }
}
