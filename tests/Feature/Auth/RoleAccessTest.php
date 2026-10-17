<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{UserRole, string, int}>
     */
    public static function screenAccessMatrix(): array
    {
        return [
            'admin sees dashboard' => [UserRole::Admin, 'dashboard', 200],
            'planner sees dashboard' => [UserRole::Planner, 'dashboard', 200],
            'sales sees dashboard' => [UserRole::Sales, 'dashboard', 200],
            'operator blocked from dashboard' => [UserRole::Operator, 'dashboard', 403],
            'admin sees shop floor' => [UserRole::Admin, 'shop-floor.index', 200],
            'planner sees shop floor' => [UserRole::Planner, 'shop-floor.index', 200],
            'operator sees shop floor' => [UserRole::Operator, 'shop-floor.index', 200],
            'sales blocked from shop floor' => [UserRole::Sales, 'shop-floor.index', 403],
            'admin manages users' => [UserRole::Admin, 'users.index', 200],
            'planner blocked from users' => [UserRole::Planner, 'users.index', 403],
            'operator blocked from users' => [UserRole::Operator, 'users.index', 403],
            'sales blocked from users' => [UserRole::Sales, 'users.index', 403],
        ];
    }

    #[DataProvider('screenAccessMatrix')]
    public function test_screen_access_by_role(UserRole $role, string $route, int $expectedStatus): void
    {
        $this->actingAs(User::factory()->role($role)->create())
            ->get(route($route))
            ->assertStatus($expectedStatus);
    }

    public function test_inactive_admin_is_not_granted_abilities_after_login_attempt(): void
    {
        $admin = User::factory()->admin()->inactive()->create();

        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'password']);

        $this->get(route('users.index'))->assertRedirect(route('login'));
    }
}
