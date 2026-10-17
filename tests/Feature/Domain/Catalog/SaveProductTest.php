<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Catalog;

use App\Domain\Catalog\Actions\SaveProduct;
use App\Domain\Inventory\Enums\MaterialType;
use App\Models\Material;
use App\Models\WorkCenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_product_creates_its_finished_good_bom_and_numbered_routing(): void
    {
        $oak = Material::factory()->create(['unit_cost' => 85]);
        $screws = Material::factory()->create(['unit_cost' => 0.07]);
        [$cutting, $assembly] = WorkCenter::factory()->count(2)->create();

        $product = app(SaveProduct::class)->execute([
            'sku' => 'FG-TBL-01',
            'name' => 'Oak dining table',
            'price' => 1290.0,
            'bom' => [
                ['material_id' => $oak->id, 'quantity' => 2.4],
                ['material_id' => $screws->id, 'quantity' => 24],
            ],
            'routing' => [
                ['work_center_id' => $cutting->id, 'name' => 'Cut top', 'planned_minutes_per_unit' => 40],
                ['work_center_id' => $assembly->id, 'name' => 'Assemble', 'planned_minutes_per_unit' => 55, 'consumes_materials' => false],
            ],
        ]);

        $this->assertSame(MaterialType::Finished, $product->material->type);
        $this->assertSame('FG-TBL-01', $product->material->sku);
        $this->assertCount(2, $product->bomItems);
        $this->assertSame([10, 20], $product->routingSteps->pluck('sequence')->all());
        $this->assertSame(95.0, $product->plannedMinutesPerUnit());
        $this->assertSame(205.68, $product->material->unit_cost);
    }

    public function test_first_step_consumes_materials_when_none_is_flagged(): void
    {
        $workCenter = WorkCenter::factory()->create();

        $product = app(SaveProduct::class)->execute([
            'sku' => 'FG-1', 'name' => 'Bench', 'price' => 300.0,
            'routing' => [
                ['work_center_id' => $workCenter->id, 'name' => 'A', 'planned_minutes_per_unit' => 5],
                ['work_center_id' => $workCenter->id, 'name' => 'B', 'planned_minutes_per_unit' => 5],
            ],
        ]);

        $this->assertSame([true, false], $product->routingSteps->pluck('consumes_materials')->all());
    }

    public function test_explicit_consuming_step_is_respected(): void
    {
        $workCenter = WorkCenter::factory()->create();

        $product = app(SaveProduct::class)->execute([
            'sku' => 'FG-2', 'name' => 'Shelf', 'price' => 150.0,
            'routing' => [
                ['work_center_id' => $workCenter->id, 'name' => 'Prep', 'planned_minutes_per_unit' => 5, 'consumes_materials' => false],
                ['work_center_id' => $workCenter->id, 'name' => 'Build', 'planned_minutes_per_unit' => 5, 'consumes_materials' => true],
            ],
        ]);

        $this->assertSame([false, true], $product->routingSteps->pluck('consumes_materials')->all());
    }

    public function test_updating_a_product_replaces_bom_and_renames_finished_good(): void
    {
        $workCenter = WorkCenter::factory()->create();
        [$oldMaterial, $newMaterial] = Material::factory()->count(2)->create();
        $action = app(SaveProduct::class);

        $product = $action->execute([
            'sku' => 'FG-3', 'name' => 'Desk', 'price' => 500.0,
            'bom' => [['material_id' => $oldMaterial->id, 'quantity' => 1]],
            'routing' => [['work_center_id' => $workCenter->id, 'name' => 'Build', 'planned_minutes_per_unit' => 30]],
        ]);

        $action->execute([
            'sku' => 'FG-3B', 'name' => 'Standing desk', 'price' => 650.0,
            'bom' => [['material_id' => $newMaterial->id, 'quantity' => 3]],
            'routing' => [['work_center_id' => $workCenter->id, 'name' => 'Build', 'planned_minutes_per_unit' => 45]],
        ], $product);

        $product->refresh()->load('bomItems', 'material');
        $this->assertSame([$newMaterial->id], $product->bomItems->pluck('material_id')->all());
        $this->assertSame('Standing desk', $product->material->name);
        $this->assertSame('FG-3B', $product->material->sku);
        $this->assertDatabaseCount('materials', 3);
    }
}
