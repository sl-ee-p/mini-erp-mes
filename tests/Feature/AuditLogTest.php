<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_are_logged_with_user_and_before_after_values(): void
    {
        $sales = User::factory()->sales()->create();
        $customer = Customer::factory()->create(['name' => 'Old Name']);

        $this->actingAs($sales)->put(route('customers.update', $customer), [...$customer->only(['code', 'country']), 'name' => 'New Name']);

        $log = AuditLog::where('auditable_type', 'customer')->where('event', 'updated')->sole();
        $this->assertSame($sales->id, $log->user_id);
        $this->assertSame(['name' => 'Old Name'], array_intersect_key($log->old_values ?? [], ['name' => true]));
        $this->assertSame('New Name', $log->new_values['name'] ?? null);
        $this->assertArrayNotHasKey('updated_at', $log->new_values ?? []);
    }

    public function test_passwords_are_never_written_to_the_audit_log(): void
    {
        $user = User::factory()->create();
        $user->update(['password' => 'Another-secret-1']);

        $this->assertFalse(AuditLog::where('auditable_type', 'user')->get()->contains(
            fn (AuditLog $log) => array_key_exists('password', $log->new_values ?? []) || array_key_exists('password', $log->old_values ?? []),
        ));
    }

    public function test_saving_without_changes_logs_nothing(): void
    {
        $customer = Customer::factory()->create();
        AuditLog::query()->delete();

        $customer->update(['name' => $customer->name]);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_only_admins_can_browse_the_audit_log(): void
    {
        Customer::factory()->create(['name' => 'Audited Customer']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('audit-logs.index', ['type' => 'customer']))
            ->assertOk()
            ->assertSee('Audited Customer', false);

        $this->actingAs(User::factory()->planner()->create())
            ->get(route('audit-logs.index'))
            ->assertForbidden();
    }
}
