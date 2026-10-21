<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Audit\Auditable;
use App\Domain\Inventory\Enums\MaterialType;
use App\Domain\Inventory\Enums\UnitOfMeasure;
use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A stock-keeping item: either a raw material or the finished good of a product.
 *
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property MaterialType $type
 * @property UnitOfMeasure $unit
 * @property float $stock_qty
 * @property float $min_stock
 * @property float $unit_cost
 */
#[Fillable(['sku', 'name', 'type', 'unit', 'min_stock', 'unit_cost'])]
class Material extends Model
{
    /**
     * Largest value the `decimal(12, 3)` stock columns can hold (materials.stock_qty, stock_movements.quantity
     * and balance_after). Keep in sync with the create_materials / create_stock_movements migrations.
     */
    public const MAX_STOCK = 999999999.999;

    /** @use HasFactory<MaterialFactory> */
    use Auditable, HasFactory;

    /**
     * Stock balance changes are already traced by stock movements.
     *
     * @var list<string>
     */
    protected array $auditExclude = ['stock_qty'];

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @return HasOne<Product, $this>
     */
    public function product(): HasOne
    {
        return $this->hasOne(Product::class);
    }

    public function isBelowMinimum(): bool
    {
        return $this->min_stock > 0 && $this->stock_qty < $this->min_stock;
    }

    /**
     * Whether stock has been booked for the material or it is planned into products or production. Its unit then
     * gives all those quantities their meaning and can no longer change.
     */
    public function isInUse(): bool
    {
        if (! $this->exists) {
            return false;
        }

        if ($this->stockMovements()->exists() || BomItem::query()->where('material_id', $this->id)->exists()) {
            return true;
        }

        return ProductionOrderMaterial::query()->where('material_id', $this->id)->exists();
    }

    /**
     * Quantity needed to get back to the minimum stock level.
     */
    public function shortage(): float
    {
        return max(0.0, round($this->min_stock - $this->stock_qty, 3));
    }

    /**
     * @param  Builder<Material>  $query
     */
    public function scopeBelowMinimum(Builder $query): void
    {
        $query->where('min_stock', '>', 0)->whereColumn('stock_qty', '<', 'min_stock');
    }

    /**
     * @param  Builder<Material>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $query, string $term) => $query->where(fn (Builder $query) => $query
            ->whereContains('name', $term)
            ->orWhereContains('sku', $term)));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MaterialType::class,
            'unit' => UnitOfMeasure::class,
            'stock_qty' => 'float',
            'min_stock' => 'float',
            'unit_cost' => 'float',
        ];
    }
}
