<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Production;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Enums\StepStatus;
use App\Domain\Production\Exceptions\ProductionException;
use App\Domain\Sales\Actions\ConfirmSalesOrder;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Models\Product;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateProductionOrdersTest extends TestCase
{
    use ProductionTestHelpers, RefreshDatabase;

    public function test_confirming_releases_one_production_order_per_line(): void
    {
        $products = Product::factory()->count(2)->withBomAndRouting()->create();
        $salesOrder = SalesOrder::factory()->withLines($products->all(), quantity: 3)->create();

        app(ConfirmSalesOrder::class)->execute($salesOrder);

        $productionOrders = $salesOrder->productionOrders()->get();
        $this->assertCount(2, $productionOrders);
        $this->assertTrue($productionOrders->every(fn ($order) => $order->status === ProductionOrderStatus::Planned && $order->quantity === 3));
        $this->assertTrue($productionOrders->every(fn ($order) => $order->due_date->equalTo($salesOrder->due_date)));
        $this->assertMatchesRegularExpression('/^PO-\d{4}-0001$/', $productionOrders->first()->number);
    }

    public function test_steps_and_materials_are_snapshotted_with_quantities(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 4);

        // Routing: 10, 20, 30 min per unit; BOM: 2.5 and 8 per unit.
        $this->assertSame([10, 20, 30], $productionOrder->steps->pluck('sequence')->all());
        $this->assertSame([40.0, 80.0, 120.0], $productionOrder->steps->pluck('planned_minutes')->all());
        $this->assertSame(240.0, $productionOrder->planned_minutes);
        $this->assertTrue($productionOrder->steps->every(fn ($step) => $step->status === StepStatus::Pending));
        $this->assertSame([10.0, 32.0], $productionOrder->materials()->orderBy('id')->pluck('required_qty')->map(fn ($qty) => (float) $qty)->all());
    }

    public function test_routing_changes_do_not_affect_released_orders(): void
    {
        $productionOrder = $this->releaseProductionOrder();

        $productionOrder->product->routingSteps()->update(['name' => 'Renamed', 'planned_minutes_per_unit' => 999]);
        $productionOrder->product->bomItems()->update(['quantity' => 999]);

        $this->assertNotContains('Renamed', $productionOrder->steps()->pluck('name'));
        $this->assertNotContains(999.0, $productionOrder->materials()->pluck('quantity_per_unit')->map(fn ($qty) => (float) $qty));
    }

    public function test_product_without_routing_blocks_confirmation_entirely(): void
    {
        $salesOrder = SalesOrder::factory()->withLines([Product::factory()->create()])->create();

        try {
            app(ConfirmSalesOrder::class)->execute($salesOrder);
            $this->fail('Expected ProductionException');
        } catch (ProductionException $exception) {
            $this->assertStringContainsString('has no routing', $exception->getMessage());
        }

        $this->assertSame(SalesOrderStatus::Draft, $salesOrder->fresh()?->status);
        $this->assertDatabaseCount('production_orders', 0);
    }
}
