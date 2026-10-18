<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Inventory;

use App\Domain\Inventory\Actions\AdjustStockToCount;
use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AdjustStockToCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_count_below_system_balance_books_negative_adjustment(): void
    {
        $material = Material::factory()->stocked(20)->create();

        $movement = app(AdjustStockToCount::class)->execute($material, 17.5, 'Quarterly count');

        $this->assertNotNull($movement);
        $this->assertSame(StockMovementType::Adjustment, $movement->type);
        $this->assertSame(-2.5, $movement->quantity);
        $this->assertSame(17.5, $material->fresh()->stock_qty);
    }

    public function test_count_above_system_balance_books_positive_adjustment(): void
    {
        $material = Material::factory()->stocked(4)->create();

        $movement = app(AdjustStockToCount::class)->execute($material, 6, 'Found on shelf B');

        $this->assertSame(2.0, $movement?->quantity);
    }

    public function test_count_is_reconciled_against_the_current_balance_not_a_stale_copy(): void
    {
        $material = Material::factory()->stocked(20)->create();
        // Someone issues 5 units after the count form was loaded with a balance of 20.
        app(RecordStockMovement::class)->execute(Material::findOrFail($material->id), StockMovementType::Out, 5, 'Issued');

        $movement = app(AdjustStockToCount::class)->execute($material, 17, 'Count');

        $this->assertSame(2.0, $movement?->quantity);
        $this->assertSame(17.0, $material->fresh()?->stock_qty);
        $this->assertSame(17.0, $material->stock_qty);
    }

    public function test_matching_count_records_nothing(): void
    {
        $material = Material::factory()->stocked(8)->create();

        $this->assertNull(app(AdjustStockToCount::class)->execute($material, 8, 'Count'));
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_negative_count_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(AdjustStockToCount::class)->execute(Material::factory()->create(), -1, 'Count');
    }
}
