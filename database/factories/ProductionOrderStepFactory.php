<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Production\Enums\StepStatus;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderStep;
use App\Models\WorkCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionOrderStep>
 */
class ProductionOrderStepFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_order_id' => ProductionOrder::factory(),
            'work_center_id' => WorkCenter::factory(),
            'sequence' => 10,
            'name' => fake()->randomElement(['Cut', 'Sand', 'Assemble', 'Finish', 'Pack']),
            'consumes_materials' => false,
            'status' => StepStatus::Pending,
            'planned_minutes' => 30,
        ];
    }
}
