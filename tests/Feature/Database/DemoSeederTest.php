<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_can_sign_in_after_seeding(): void
    {
        $this->seed();

        $this->assertSame(6, User::query()->count());
        $this->assertSame(15, Customer::query()->count());

        $this->post(route('login.store'), ['email' => 'admin@oakline.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_seeders_do_not_depend_on_faker(): void
    {
        // The production image is built with --no-dev, so Faker is not available there.
        foreach (glob(database_path('seeders/*.php')) ?: [] as $seeder) {
            $source = (string) file_get_contents($seeder);

            $this->assertDoesNotMatchRegularExpression('/::factory\(|fake\(/', $source, basename($seeder).' must not use factories or Faker.');
        }
    }
}
