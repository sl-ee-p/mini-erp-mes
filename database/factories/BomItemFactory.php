<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BomItem;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BomItem>
 */
class BomItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'material_id' => Material::factory(),
            'quantity' => fake()->randomFloat(3, 0.5, 10),
        ];
    }
}
