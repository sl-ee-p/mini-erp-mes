<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_list_and_filter_users(): void
    {
        User::factory()->operator()->create(['name' => 'Workshop Operator']);
        User::factory()->sales()->create(['name' => 'Sales Person']);

        $this->actingAs($this->admin)
            ->get(route('users.index', ['role' => 'operator']))
            ->assertOk()
            ->assertSee('Workshop Operator')
            ->assertDontSee('Sales Person');
    }

    public function test_admin_can_create_a_user(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), [
                'name' => 'New Planner',
                'email' => 'new.planner@oakline.test',
                'role' => 'planner',
                'password' => 'Secret-pass-123',
                'password_confirmation' => 'Secret-pass-123',
            ])
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'new.planner@oakline.test')->firstOrFail();
        $this->assertSame(UserRole::Planner, $user->role);
        $this->assertTrue(Hash::check('Secret-pass-123', $user->password));
    }

    public function test_user_creation_validates_input(): void
    {
        $this->actingAs($this->admin)
            ->post(route('users.store'), ['name' => '', 'email' => $this->admin->email, 'role' => 'janitor'])
            ->assertSessionHasErrors(['name', 'email', 'role', 'password']);
    }

    public function test_admin_can_update_role_and_deactivate_user_without_changing_password(): void
    {
        $user = User::factory()->operator()->create();
        $originalHash = $user->password;

        $this->actingAs($this->admin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'role' => 'planner',
                'is_active' => '0',
                'password' => '',
            ])
            ->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertSame(UserRole::Planner, $user->role);
        $this->assertFalse($user->is_active);
        $this->assertSame($originalHash, $user->password);
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $this->actingAs($this->admin)
            ->put(route('users.update', $this->admin), [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => 'sales',
                'is_active' => '0',
            ])
            ->assertSessionHasErrors(['role', 'is_active']);

        $this->assertSame(UserRole::Admin, $this->admin->refresh()->role);
    }

    public function test_non_admins_cannot_create_users(): void
    {
        $this->actingAs(User::factory()->planner()->create())
            ->post(route('users.store'), [
                'name' => 'Sneaky',
                'email' => 'sneaky@oakline.test',
                'role' => 'admin',
                'password' => 'Secret-pass-123',
                'password_confirmation' => 'Secret-pass-123',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@oakline.test']);
    }
}
