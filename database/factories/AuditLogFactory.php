<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'auditable_type' => 'customer',
            'auditable_id' => Customer::factory(),
            'event' => 'updated',
            'old_values' => ['name' => fake()->company()],
            'new_values' => ['name' => fake()->company()],
            'ip_address' => fake()->ipv4(),
        ];
    }
}
