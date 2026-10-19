<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Reporting\DashboardSummary;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\Material;
use App\Models\ProductionOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Domain\Production\ProductionTestHelpers;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use ProductionTestHelpers, RefreshDatabase;

    public function test_an_empty_workshop_renders(): void
    {
        $this->actingAs(User::factory()->planner()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No late orders.')
            ->assertSee('Nothing waiting to ship.')
            ->assertSee('Released production is fully covered by stock.');
    }

    public function test_it_lists_late_orders_and_orders_ready_to_ship(): void
    {
        $late = SalesOrder::factory()->status(SalesOrderStatus::InProduction)->overdue(4)
            ->create(['customer_id' => Customer::factory()->create(['name' => 'Late Customer GmbH'])]);
        $ready = SalesOrder::factory()->status(SalesOrderStatus::Completed)
            ->create(['customer_id' => Customer::factory()->create(['name' => 'Ready Customer Srl']), 'total' => 1234.5]);
        SalesOrder::factory()->status(SalesOrderStatus::Shipped)->overdue()->create();

        $this->actingAs(User::factory()->sales()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Late orders', $late->number, 'Late Customer GmbH', '4d late'])
            ->assertSeeInOrder(['Ready to ship', $ready->number, 'Ready Customer Srl', '€ 1,234.50']);
    }

    public function test_it_shows_material_shortages_of_released_production(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 4, rawStock: 20);
        $short = $productionOrder->materials()->where('required_qty', 32)->sole()->material;

        $this->actingAs(User::factory()->planner()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Short for released production', $short->name, '−12']);
    }

    public function test_work_center_load_counts_remaining_planned_work(): void
    {
        // 4 units over three steps planned at 10, 20 and 30 minutes per unit.
        $this->releaseProductionOrder(quantity: 4);

        $load = app(DashboardSummary::class)->workCenterLoad()->keyBy(fn (array $row) => $row['work_center']->name);

        $this->assertSame(40.0, $load['Cutting']['remaining_minutes']);
        $this->assertSame(120.0, $load['Finishing']['remaining_minutes']);
        $this->assertSame(1, $load['Assembly']['open_steps']);
    }

    public function test_scrap_rate_covers_recently_completed_orders(): void
    {
        ProductionOrder::factory()->create(['status' => ProductionOrderStatus::Completed, 'completed_at' => now()->subDays(3), 'good_qty' => 18, 'scrap_qty' => 2]);
        ProductionOrder::factory()->create(['status' => ProductionOrderStatus::Completed, 'completed_at' => now()->subDays(60), 'good_qty' => 0, 'scrap_qty' => 10]);

        $this->assertSame(10.0, app(DashboardSummary::class)->scrapRate());
    }

    public function test_low_stock_materials_are_listed_most_critical_first(): void
    {
        Material::factory()->stocked(8, minimum: 10)->create(['name' => 'Slightly low glue']);
        Material::factory()->stocked(1, minimum: 10)->create(['name' => 'Almost out of oil']);

        $this->actingAs(User::factory()->planner()->create())
            ->get(route('dashboard'))
            ->assertSeeInOrder(['Below minimum stock', 'Almost out of oil', 'Slightly low glue']);
    }

    public function test_operators_use_the_shop_floor_instead(): void
    {
        $this->actingAs(User::factory()->operator()->create())->get(route('dashboard'))->assertForbidden();
    }
}
