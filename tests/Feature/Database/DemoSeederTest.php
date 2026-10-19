<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Domain\Inventory\Queries\MaterialRequirements;
use App\Domain\Production\Enums\StepStatus;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\ProductionOrderStep;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\DemoActivitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_accounts_can_sign_in_after_seeding(): void
    {
        Queue::fake();
        $this->seed();

        $this->assertSame(6, User::query()->count());
        $this->assertSame(15, Customer::query()->count());

        $this->post(route('login.store'), ['email' => 'admin@oakline.test', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
    }

    public function test_demo_history_covers_every_workflow_state(): void
    {
        Queue::fake();
        Carbon::setTestNow('2026-10-19 10:30:00');

        $this->seed();

        $statuses = SalesOrder::query()->pluck('status')->map(fn (SalesOrderStatus $status) => $status->value)->unique()->sort()->values()->all();
        $this->assertSame(['cancelled', 'completed', 'confirmed', 'draft', 'in_production', 'shipped'], $statuses);

        $this->assertSame(1, SalesOrder::query()->late()->count());
        $this->assertSame(2, ProductionOrderStep::query()->where('status', StepStatus::Running)->count());
        $this->assertSame(1, ProductionOrderStep::query()->where('status', StepStatus::Paused)->count());
        $this->assertSame(['RM-RUN-450'], app(MaterialRequirements::class)->shortages()->map(fn (array $row) => $row['material']->sku)->all());

        // Nothing is stamped in the future and the clock is back to normal afterwards.
        $this->assertSame(0, StockMovement::query()->where('created_at', '>', now())->count());
        $this->assertSame(0, SalesOrder::query()->where('created_at', '>', now())->count());
        $this->assertSame('2026-10-19 10:30:00', now()->toDateTimeString());
        $this->assertNull(auth()->user());

        // Every document was booked by the person who would have done it.
        $this->assertSame(0, AuditLog::query()->where('auditable_type', 'sales_order')->whereNull('user_id')->count());
    }

    public function test_demo_history_is_not_added_to_a_workshop_with_orders(): void
    {
        Queue::fake();
        $this->seed();
        $orders = SalesOrder::query()->count();

        $this->seed(DemoActivitySeeder::class);

        $this->assertSame($orders, SalesOrder::query()->count());
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
