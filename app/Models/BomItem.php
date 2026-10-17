<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BomItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $material_id
 * @property float $quantity
 * @property-read Material $material
 * @property-read Product $product
 */
#[Fillable(['product_id', 'material_id', 'quantity'])]
class BomItem extends Model
{
    /** @use HasFactory<BomItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
        return ['quantity' => 'float'];
    }
}
