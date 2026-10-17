<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Actions;

use App\Domain\Inventory\Enums\MaterialType;
use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Models\BomItem;
use App\Models\Material;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Creates or updates a product together with its finished-good stock item, bill of materials and routing.
 *
 * @phpstan-type BomLine array{material_id: int, quantity: float}
 * @phpstan-type RoutingLine array{work_center_id: int, name: string, planned_minutes_per_unit: float, consumes_materials?: bool}
 * @phpstan-type ProductInput array{sku: string, name: string, description?: string|null, price: float, is_active?: bool, bom?: list<BomLine>, routing: list<RoutingLine>}
 */
class SaveProduct
{
    public const SEQUENCE_STEP = 10;

    /**
     * @param  ProductInput  $data
     */
    public function execute(array $data, ?Product $product = null): Product
    {
        return DB::transaction(function () use ($data, $product): Product {
            $attributes = [
                'sku' => $data['sku'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'price' => $data['price'],
                'is_active' => $data['is_active'] ?? true,
            ];

            if ($product === null) {
                $finishedGood = Material::create([
                    'sku' => $data['sku'],
                    'name' => $data['name'],
                    'type' => MaterialType::Finished,
                    'unit' => UnitOfMeasure::Piece,
                ]);

                $product = Product::create([...$attributes, 'material_id' => $finishedGood->id]);
            } else {
                $product->update($attributes);
                $product->material->update(['sku' => $data['sku'], 'name' => $data['name']]);
            }

            $this->syncBillOfMaterials($product, $data['bom'] ?? []);
            $this->syncRouting($product, $data['routing']);
            $this->rollUpStandardCost($product);

            return $product->load(['material', 'bomItems.material', 'routingSteps.workCenter']);
        });
    }

    /**
     * @param  list<BomLine>  $lines
     */
    private function syncBillOfMaterials(Product $product, array $lines): void
    {
        $product->bomItems()->delete();

        $product->bomItems()->createMany(array_map(fn (array $line): array => [
            'material_id' => $line['material_id'],
            'quantity' => round($line['quantity'], 3),
        ], $lines));
    }

    /**
     * The finished good is valued at the material cost of its bill of materials.
     */
    private function rollUpStandardCost(Product $product): void
    {
        $cost = $product->bomItems()->with('material')->get()
            ->sum(fn (BomItem $item): float => $item->quantity * $item->material->unit_cost);

        $product->material->update(['unit_cost' => round($cost, 2)]);
    }

    /**
     * Steps are renumbered 10, 20, 30… so an extra step can be slotted in later without renumbering.
     * If no step is flagged to consume materials, the first step does.
     *
     * @param  list<RoutingLine>  $steps
     */
    private function syncRouting(Product $product, array $steps): void
    {
        $anyConsumes = collect($steps)->contains(fn (array $step): bool => (bool) ($step['consumes_materials'] ?? false));

        $product->routingSteps()->delete();

        $product->routingSteps()->createMany(array_map(fn (array $step, int $index): array => [
            'sequence' => ($index + 1) * self::SEQUENCE_STEP,
            'work_center_id' => $step['work_center_id'],
            'name' => $step['name'],
            'planned_minutes_per_unit' => $step['planned_minutes_per_unit'],
            'consumes_materials' => $anyConsumes ? (bool) ($step['consumes_materials'] ?? false) : $index === 0,
        ], $steps, array_keys($steps)));
    }
}
