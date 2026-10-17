<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Inventory\Enums\StockMovementType;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Immutable ledger entry. The material's stock balance is the running sum of these rows.
 *
 * @property int $id
 * @property int $material_id
 * @property StockMovementType $type
 * @property float $quantity
 * @property float $balance_after
 * @property string|null $reason
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property int|null $user_id
 * @property Carbon $created_at
 * @property-read Material $material
 * @property-read User|null $user
 */
#[Fillable(['material_id', 'type', 'quantity', 'balance_after', 'reason', 'reference_type', 'reference_id', 'user_id'])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The document that caused the movement (production step, sales order, import…).
     *
     * @return MorphTo<Model, $this>
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'float',
            'balance_after' => 'float',
            'created_at' => 'datetime',
        ];
    }
}
