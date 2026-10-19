<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\Material;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_materials_export_follows_the_list_filters(): void
    {
        Material::factory()->stocked(2, minimum: 10)->create(['sku' => 'RM-LOW', 'name' => 'Low glue', 'unit_cost' => 5]);
        Material::factory()->stocked(50, minimum: 10)->create(['sku' => 'RM-OK', 'name' => 'Plenty of screws']);

        $response = $this->actingAs(User::factory()->sales()->create())->get(route('materials.export', ['low_stock' => 1]));

        $response->assertOk()->assertDownload('materials-'.now()->format('Y-m-d').'.csv');
        $rows = $this->rows($response);

        $this->assertSame(['SKU', 'Name', 'Type', 'Unit', 'On hand', 'Allocated', 'Available', 'Minimum', 'Unit cost (EUR)', 'Stock value (EUR)'], $rows[0]);
        $this->assertCount(2, $rows);
        $this->assertSame(['RM-LOW', 'Low glue'], array_slice($rows[1], 0, 2));
        $this->assertSame('10', $rows[1][9]);
    }

    public function test_stock_ledger_export_lists_movements_oldest_first(): void
    {
        $planner = User::factory()->planner()->create();
        $material = Material::factory()->create(['sku' => 'RM-OIL']);
        app(RecordStockMovement::class)->execute($material, StockMovementType::In, 20, 'Delivery DN-1', user: $planner);
        app(RecordStockMovement::class)->execute($material, StockMovementType::Out, 5, '=HYPERLINK("http://example.com")', user: $planner);

        $rows = $this->rows($this->actingAs($planner)->get(route('stock-movements.export', ['search' => 'RM-OIL']))->assertOk());

        $this->assertCount(3, $rows);
        $this->assertSame(['RM-OIL', 'Stock in', '20', 'Delivery DN-1'], [$rows[1][1], $rows[1][3], $rows[1][4], $rows[1][7]]);
        $this->assertSame(['Stock out', '-5', '15'], [$rows[2][3], $rows[2][4], $rows[2][6]]);
        // Text that a spreadsheet would execute as a formula is neutralised.
        $this->assertSame('\'=HYPERLINK("http://example.com")', $rows[2][7]);
        $this->assertSame($planner->name, $rows[2][9]);
    }

    public function test_guests_cannot_export(): void
    {
        $this->get(route('stock-movements.export'))->assertRedirect(route('login'));
    }

    /**
     * @return list<list<string>>
     */
    private function rows(TestResponse $response): array
    {
        $content = (string) $response->streamedContent();
        $this->assertStringStartsWith("\u{FEFF}", $content);

        $lines = array_filter(explode("\n", substr($content, 3)), fn (string $line) => $line !== '');

        return array_values(array_map(fn (string $line) => str_getcsv($line, escape: ''), $lines));
    }
}
