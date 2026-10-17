<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a complete demo workshop.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            WorkCenterSeeder::class,
            MaterialSeeder::class,
            ProductSeeder::class,
            CustomerSeeder::class,
        ]);
    }
}
