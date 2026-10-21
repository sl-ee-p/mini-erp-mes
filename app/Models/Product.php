<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Audit\Auditable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sellable item with a bill of materials and a production routing.
 *
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property float $price
 * @property int $material_id
 * @property bool $is_active
 * @property-read Material $material
 */
#[Fillable(['sku', 'name', 'description', 'price', 'material_id', 'is_active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use Auditable, HasFactory;

    /**
     * The finished-good stock item this product is produced into.
     *
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return HasMany<BomItem, $this>
     */
    public function bomItems(): HasMany
    {
        return $this->hasMany(BomItem::class);
    }

    /**
     * @return HasMany<RoutingStep, $this>
     */
    public function routingSteps(): HasMany
    {
        return $this->hasMany(RoutingStep::class)->orderBy('sequence');
    }

    /**
     * Total planned work content for one unit, in minutes.
     */
    public function plannedMinutesPerUnit(): float
    {
        return round((float) $this->routingSteps->sum('planned_minutes_per_unit'), 2);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<Product>  $query
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
            'price' => 'float',
            'is_active' => 'boolean',
        ];
    }
}
