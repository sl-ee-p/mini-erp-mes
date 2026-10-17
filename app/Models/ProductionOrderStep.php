<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Enums\StepStatus;
use Database\Factories\ProductionOrderStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $production_order_id
 * @property int|null $routing_step_id
 * @property int $work_center_id
 * @property int $sequence
 * @property string $name
 * @property bool $consumes_materials
 * @property StepStatus $status
 * @property float $planned_minutes
 * @property int $actual_seconds
 * @property int|null $input_qty
 * @property int|null $good_qty
 * @property int|null $scrap_qty
 * @property int|null $operator_id
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property-read ProductionOrder $productionOrder
 * @property-read WorkCenter $workCenter
 * @property-read User|null $operator
 * @property-read StepTimeLog|null $openTimeLog
 */
#[Fillable(['production_order_id', 'routing_step_id', 'work_center_id', 'sequence', 'name', 'consumes_materials', 'status', 'planned_minutes', 'actual_seconds', 'input_qty', 'good_qty', 'scrap_qty', 'operator_id', 'started_at', 'completed_at'])]
class ProductionOrderStep extends Model
{
    /** @use HasFactory<ProductionOrderStepFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ProductionOrder, $this>
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * @return BelongsTo<WorkCenter, $this>
     */
    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    /**
     * @return HasMany<StepTimeLog, $this>
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(StepTimeLog::class);
    }

    /**
     * @return HasOne<StepTimeLog, $this>
     */
    public function openTimeLog(): HasOne
    {
        return $this->hasOne(StepTimeLog::class)->whereNull('ended_at');
    }

    /**
     * Seconds worked so far, including the interval currently running.
     */
    public function elapsedSeconds(): int
    {
        $running = $this->status === StepStatus::Running && $this->openTimeLog
            ? (int) $this->openTimeLog->started_at->diffInSeconds(now(), true)
            : 0;

        return $this->actual_seconds + $running;
    }

    public function isLastStep(): bool
    {
        return ! static::query()
            ->where('production_order_id', $this->production_order_id)
            ->where('sequence', '>', $this->sequence)
            ->exists();
    }

    /**
     * The step before this one in the routing, if any.
     */
    public function previousStep(): ?self
    {
        return static::query()
            ->where('production_order_id', $this->production_order_id)
            ->where('sequence', '<', $this->sequence)
            ->orderByDesc('sequence')
            ->first();
    }

    /**
     * Steps an operator can act on now: running, paused, or pending with every earlier step completed.
     *
     * @param  Builder<ProductionOrderStep>  $query
     */
    public function scopeActionable(Builder $query): void
    {
        $query->whereIn('status', [StepStatus::Pending, StepStatus::Running, StepStatus::Paused])
            ->whereHas('productionOrder', fn (Builder $query) => $query->whereIn('status', ProductionOrderStatus::active()))
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('production_order_steps as previous')
                ->whereColumn('previous.production_order_id', 'production_order_steps.production_order_id')
                ->whereColumn('previous.sequence', '<', 'production_order_steps.sequence')
                ->where('previous.status', '!=', StepStatus::Completed->value));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StepStatus::class,
            'sequence' => 'integer',
            'consumes_materials' => 'boolean',
            'planned_minutes' => 'float',
            'actual_seconds' => 'integer',
            'input_qty' => 'integer',
            'good_qty' => 'integer',
            'scrap_qty' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
