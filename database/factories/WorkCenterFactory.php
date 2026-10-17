<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\WorkCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkCenter>
 */
class WorkCenterFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'WC-'.fake()->unique()->numerify('###'),
            'name' => fake()->randomElement(['Cutting', 'Assembly', 'Sanding', 'Finishing', 'Packing']),
        ];
    }
}
