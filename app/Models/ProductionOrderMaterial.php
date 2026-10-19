<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProductionOrderMaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BOM line snapshotted onto a production order when it is released.
 *
 * @property int $id
 * @property int $production_order_id
 * @property int $material_id
 * @property float $quantity_per_unit
 * @property float $required_qty
 * @property float $consumed_qty
 * @property-read Material $material
 * @property-read ProductionOrder $productionOrder
 */
#[Fillable(['production_order_id', 'material_id', 'quantity_per_unit', 'required_qty', 'consumed_qty'])]
class ProductionOrderMaterial extends Model
{
    /** @use HasFactory<ProductionOrderMaterialFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ProductionOrder, $this>
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_per_unit' => 'float',
            'required_qty' => 'float',
            'consumed_qty' => 'float',
        ];
    }
}
