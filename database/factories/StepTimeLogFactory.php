<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProductionOrderStep;
use App\Models\StepTimeLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StepTimeLog>
 */
class StepTimeLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_order_step_id' => ProductionOrderStep::factory(),
            'user_id' => User::factory()->operator(),
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'seconds' => 3600,
        ];
    }
}
