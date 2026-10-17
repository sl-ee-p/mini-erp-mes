<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'SO-'.now()->year.'-'.fake()->unique()->numerify('9###'),
            'customer_id' => Customer::factory(),
            'status' => SalesOrderStatus::Draft,
            'order_date' => today(),
            'due_date' => today()->addWeeks(4),
            'total' => 0,
        ];
    }

    public function status(SalesOrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'confirmed_at' => $status === SalesOrderStatus::Draft ? null : now(),
        ]);
    }

    public function overdue(int $days = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'order_date' => today()->subWeeks(5),
            'due_date' => today()->subDays($days),
        ]);
    }

    /**
     * Adds one line per product and keeps the order total consistent.
     *
     * @param  array<int, Product>|null  $products
     */
    public function withLines(?array $products = null, int $quantity = 2): static
    {
        return $this->afterCreating(function (SalesOrder $order) use ($products, $quantity): void {
            foreach ($products ?? [Product::factory()->withBomAndRouting()->create()] as $product) {
                SalesOrderLine::factory()->for($order)->for($product)->create([
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'line_total' => round($product->price * $quantity, 2),
                ]);
            }

            $order->update(['total' => $order->lines()->sum('line_total')]);
        });
    }
}
