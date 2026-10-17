<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Bare production order without steps. Use ConfirmSalesOrder to release realistic orders in tests.
 *
 * @extends Factory<ProductionOrder>
 */
class ProductionOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'PO-'.now()->year.'-'.fake()->unique()->numerify('9###'),
            'sales_order_id' => SalesOrder::factory(),
            'sales_order_line_id' => fn (array $attributes) => SalesOrderLine::factory()->create(['sales_order_id' => $attributes['sales_order_id']]),
            'product_id' => fn (array $attributes) => SalesOrderLine::find($attributes['sales_order_line_id'])->product_id ?? Product::factory(),
            'quantity' => 2,
            'status' => ProductionOrderStatus::Planned,
            'due_date' => today()->addWeeks(2),
            'planned_minutes' => 120,
        ];
    }
}
