<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Queries;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Enums\StepStatus;
use App\Models\Material;
use App\Models\ProductionOrderMaterial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Material still needed by released production: the bill-of-materials snapshot of every active production order
 * whose consuming step has not been completed yet. The BOM is backflushed in one go, so a line is either fully
 * outstanding (nothing consumed) or done. Orders whose units have all been scrapped before the consuming step
 * release their allocation, since that step can no longer receive anything.
 */
class MaterialRequirements
{
    /**
     * Outstanding quantity per material id.
     *
     * @param  list<int>|null  $materialIds  limit the lookup, e.g. to the materials on the current page
     * @return Collection<int, float>
     */
    public function allocated(?array $materialIds = null): Collection
    {
        return $this->outstanding()
            ->when($materialIds !== null, fn (Builder $query) => $query->whereIn('material_id', $materialIds))
            ->selectRaw('material_id, sum(required_qty) as allocated')
            ->groupBy('material_id')
            ->pluck('allocated', 'material_id')
            ->map(fn (mixed $quantity): float => round((float) $quantity, 3));
    }

    /**
     * Materials whose outstanding requirements exceed the stock on hand, largest gap first.
     *
     * @return Collection<int, array{material: Material, allocated: float, shortage: float}>
     */
    public function shortages(): Collection
    {
        $allocated = $this->allocated();

        return Material::query()
            ->whereKey($allocated->keys()->all())
            ->get()
            ->map(function (Material $material) use ($allocated): array {
                $required = $allocated->get($material->id, 0.0);

                return ['material' => $material, 'allocated' => $required, 'shortage' => round($required - $material->stock_qty, 3)];
            })
            ->filter(fn (array $row): bool => $row['shortage'] > 0)
            ->sortByDesc(fn (array $row): float => $row['shortage'] * $row['material']->unit_cost)
            ->values();
    }

    /**
     * The production orders still waiting to consume a material, earliest due date first.
     *
     * @return EloquentCollection<int, ProductionOrderMaterial>
     */
    public function openDemand(Material $material): EloquentCollection
    {
        return $this->outstanding()
            ->where('material_id', $material->id)
            ->with('productionOrder.product')
            ->get()
            ->sortBy(fn (ProductionOrderMaterial $line) => $line->productionOrder->due_date)
            ->values();
    }

    /**
     * @return Builder<ProductionOrderMaterial>
     */
    private function outstanding(): Builder
    {
        return ProductionOrderMaterial::query()
            ->where('consumed_qty', 0)
            ->where('required_qty', '>', 0)
            ->whereHas('productionOrder', fn (Builder $query) => $query
                ->whereIn('status', ProductionOrderStatus::active())
                // Once a completed step has no good units left, nothing reaches the consuming step any more
                // (a step that did consume has consumed_qty > 0 and is filtered out above).
                ->whereDoesntHave('steps', fn (Builder $query) => $query
                    ->where('status', StepStatus::Completed)
                    ->where('good_qty', 0)));
    }
}
