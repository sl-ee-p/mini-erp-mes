<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\Material;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds ledger rows directly; application code must go through RecordStockMovement.
 *
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 50);

        return [
            'material_id' => Material::factory(),
            'type' => StockMovementType::In,
            'quantity' => $quantity,
            'balance_after' => $quantity,
            'reason' => 'Goods receipt',
        ];
    }
}
