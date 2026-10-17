<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Inventory;

use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Events\StockBelowMinimum;
use App\Domain\Inventory\Exceptions\InsufficientStock;
use App\Domain\Inventory\Notifications\LowStockNotification;
use App\Models\Customer;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

class RecordStockMovementTest extends TestCase
{
    use RefreshDatabase;

    private RecordStockMovement $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = app(RecordStockMovement::class);
    }

    public function test_goods_receipt_increases_balance_and_writes_ledger_row(): void
    {
        $material = Material::factory()->stocked(10)->create();
        $user = User::factory()->planner()->create();

        $movement = $this->action->execute($material, StockMovementType::In, 2.5, 'Delivery DN-1', user: $user);

        $this->assertSame(12.5, $material->stock_qty);
        $this->assertSame(12.5, $material->fresh()->stock_qty);
        $this->assertSame(2.5, $movement->quantity);
        $this->assertSame(12.5, $movement->balance_after);
        $this->assertSame($user->id, $movement->user_id);
    }

    public function test_issue_decreases_balance_with_negative_ledger_quantity(): void
    {
        $material = Material::factory()->stocked(10)->create();

        $movement = $this->action->execute($material, StockMovementType::Out, 4);

        $this->assertSame(-4.0, $movement->quantity);
        $this->assertSame(6.0, $material->fresh()->stock_qty);
    }

    public function test_issue_beyond_available_stock_is_rejected_without_side_effects(): void
    {
        $material = Material::factory()->stocked(3)->create();

        try {
            $this->action->execute($material, StockMovementType::Out, 3.001);
            $this->fail('Expected InsufficientStock');
        } catch (InsufficientStock $exception) {
            $this->assertStringContainsString('requested 3.001', $exception->getMessage());
        }

        $this->assertSame(3.0, $material->fresh()->stock_qty);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_balance_can_reach_exactly_zero(): void
    {
        $material = Material::factory()->stocked(0.3)->create();

        $this->action->execute($material, StockMovementType::Out, 0.1);
        $this->action->execute($material, StockMovementType::Out, 0.2);

        $this->assertSame(0.0, $material->fresh()->stock_qty);
    }

    public function test_zero_quantity_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->action->execute(Material::factory()->create(), StockMovementType::In, 0);
    }

    public function test_reference_document_is_stored_polymorphically(): void
    {
        $material = Material::factory()->stocked(5)->create();
        $customer = Customer::factory()->create();

        $movement = $this->action->execute($material, StockMovementType::Out, 1, reference: $customer);

        $this->assertSame('customer', $movement->reference_type);
        $this->assertTrue($movement->reference->is($customer));
    }

    public function test_low_stock_event_fires_only_when_crossing_the_minimum(): void
    {
        Event::fake([StockBelowMinimum::class]);
        $material = Material::factory()->stocked(12, minimum: 10)->create();

        $this->action->execute($material, StockMovementType::Out, 1);   // 11: still fine
        Event::assertNotDispatched(StockBelowMinimum::class);

        $this->action->execute($material, StockMovementType::Out, 3);   // 8: crosses
        $this->action->execute($material, StockMovementType::Out, 1);   // 7: already low

        Event::assertDispatchedTimes(StockBelowMinimum::class, 1);
    }

    public function test_planners_and_admins_are_notified_of_low_stock(): void
    {
        Notification::fake();
        $planner = User::factory()->planner()->create();
        $admin = User::factory()->admin()->create();
        $sales = User::factory()->sales()->create();
        User::factory()->planner()->inactive()->create();
        $material = Material::factory()->stocked(10, minimum: 5)->create();

        $this->action->execute($material, StockMovementType::Out, 6);

        Notification::assertSentTo([$planner, $admin], LowStockNotification::class);
        Notification::assertNotSentTo($sales, LowStockNotification::class);
        Notification::assertCount(2);
    }
}
