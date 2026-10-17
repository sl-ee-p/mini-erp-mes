<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RoutingStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int $work_center_id
 * @property int $sequence
 * @property string $name
 * @property float $planned_minutes_per_unit
 * @property bool $consumes_materials
 * @property-read WorkCenter $workCenter
 */
#[Fillable(['product_id', 'work_center_id', 'sequence', 'name', 'planned_minutes_per_unit', 'consumes_materials'])]
class RoutingStep extends Model
{
    /** @use HasFactory<RoutingStepFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<WorkCenter, $this>
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'planned_minutes_per_unit' => 'float',
            'consumes_materials' => 'boolean',
        ];
    }
}
