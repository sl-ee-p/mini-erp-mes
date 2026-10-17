<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'C'.fake()->unique()->numerify('#####'),
            'name' => fake()->company().' '.fake()->randomElement(['Interiors', 'Home', 'Design Studio', 'Living', 'Furnishings']),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'vat_number' => fake()->bothify('IT###########'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => fake()->randomElement(['IT', 'IT', 'IT', 'CH', 'AT', 'DE', 'FR']),
            'notes' => null,
        ];
    }
}
