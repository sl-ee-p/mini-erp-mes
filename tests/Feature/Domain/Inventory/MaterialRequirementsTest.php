<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Inventory;

use App\Domain\Inventory\Queries\MaterialRequirements;
use App\Domain\Production\Actions\CompleteStep;
use App\Domain\Production\Actions\StartStep;
use App\Domain\Sales\Actions\CancelSalesOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Domain\Production\ProductionTestHelpers;
use Tests\TestCase;

class MaterialRequirementsTest extends TestCase
{
    use ProductionTestHelpers, RefreshDatabase;

    public function test_released_production_allocates_its_bill_of_materials(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 4);

        $allocated = app(MaterialRequirements::class)->allocated();

        $this->assertEqualsCanonicalizing([10.0, 32.0], $allocated->values()->all());
        $this->assertSame(
            $productionOrder->materials()->pluck('material_id')->sort()->values()->all(),
            $allocated->keys()->sort()->values()->all(),
        );
    }

    public function test_shortage_is_reported_when_requirements_exceed_stock(): void
    {
        // 4 units need 10 and 32; only 20 of each are on hand.
        $this->releaseProductionOrder(quantity: 4, rawStock: 20);

        $shortages = app(MaterialRequirements::class)->shortages();

        $this->assertCount(1, $shortages);
        $this->assertSame(32.0, $shortages->first()['allocated']);
        $this->assertSame(12.0, $shortages->first()['shortage']);
    }

    public function test_consumed_material_is_no_longer_allocated(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 2);
        $operator = User::factory()->operator()->create();
        $consumingStep = $productionOrder->steps->first();

        app(StartStep::class)->execute($consumingStep, $operator);
        app(CompleteStep::class)->execute($consumingStep, $operator);

        $this->assertTrue(app(MaterialRequirements::class)->allocated()->isEmpty());
    }

    public function test_allocation_is_released_when_every_unit_is_scrapped_before_the_consuming_step(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 3);
        $operator = User::factory()->operator()->create();
        [$first, $second] = [$productionOrder->steps[0], $productionOrder->steps[1]];
        $first->update(['consumes_materials' => false]);
        $second->update(['consumes_materials' => true]);

        $this->assertFalse(app(MaterialRequirements::class)->allocated()->isEmpty());

        app(StartStep::class)->execute($first, $operator);
        app(CompleteStep::class)->execute($first, $operator, scrapQty: 3);

        $this->assertTrue(app(MaterialRequirements::class)->allocated()->isEmpty());
        $this->assertSame(0, app(MaterialRequirements::class)->shortages()->count());
    }

    public function test_cancelled_orders_do_not_allocate_material(): void
    {
        $productionOrder = $this->releaseProductionOrder();

        app(CancelSalesOrder::class)->execute($productionOrder->salesOrder);

        $this->assertTrue(app(MaterialRequirements::class)->allocated()->isEmpty());
    }

    public function test_open_demand_lists_the_waiting_production_orders(): void
    {
        $product = Product::factory()->withBomAndRouting()->create();
        $first = $this->releaseProductionOrder(quantity: 1, product: $product);
        $second = $this->releaseProductionOrder(quantity: 3, product: $product);
        $material = $product->bomItems()->firstOrFail()->material;

        $demand = app(MaterialRequirements::class)->openDemand($material);

        $this->assertEqualsCanonicalizing([$first->id, $second->id], $demand->pluck('production_order_id')->all());
        $this->assertSame(10.0, app(MaterialRequirements::class)->allocated([$material->id])[$material->id]);
    }
}
