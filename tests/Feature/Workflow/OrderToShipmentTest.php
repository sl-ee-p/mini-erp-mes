<?php

declare(strict_types=1);

namespace Tests\Feature\Workflow;

use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Jobs\GenerateSalesOrderPdf;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * The whole business flow through the HTTP layer, as the three roles would do it on a normal day.
 */
class OrderToShipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_produced_from_stock_and_shipped(): void
    {
        Queue::fake([GenerateSalesOrderPdf::class]);
        $this->freezeTime();

        $planner = User::factory()->planner()->create();
        $sales = User::factory()->sales()->create();
        $operator = User::factory()->operator()->create();

        // Planner sets up a product: 1.5 m² oak + 12 screws per unit, cut (consumes BOM) then assemble.
        $oak = Material::factory()->unit(UnitOfMeasure::SquareMeter)->stocked(20, minimum: 5)->create(['name' => 'Oak panel']);
        $screws = Material::factory()->stocked(100)->create(['name' => 'Screw']);
        $cutting = WorkCenter::factory()->create(['name' => 'Cutting']);
        $assembly = WorkCenter::factory()->create(['name' => 'Assembly']);

        $this->actingAs($planner)->post(route('products.store'), [
            'sku' => 'FG-BENCH', 'name' => 'Oak bench', 'price' => 400, 'is_active' => 1,
            'bom' => [['material_id' => $oak->id, 'quantity' => 1.5], ['material_id' => $screws->id, 'quantity' => 12]],
            'routing' => [
                ['work_center_id' => $cutting->id, 'name' => 'Cut', 'planned_minutes_per_unit' => 10, 'consumes_materials' => 1],
                ['work_center_id' => $assembly->id, 'name' => 'Assemble', 'planned_minutes_per_unit' => 20, 'consumes_materials' => 0],
            ],
        ])->assertSessionHasNoErrors();
        $product = Product::where('sku', 'FG-BENCH')->sole();

        // Sales takes an order for 4 benches and confirms it.
        $this->actingAs($sales)->post(route('sales-orders.store'), [
            'customer_id' => Customer::factory()->create()->id,
            'due_date' => today()->addWeek()->toDateString(),
            'lines' => [['product_id' => $product->id, 'quantity' => 4]],
        ])->assertSessionHasNoErrors();
        $order = SalesOrder::sole();
        $this->actingAs($sales)->post(route('sales-orders.confirm', $order))->assertSessionHas('success');
        Queue::assertPushed(GenerateSalesOrderPdf::class);

        $productionOrder = $order->productionOrders()->with('steps')->sole();
        [$cut, $assemble] = $productionOrder->steps;
        $this->assertSame(120.0, $productionOrder->planned_minutes);

        // Operator sees the cutting step on the shop floor, runs it in 45 minutes, scraps one unit.
        $this->actingAs($operator)->get(route('shop-floor.index'))->assertOk()->assertSee($productionOrder->number)->assertDontSee('Assemble');
        $this->actingAs($operator)->post(route('production-steps.start', $cut))->assertSessionHas('success');
        $this->assertSame(SalesOrderStatus::InProduction, $order->fresh()?->status);
        $this->travel(45)->minutes();
        $this->actingAs($operator)->post(route('production-steps.complete', $cut), ['scrap_qty' => 1])->assertSessionHas('success');

        // Materials were backflushed for all 4 units that entered cutting.
        $this->assertSame(14.0, $oak->fresh()?->stock_qty);
        $this->assertSame(52.0, $screws->fresh()?->stock_qty);

        // Assembly of the 3 good units, with a pause.
        $this->actingAs($operator)->post(route('production-steps.start', $assemble));
        $this->travel(30)->minutes();
        $this->actingAs($operator)->post(route('production-steps.pause', $assemble));
        $this->travel(60)->minutes();
        $this->actingAs($operator)->post(route('production-steps.start', $assemble));
        $this->travel(20)->minutes();
        $this->actingAs($operator)->post(route('production-steps.complete', $assemble), ['scrap_qty' => 0])->assertSessionHas('success');

        $productionOrder->refresh();
        $this->assertSame(ProductionOrderStatus::Completed, $productionOrder->status);
        $this->assertSame(95.0, $productionOrder->actualMinutes());
        $this->assertSame([3, 1], [$productionOrder->good_qty, $productionOrder->scrap_qty]);
        $this->assertSame(3.0, $product->material->fresh()?->stock_qty);
        $this->assertSame(SalesOrderStatus::Completed, $order->fresh()?->status);

        // 4 benches ordered, 3 produced: shipping is refused until the shortfall is produced.
        $this->actingAs($sales)->post(route('sales-orders.ship', $order))->assertSessionHas('error');
        $this->assertSame(SalesOrderStatus::Completed, $order->fresh()?->status);

        // A bench from a previous run is found in the warehouse and counted in; the order ships.
        $this->actingAs($planner)->post(route('materials.stock-movements.store', $product->material), ['type' => 'adjustment', 'quantity' => 4, 'reason' => 'Found in showroom']);
        $this->actingAs($sales)->post(route('sales-orders.ship', $order))->assertSessionHas('success');

        $this->assertSame(SalesOrderStatus::Shipped, $order->fresh()?->status);
        $this->assertSame(0.0, $product->material->fresh()?->stock_qty);
        $this->assertDatabaseHas('audit_logs', ['auditable_type' => 'sales_order', 'auditable_id' => $order->id, 'user_id' => $sales->id, 'event' => 'updated']);
    }
}
