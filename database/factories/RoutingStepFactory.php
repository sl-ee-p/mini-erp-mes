<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\RoutingStep;
use App\Models\WorkCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoutingStep>
 */
class RoutingStepFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'work_center_id' => WorkCenter::factory(),
            'sequence' => fake()->unique()->numberBetween(1, 99) * 10,
            'name' => fake()->randomElement(['Cut panels', 'Assemble frame', 'Sand surfaces', 'Apply finish', 'Pack']),
            'planned_minutes_per_unit' => fake()->randomFloat(2, 5, 60),
            'consumes_materials' => false,
        ];
    }
}
