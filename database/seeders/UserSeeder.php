<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * One demo account per role (password: "password") plus a few extra shop-floor operators.
     */
    public function run(): void
    {
        $accounts = [
            ['name' => 'Alex Morgan', 'email' => 'admin@oakline.test', 'role' => UserRole::Admin],
            ['name' => 'Priya Desai', 'email' => 'planner@oakline.test', 'role' => UserRole::Planner],
            ['name' => 'Tomás Ruiz', 'email' => 'operator@oakline.test', 'role' => UserRole::Operator],
            ['name' => 'Hannah Becker', 'email' => 'sales@oakline.test', 'role' => UserRole::Sales],
            ['name' => 'Kenji Watanabe', 'email' => 'kenji@oakline.test', 'role' => UserRole::Operator],
            ['name' => 'Ines Duarte', 'email' => 'ines@oakline.test', 'role' => UserRole::Operator],
        ];

        // No factories here: Faker is a dev dependency and is not installed in the production image.
        foreach ($accounts as $account) {
            User::query()->firstOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'role' => $account['role'], 'password' => 'password', 'is_active' => true],
            );
        }
    }
}
