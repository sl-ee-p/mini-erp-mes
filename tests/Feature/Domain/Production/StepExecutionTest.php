<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Production;

use App\Domain\Inventory\Exceptions\InsufficientStock;
use App\Domain\Production\Actions\CompleteStep;
use App\Domain\Production\Actions\PauseStep;
use App\Domain\Production\Actions\StartStep;
use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Enums\StepStatus;
use App\Domain\Production\Exceptions\ProductionException;
use App\Domain\Sales\Actions\CancelSalesOrder;
use App\Domain\Sales\Actions\ConfirmSalesOrder;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Domain\Shared\InvalidStateTransition;
use App\Models\Product;
use App\Models\ProductionOrderStep;
use App\Models\SalesOrder;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StepExecutionTest extends TestCase
{
    use ProductionTestHelpers, RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->operator()->create();
    }

    public function test_starting_the_first_step_moves_orders_into_production(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 4);

        $step = app(StartStep::class)->execute($productionOrder->steps[0], $this->operator);

        $this->assertSame(StepStatus::Running, $step->status);
        $this->assertSame(4, $step->input_qty);
        $this->assertSame($this->operator->id, $step->operator_id);
        $this->assertSame(ProductionOrderStatus::InProgress, $productionOrder->fresh()?->status);
        $this->assertSame(SalesOrderStatus::InProduction, $productionOrder->salesOrder->fresh()?->status);
        $this->assertDatabaseHas('step_time_logs', ['production_order_step_id' => $step->id, 'user_id' => $this->operator->id, 'ended_at' => null]);
    }

    public function test_steps_must_follow_the_routing_sequence(): void
    {
        $productionOrder = $this->releaseProductionOrder();

        $this->expectException(ProductionException::class);
        $this->expectExceptionMessage('cannot start before');

        app(StartStep::class)->execute($productionOrder->steps[1], $this->operator);
    }

    public function test_an_operator_works_on_one_step_at_a_time(): void
    {
        [$first, $second] = [$this->releaseProductionOrder(), $this->releaseProductionOrder()];
        app(StartStep::class)->execute($first->steps[0], $this->operator);

        $this->expectExceptionMessage('You are already working on');

        app(StartStep::class)->execute($second->steps[0], $this->operator);
    }

    public function test_pausing_and_resuming_accumulates_worked_time(): void
    {
        $step = $this->releaseProductionOrder()->steps[0];

        $this->freezeTime();
        app(StartStep::class)->execute($step, $this->operator);
        $this->travel(15)->minutes();
        app(PauseStep::class)->execute($step, $this->operator);
        $this->travel(30)->minutes(); // break: not counted
        app(StartStep::class)->execute($step, $this->operator);
        $this->travel(10)->minutes();
        $completed = app(CompleteStep::class)->execute($step, $this->operator);

        $this->assertSame(25 * 60, $completed->actual_seconds);
        $this->assertSame(2, $completed->timeLogs()->count());
        $this->assertSame(0, $completed->timeLogs()->whereNull('ended_at')->count());
    }

    public function test_paused_step_cannot_be_completed(): void
    {
        $step = $this->releaseProductionOrder()->steps[0];
        app(StartStep::class)->execute($step, $this->operator);
        app(PauseStep::class)->execute($step, $this->operator);

        $this->expectException(InvalidStateTransition::class);

        app(CompleteStep::class)->execute($step, $this->operator);
    }

    public function test_only_the_operator_running_a_step_can_pause_or_complete_it(): void
    {
        $step = $this->releaseProductionOrder()->steps[0];
        $colleague = User::factory()->operator()->create();
        app(StartStep::class)->execute($step, $this->operator);

        foreach ([
            'pause' => fn () => app(PauseStep::class)->execute($step, $colleague),
            'complete' => fn () => app(CompleteStep::class)->execute($step, $colleague),
        ] as $action => $attempt) {
            try {
                $attempt();
                $this->fail("A colleague should not be able to {$action} a step someone else is running.");
            } catch (ProductionException $exception) {
                $this->assertStringContainsString("being worked on by {$this->operator->name}", $exception->getMessage());
            }
        }

        $step->refresh();
        $this->assertSame(StepStatus::Running, $step->status);
        $this->assertSame($this->operator->id, $step->openTimeLog()->value('user_id'));
    }

    public function test_a_paused_step_can_be_handed_over_to_another_operator(): void
    {
        $step = $this->releaseProductionOrder()->steps[0];
        $colleague = User::factory()->operator()->create();
        app(StartStep::class)->execute($step, $this->operator);
        app(PauseStep::class)->execute($step, $this->operator);

        app(StartStep::class)->execute($step, $colleague);
        $completed = app(CompleteStep::class)->execute($step, $colleague);

        $this->assertSame(StepStatus::Completed, $completed->status);
        $this->assertSame($colleague->id, $completed->operator_id);
    }

    public function test_a_planner_can_stop_a_step_left_running_by_an_operator(): void
    {
        $step = $this->releaseProductionOrder()->steps[0];
        app(StartStep::class)->execute($step, $this->operator);

        $paused = app(PauseStep::class)->execute($step, User::factory()->planner()->create());

        $this->assertSame(StepStatus::Paused, $paused->status);
        $this->assertSame(0, $paused->timeLogs()->whereNull('ended_at')->count());
    }

    public function test_completing_records_scrap_and_feeds_good_units_to_the_next_step(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 5);
        [$first, $second] = $productionOrder->steps;

        app(StartStep::class)->execute($first, $this->operator);
        $completed = app(CompleteStep::class)->execute($first, $this->operator, scrapQty: 1);
        $started = app(StartStep::class)->execute($second, $this->operator);

        $this->assertSame(4, $completed->good_qty);
        $this->assertSame(1, $completed->scrap_qty);
        $this->assertSame(4, $started->input_qty);
        $this->assertSame(1, $productionOrder->fresh()?->scrap_qty);
    }

    public function test_scrap_cannot_exceed_units_processed(): void
    {
        $step = $this->releaseProductionOrder(quantity: 2)->steps[0];
        app(StartStep::class)->execute($step, $this->operator);

        $this->expectExceptionMessage('cannot exceed the 2 units');

        app(CompleteStep::class)->execute($step, $this->operator, scrapQty: 3);
    }

    public function test_consuming_step_backflushes_materials_including_scrapped_units(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 4, rawStock: 100);
        $step = $productionOrder->steps[0];

        app(StartStep::class)->execute($step, $this->operator);
        app(CompleteStep::class)->execute($step, $this->operator, scrapQty: 1);

        $materials = $productionOrder->materials()->with('material')->orderBy('id')->get();
        $this->assertSame([10.0, 32.0], $materials->pluck('consumed_qty')->all());          // 4 units × (2.5, 8)
        $this->assertSame([90.0, 68.0], $materials->pluck('material.stock_qty')->all());
        $this->assertDatabaseHas('stock_movements', ['reference_type' => 'production_order_step', 'reference_id' => $step->id, 'quantity' => -10]);
    }

    public function test_non_consuming_steps_do_not_touch_stock(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 1);
        [$first, $second] = $productionOrder->steps;
        $this->runStep($first);
        $movementsAfterFirst = StockMovement::count();

        $this->runStep($second);

        $this->assertSame($movementsAfterFirst, StockMovement::count());
    }

    public function test_insufficient_material_rolls_back_the_step_completion(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 4, rawStock: 5); // needs 10 and 32
        $step = $productionOrder->steps[0];
        app(StartStep::class)->execute($step, $this->operator);

        try {
            app(CompleteStep::class)->execute($step, $this->operator);
            $this->fail('Expected InsufficientStock');
        } catch (InsufficientStock) {
        }

        $step->refresh();
        $this->assertSame(StepStatus::Running, $step->status);
        $this->assertNull($step->good_qty);
        $this->assertSame(1, $step->timeLogs()->whereNull('ended_at')->count());
        $this->assertSame([5.0, 5.0], $productionOrder->materials()->with('material')->get()->pluck('material.stock_qty')->all());
    }

    public function test_last_step_books_finished_goods_and_completes_the_orders(): void
    {
        $productionOrder = $this->releaseProductionOrder(quantity: 3);

        $this->runStep($productionOrder->steps[0], scrap: 0);
        $this->runStep($productionOrder->steps[1], scrap: 1);
        $this->runStep($productionOrder->steps[2], scrap: 0);

        $productionOrder->refresh();
        $this->assertSame(ProductionOrderStatus::Completed, $productionOrder->status);
        $this->assertSame(2, $productionOrder->good_qty);
        $this->assertSame(1, $productionOrder->scrap_qty);
        $this->assertSame(2.0, $productionOrder->product->material->fresh()?->stock_qty);
        $this->assertSame(SalesOrderStatus::Completed, $productionOrder->salesOrder->fresh()?->status);
    }

    public function test_sales_order_stays_in_production_until_every_production_order_is_done(): void
    {
        $products = Product::factory()->count(2)->withBomAndRouting()->create();
        $salesOrder = SalesOrder::factory()->withLines($products->all(), quantity: 1)->create();
        app(ConfirmSalesOrder::class)->execute($salesOrder);
        [$first, $second] = $salesOrder->productionOrders()->with('steps')->get();

        $first->steps->each(fn ($step) => $this->runStep($step));
        $this->assertSame(SalesOrderStatus::InProduction, $salesOrder->fresh()?->status);

        $second->steps->each(fn ($step) => $this->runStep($step));
        $this->assertSame(SalesOrderStatus::Completed, $salesOrder->fresh()?->status);
    }

    public function test_cancelling_a_sales_order_cancels_planned_production(): void
    {
        $productionOrder = $this->releaseProductionOrder();

        app(CancelSalesOrder::class)->execute($productionOrder->salesOrder);

        $this->assertSame(ProductionOrderStatus::Cancelled, $productionOrder->fresh()?->status);
    }

    public function test_steps_of_a_cancelled_production_order_cannot_be_started(): void
    {
        $productionOrder = $this->releaseProductionOrder();
        app(CancelSalesOrder::class)->execute($productionOrder->salesOrder);

        try {
            app(StartStep::class)->execute($productionOrder->steps[0], $this->operator);
            $this->fail('Starting a step of a cancelled production order should be refused.');
        } catch (ProductionException $exception) {
            $this->assertStringContainsString('is cancelled', $exception->getMessage());
        }

        $this->assertSame(StepStatus::Pending, $productionOrder->steps[0]->fresh()?->status);
        $this->assertDatabaseCount('step_time_logs', 0);
    }

    public function test_sales_order_cannot_be_cancelled_once_production_started(): void
    {
        $productionOrder = $this->releaseProductionOrder();
        app(StartStep::class)->execute($productionOrder->steps[0], $this->operator);

        $this->expectException(InvalidStateTransition::class);

        app(CancelSalesOrder::class)->execute($productionOrder->salesOrder);
    }

    public function test_actionable_scope_lists_only_steps_whose_predecessors_are_done(): void
    {
        $productionOrder = $this->releaseProductionOrder();
        $this->assertSame([$productionOrder->steps[0]->id], ProductionOrderStep::actionable()->pluck('id')->all());

        $this->runStep($productionOrder->steps[0]);

        $this->assertSame([$productionOrder->steps[1]->id], ProductionOrderStep::actionable()->pluck('id')->all());
    }

    private function runStep(ProductionOrderStep $step, int $scrap = 0): void
    {
        app(StartStep::class)->execute($step, $this->operator);
        app(CompleteStep::class)->execute($step, $this->operator, $scrap);
    }
}
