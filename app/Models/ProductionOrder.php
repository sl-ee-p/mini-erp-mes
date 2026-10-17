<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Audit\Auditable;
use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Enums\StepStatus;
use Database\Factories\ProductionOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Work order to produce one sales order line, following a snapshot of the product routing and BOM.
 *
 * @property int $id
 * @property string $number
 * @property int $sales_order_id
 * @property int $sales_order_line_id
 * @property int $product_id
 * @property int $quantity
 * @property ProductionOrderStatus $status
 * @property Carbon $due_date
 * @property float $planned_minutes
 * @property int $good_qty
 * @property int $scrap_qty
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property-read SalesOrder $salesOrder
 * @property-read SalesOrderLine $salesOrderLine
 * @property-read Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductionOrderStep> $steps
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductionOrderMaterial> $materials
 */
#[Fillable(['number', 'sales_order_id', 'sales_order_line_id', 'product_id', 'quantity', 'status', 'due_date', 'planned_minutes', 'good_qty', 'scrap_qty', 'started_at', 'completed_at'])]
class ProductionOrder extends Model
{
    /** @use HasFactory<ProductionOrderFactory> */
    use Auditable, HasFactory;

    /**
     * @return BelongsTo<SalesOrder, $this>
     */
    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    /**
     * @return BelongsTo<SalesOrderLine, $this>
     */
    public function salesOrderLine(): BelongsTo
    {
        return $this->belongsTo(SalesOrderLine::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Steps loaded through the order get the order set as their parent, avoiding N+1 queries.
     *
     * @return HasMany<ProductionOrderStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ProductionOrderStep::class)->orderBy('sequence')->chaperone('productionOrder');
    }

    /**
     * @return HasMany<ProductionOrderMaterial, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(ProductionOrderMaterial::class);
    }

    public function transitionTo(ProductionOrderStatus $status): void
    {
        $this->status->assertCanTransitionTo($status, "Production order {$this->number}");

        $this->status = $status;

        match ($status) {
            ProductionOrderStatus::InProgress => $this->started_at = now(),
            ProductionOrderStatus::Completed => $this->completed_at = now(),
            default => null,
        };

        $this->save();
    }

    /**
     * Share of routing steps completed, 0–100. Uses the loaded relation when available.
     */
    public function progressPercent(): int
    {
        $steps = $this->relationLoaded('steps') ? $this->steps : $this->steps()->get(['id', 'status']);

        if ($steps->isEmpty()) {
            return 0;
        }

        return (int) round($steps->where('status', StepStatus::Completed)->count() / $steps->count() * 100);
    }

    public function actualMinutes(): float
    {
        $seconds = $this->relationLoaded('steps') ? $this->steps->sum('actual_seconds') : $this->steps()->sum('actual_seconds');

        return round((int) $seconds / 60, 1);
    }

    public function isLate(): bool
    {
        return in_array($this->status, ProductionOrderStatus::active(), true) && $this->due_date->lt(today());
    }

    /**
     * @param  Builder<ProductionOrder>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', ProductionOrderStatus::active());
    }

    /**
     * @param  Builder<ProductionOrder>  $query
     */
    public function scopeLate(Builder $query): void
    {
        $query->whereIn('status', ProductionOrderStatus::active())->whereDate('due_date', '<', today());
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProductionOrderStatus::class,
            'quantity' => 'integer',
            'due_date' => 'date',
            'planned_minutes' => 'float',
            'good_qty' => 'integer',
            'scrap_qty' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
