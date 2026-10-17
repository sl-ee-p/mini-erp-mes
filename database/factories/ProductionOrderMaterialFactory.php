<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Material;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionOrderMaterial>
 */
class ProductionOrderMaterialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_order_id' => ProductionOrder::factory(),
            'material_id' => Material::factory(),
            'quantity_per_unit' => 1,
            'required_qty' => 2,
            'consumed_qty' => 0,
        ];
    }
}
