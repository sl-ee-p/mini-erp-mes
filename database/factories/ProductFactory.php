<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BomItem;
use App\Models\Material;
use App\Models\Product;
use App\Models\RoutingStep;
use App\Models\WorkCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku' => 'FG-'.fake()->unique()->numerify('#####'),
            'name' => fake()->randomElement(['Oak', 'Walnut', 'Ash', 'Cherry']).' '.fake()->randomElement(['dining table', 'bookshelf', 'nightstand', 'bench', 'desk']),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 150, 2500),
            'material_id' => fn (array $attributes) => Material::factory()->finished()->create([
                'sku' => $attributes['sku'],
                'name' => $attributes['name'],
            ]),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    /**
     * A ready-to-produce product: two raw materials consumed at the first of three routing steps.
     */
    public function withBomAndRouting(float $rawStock = 1000): static
    {
        return $this->afterCreating(function (Product $product) use ($rawStock): void {
            foreach ([2.5, 8] as $quantity) {
                BomItem::factory()->for($product)->create([
                    'material_id' => Material::factory()->stocked($rawStock),
                    'quantity' => $quantity,
                ]);
            }

            foreach (['Cutting', 'Assembly', 'Finishing'] as $index => $name) {
                RoutingStep::factory()->for($product)->create([
                    'work_center_id' => WorkCenter::factory()->create(['name' => $name]),
                    'sequence' => ($index + 1) * 10,
                    'name' => $name,
                    'planned_minutes_per_unit' => 10 * ($index + 1),
                    'consumes_materials' => $index === 0,
                ]);
            }
        });
    }
}
