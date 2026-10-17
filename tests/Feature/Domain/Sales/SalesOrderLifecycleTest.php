<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Sales;

use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Exceptions\InsufficientStock;
use App\Domain\Sales\Actions\CancelSalesOrder;
use App\Domain\Sales\Actions\ConfirmSalesOrder;
use App\Domain\Sales\Actions\CreateSalesOrder;
use App\Domain\Sales\Actions\ShipSalesOrder;
use App\Domain\Sales\Actions\UpdateSalesOrder;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Domain\Sales\Events\SalesOrderConfirmed;
use App\Domain\Sales\Exceptions\SalesOrderException;
use App\Domain\Shared\InvalidStateTransition;
use App\Jobs\GenerateSalesOrderPdf;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SalesOrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_order_is_a_numbered_draft_with_computed_total(): void
    {
        $table = Product::factory()->create(['price' => 1490]);
        $chair = Product::factory()->create(['price' => 260]);
        $user = User::factory()->sales()->create();

        $order = app(CreateSalesOrder::class)->execute([
            'customer_id' => Customer::factory()->create()->id,
            'due_date' => today()->addWeeks(3)->toDateString(),
            'lines' => [
                ['product_id' => $table->id, 'quantity' => 1],
                ['product_id' => $chair->id, 'quantity' => 6, 'unit_price' => 240.0],
            ],
        ], $user);

        $this->assertSame(SalesOrderStatus::Draft, $order->status);
        $this->assertMatchesRegularExpression('/^SO-\d{4}-0001$/', $order->number);
        $this->assertSame(1490.0 + 6 * 240.0, $order->total);
        $this->assertSame($user->id, $order->created_by);
        $this->assertSame([1490.0, 240.0], $order->lines()->orderBy('id')->pluck('unit_price')->map(fn ($price) => (float) $price)->all());
    }

    public function test_draft_can_be_updated_but_confirmed_order_cannot(): void
    {
        $order = SalesOrder::factory()->withLines()->create();
        $product = Product::factory()->create(['price' => 100]);
        $data = ['customer_id' => $order->customer_id, 'due_date' => today()->addWeek()->toDateString(), 'lines' => [['product_id' => $product->id, 'quantity' => 3]]];

        app(UpdateSalesOrder::class)->execute($order, $data);
        $this->assertSame(300.0, $order->fresh()?->total);

        $order->update(['status' => SalesOrderStatus::Confirmed]);
        $this->expectException(SalesOrderException::class);
        app(UpdateSalesOrder::class)->execute($order, $data);
    }

    public function test_confirming_moves_draft_to_confirmed_and_announces_it(): void
    {
        Event::fake([SalesOrderConfirmed::class]);
        $order = SalesOrder::factory()->withLines()->create();

        $confirmed = app(ConfirmSalesOrder::class)->execute($order);

        $this->assertSame(SalesOrderStatus::Confirmed, $confirmed->status);
        $this->assertNotNull($confirmed->confirmed_at);
        Event::assertDispatched(SalesOrderConfirmed::class, fn ($event) => $event->salesOrder->is($order));
    }

    public function test_confirmation_queues_the_order_pdf(): void
    {
        Queue::fake();

        app(ConfirmSalesOrder::class)->execute(SalesOrder::factory()->withLines()->create());

        Queue::assertPushed(GenerateSalesOrderPdf::class);
    }

    public function test_empty_order_cannot_be_confirmed(): void
    {
        $this->expectException(SalesOrderException::class);
        $this->expectExceptionMessage('has no lines');

        app(ConfirmSalesOrder::class)->execute(SalesOrder::factory()->create());
    }

    public function test_order_with_discontinued_product_cannot_be_confirmed(): void
    {
        $order = SalesOrder::factory()->withLines([Product::factory()->inactive()->create(['name' => 'Old stool'])])->create();

        $this->expectExceptionMessage('discontinued products: Old stool');

        app(ConfirmSalesOrder::class)->execute($order);
    }

    public function test_order_cannot_be_confirmed_twice(): void
    {
        $order = SalesOrder::factory()->withLines()->create();
        app(ConfirmSalesOrder::class)->execute($order);

        $this->expectException(InvalidStateTransition::class);
        app(ConfirmSalesOrder::class)->execute($order);
    }

    public function test_draft_can_be_cancelled_but_shipped_order_cannot(): void
    {
        $draft = SalesOrder::factory()->create();
        $this->assertSame(SalesOrderStatus::Cancelled, app(CancelSalesOrder::class)->execute($draft)->status);

        $this->expectException(InvalidStateTransition::class);
        app(CancelSalesOrder::class)->execute(SalesOrder::factory()->status(SalesOrderStatus::Shipped)->create());
    }

    public function test_shipping_issues_finished_goods_and_closes_the_order(): void
    {
        $product = Product::factory()->create();
        app(RecordStockMovement::class)->execute($product->material, StockMovementType::In, 5, 'Produced');
        $order = SalesOrder::factory()->status(SalesOrderStatus::Completed)->withLines([$product], quantity: 3)->create();

        $shipped = app(ShipSalesOrder::class)->execute($order, User::factory()->sales()->create());

        $this->assertSame(SalesOrderStatus::Shipped, $shipped->status);
        $this->assertNotNull($shipped->shipped_at);
        $this->assertSame(2.0, $product->material->fresh()?->stock_qty);
        $this->assertDatabaseHas('stock_movements', ['reference_type' => 'sales_order', 'reference_id' => $order->id, 'quantity' => -3]);
    }

    public function test_shipping_without_enough_finished_goods_changes_nothing(): void
    {
        [$inStock, $missing] = Product::factory()->count(2)->create();
        app(RecordStockMovement::class)->execute($inStock->material, StockMovementType::In, 5, 'Produced');
        $order = SalesOrder::factory()->status(SalesOrderStatus::Completed)->withLines([$inStock, $missing], quantity: 2)->create();

        try {
            app(ShipSalesOrder::class)->execute($order);
            $this->fail('Expected InsufficientStock');
        } catch (InsufficientStock) {
        }

        $this->assertSame(SalesOrderStatus::Completed, $order->fresh()?->status);
        $this->assertSame(5.0, $inStock->material->fresh()?->stock_qty);
    }

    public function test_only_completed_orders_can_ship(): void
    {
        $this->expectException(InvalidStateTransition::class);

        app(ShipSalesOrder::class)->execute(SalesOrder::factory()->status(SalesOrderStatus::Confirmed)->withLines()->create());
    }

    public function test_late_scope_only_includes_open_orders_past_due(): void
    {
        $late = SalesOrder::factory()->status(SalesOrderStatus::Confirmed)->overdue()->create();
        SalesOrder::factory()->status(SalesOrderStatus::Shipped)->overdue()->create();
        SalesOrder::factory()->overdue()->create(); // draft: not a commitment yet
        SalesOrder::factory()->status(SalesOrderStatus::Confirmed)->create(); // not due yet

        $this->assertSame([$late->id], SalesOrder::late()->pluck('id')->all());
        $this->assertTrue($late->isLate());
    }
}
