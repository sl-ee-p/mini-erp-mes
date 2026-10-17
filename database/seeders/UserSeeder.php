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

        foreach ($accounts as $account) {
            User::factory()->role($account['role'])->create([
                'name' => $account['name'],
                'email' => $account['email'],
            ]);
        }
    }
}
