<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Inventory\Enums\MaterialType;
use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'RM-'.fake()->unique()->numerify('#####'),
            'name' => fake()->randomElement(['Oak board', 'Walnut board', 'Birch plywood', 'Steel hinge', 'Wood screw', 'Linseed oil', 'Drawer runner']).' '.fake()->bothify('##??'),
            'type' => MaterialType::Raw,
            'unit' => UnitOfMeasure::Piece,
            'stock_qty' => 0,
            'min_stock' => 0,
            'unit_cost' => fake()->randomFloat(2, 0.1, 80),
        ];
    }

    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'sku' => 'FG-'.fake()->unique()->numerify('#####'),
            'type' => MaterialType::Finished,
            'unit' => UnitOfMeasure::Piece,
        ]);
    }

    public function unit(UnitOfMeasure $unit): static
    {
        return $this->state(fn (array $attributes) => ['unit' => $unit]);
    }

    public function stocked(float $quantity, float $minimum = 0): static
    {
        return $this->state(fn (array $attributes) => ['stock_qty' => $quantity, 'min_stock' => $minimum]);
    }
}
