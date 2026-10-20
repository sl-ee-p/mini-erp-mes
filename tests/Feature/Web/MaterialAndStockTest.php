<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\MaterialType;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Domain\Sales\Actions\ConfirmSalesOrder;
use App\Models\BomItem;
use App\Models\Material;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialAndStockTest extends TestCase
{
    use RefreshDatabase;

    private User $planner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planner = User::factory()->planner()->create();
    }

    public function test_low_stock_filter_lists_only_materials_below_minimum(): void
    {
        Material::factory()->stocked(2, minimum: 10)->create(['name' => 'Walnut board low']);
        Material::factory()->stocked(50, minimum: 10)->create(['name' => 'Oak board plenty']);

        $this->actingAs($this->planner)
            ->get(route('materials.index', ['low_stock' => 1]))
            ->assertOk()
            ->assertSee('Walnut board low')
            ->assertDontSee('Oak board plenty');
    }

    public function test_planner_creates_raw_material(): void
    {
        $this->actingAs($this->planner)
            ->post(route('materials.store'), ['sku' => 'rm-oak-20', 'name' => 'Oak board 20mm', 'unit' => 'm2', 'min_stock' => 15, 'unit_cost' => 42.5])
            ->assertRedirect();

        $material = Material::where('sku', 'RM-OAK-20')->firstOrFail();
        $this->assertSame(MaterialType::Raw, $material->type);
        $this->assertSame(UnitOfMeasure::SquareMeter, $material->unit);
    }

    public function test_finished_good_identity_cannot_be_changed_from_material_screen(): void
    {
        $product = Product::factory()->create(['sku' => 'FG-KEEP']);

        $this->actingAs($this->planner)
            ->put(route('materials.update', $product->material), ['sku' => 'FG-HACK', 'name' => 'Hacked', 'unit' => 'kg', 'min_stock' => 3, 'unit_cost' => 100])
            ->assertRedirect();

        $material = $product->material->fresh();
        $this->assertSame('FG-KEEP', $material?->sku);
        $this->assertSame(UnitOfMeasure::Piece, $material?->unit);
        $this->assertSame(3.0, $material?->min_stock);
    }

    public function test_unit_is_fixed_once_stock_is_booked_or_the_material_is_in_a_bill_of_materials(): void
    {
        $oak = Material::factory()->unit(UnitOfMeasure::SquareMeter)->create(['sku' => 'RM-OAK-20']);
        $fields = ['sku' => 'RM-OAK-20', 'name' => 'Oak panel', 'min_stock' => 0, 'unit_cost' => 48];

        // Nothing booked yet: a wrong unit can still be corrected.
        $this->actingAs($this->planner)->put(route('materials.update', $oak), [...$fields, 'unit' => 'm'])->assertSessionHasNoErrors();
        $this->assertSame(UnitOfMeasure::Meter, $oak->fresh()?->unit);

        app(RecordStockMovement::class)->execute($oak, StockMovementType::In, 64, 'Opening balance');

        $this->actingAs($this->planner)->put(route('materials.update', $oak), [...$fields, 'unit' => 'pcs'])->assertSessionHasErrors('unit');
        $this->assertSame(UnitOfMeasure::Meter, $oak->fresh()?->unit);
        $this->actingAs($this->planner)->get(route('materials.edit', $oak))->assertOk()->assertSee('Fixed: stock has been booked');

        $glue = BomItem::factory()->create(['material_id' => Material::factory()->unit(UnitOfMeasure::Kilogram)->create(['sku' => 'RM-GLUE'])])->material;

        $this->actingAs($this->planner)
            ->put(route('materials.update', $glue), ['sku' => 'RM-GLUE', 'name' => 'Glue', 'min_stock' => 0, 'unit_cost' => 6, 'unit' => 'l'])
            ->assertSessionHasErrors('unit');
    }

    public function test_material_page_shows_movement_history(): void
    {
        $material = Material::factory()->stocked(10)->create();

        $this->actingAs($this->planner)
            ->post(route('materials.stock-movements.store', $material), ['type' => 'in', 'quantity' => 5, 'reason' => 'Delivery DN-7781']);

        $this->actingAs($this->planner)
            ->get(route('materials.show', $material))
            ->assertOk()
            ->assertSee('Delivery DN-7781')
            ->assertSee($this->planner->name);

        $this->assertSame(15.0, $material->fresh()?->stock_qty);
    }

    public function test_issuing_more_than_available_shows_validation_error(): void
    {
        $material = Material::factory()->stocked(2)->create();

        $this->actingAs($this->planner)
            ->from(route('materials.show', $material))
            ->post(route('materials.stock-movements.store', $material), ['type' => 'out', 'quantity' => 5, 'reason' => 'Scrapped'])
            ->assertRedirect(route('materials.show', $material))
            ->assertSessionHasErrors('quantity');

        $this->assertSame(2.0, $material->fresh()?->stock_qty);
    }

    public function test_physical_count_books_the_difference(): void
    {
        $material = Material::factory()->stocked(10)->create();

        $this->actingAs($this->planner)
            ->post(route('materials.stock-movements.store', $material), ['type' => 'adjustment', 'quantity' => 7, 'reason' => 'Cycle count']);

        $this->assertDatabaseHas('stock_movements', ['material_id' => $material->id, 'type' => 'adjustment', 'quantity' => -3]);
    }

    public function test_pieces_cannot_be_booked_in_fractions(): void
    {
        $material = Material::factory()->unit(UnitOfMeasure::Piece)->create();

        $this->actingAs($this->planner)
            ->post(route('materials.stock-movements.store', $material), ['type' => 'in', 'quantity' => 1.5])
            ->assertSessionHasErrors('quantity');
    }

    public function test_out_movements_require_a_reason(): void
    {
        $material = Material::factory()->stocked(10)->create();

        $this->actingAs($this->planner)
            ->post(route('materials.stock-movements.store', $material), ['type' => 'out', 'quantity' => 1])
            ->assertSessionHasErrors('reason');
    }

    public function test_sales_cannot_book_stock(): void
    {
        $material = Material::factory()->stocked(10)->create();

        $this->actingAs(User::factory()->sales()->create())
            ->post(route('materials.stock-movements.store', $material), ['type' => 'in', 'quantity' => 1])
            ->assertForbidden();
    }

    public function test_stock_ledger_can_be_filtered_by_type(): void
    {
        $material = Material::factory()->stocked(10)->create();
        $this->actingAs($this->planner)->post(route('materials.stock-movements.store', $material), ['type' => 'in', 'quantity' => 1, 'reason' => 'Receipt R-1']);
        $this->actingAs($this->planner)->post(route('materials.stock-movements.store', $material), ['type' => 'out', 'quantity' => 1, 'reason' => 'Issue I-1']);

        $this->actingAs($this->planner)
            ->get(route('stock-movements.index', ['type' => 'out']))
            ->assertOk()
            ->assertSee('Issue I-1')
            ->assertDontSee('Receipt R-1');
    }

    public function test_stock_ledger_can_be_filtered_by_date_and_ignores_malformed_dates(): void
    {
        $material = Material::factory()->stocked(10)->create();
        $this->travelTo('2026-03-10 09:00');
        $this->actingAs($this->planner)->post(route('materials.stock-movements.store', $material), ['type' => 'out', 'quantity' => 1, 'reason' => 'March issue']);
        $this->travelTo('2026-04-10 09:00');
        $this->actingAs($this->planner)->post(route('materials.stock-movements.store', $material), ['type' => 'out', 'quantity' => 1, 'reason' => 'April issue']);

        $this->actingAs($this->planner)
            ->get(route('stock-movements.index', ['from' => '2026-04-01', 'to' => '2026-04-30']))
            ->assertOk()
            ->assertSee('April issue')
            ->assertDontSee('March issue');

        $this->actingAs($this->planner)
            ->get(route('stock-movements.index', ['from' => 'not-a-date', 'to' => '2026-02-31']))
            ->assertOk()
            ->assertSee('April issue')
            ->assertSee('March issue');
    }

    public function test_list_shows_allocation_and_filters_shortages(): void
    {
        $product = Product::factory()->withBomAndRouting(rawStock: 20)->create();
        $salesOrder = SalesOrder::factory()->withLines([$product], 4)->create();
        app(ConfirmSalesOrder::class)->execute($salesOrder);
        $short = $product->bomItems()->where('quantity', 8)->sole()->material;
        $covered = $product->bomItems()->where('quantity', 2.5)->sole()->material;

        $this->actingAs($this->planner)
            ->get(route('materials.index', ['shortage' => 1]))
            ->assertOk()
            ->assertSeeInOrder([$short->name, '20 pcs', '32', '-12', 'Short'])
            ->assertDontSee($covered->name);

        $this->actingAs($this->planner)
            ->get(route('materials.show', $short))
            ->assertOk()
            ->assertSee('Released production needs 12 pcs more than is on hand.')
            ->assertSee($salesOrder->productionOrders()->sole()->number);
    }
}
