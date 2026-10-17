<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StepTimeLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One uninterrupted work interval on a step (start → pause/complete).
 *
 * @property int $id
 * @property int $production_order_step_id
 * @property int $user_id
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property int|null $seconds
 * @property-read ProductionOrderStep $step
 * @property-read User $user
 */
#[Fillable(['production_order_step_id', 'user_id', 'started_at', 'ended_at', 'seconds'])]
class StepTimeLog extends Model
{
    /** @use HasFactory<StepTimeLogFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return BelongsTo<ProductionOrderStep, $this>
     */
    public function step(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderStep::class, 'production_order_step_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Closes the interval and returns its length in seconds.
     */
    public function close(?Carbon $at = null): int
    {
        $at ??= now();
        $seconds = (int) $this->started_at->diffInSeconds($at, true);

        $this->update(['ended_at' => $at, 'seconds' => $seconds]);

        return $seconds;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'seconds' => 'integer',
        ];
    }
}
