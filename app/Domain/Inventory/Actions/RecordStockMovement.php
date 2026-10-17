<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Events\StockBelowMinimum;
use App\Domain\Inventory\Exceptions\InsufficientStock;
use App\Models\Material;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The single entry point for changing a stock balance. Every change is written to the ledger.
 */
class RecordStockMovement
{
    /**
     * @param  float  $quantity  Positive amount for In/Out; signed delta for Adjustment.
     *
     * @throws InsufficientStock when the movement would make the balance negative
     */
    public function execute(
        Material $material,
        StockMovementType $type,
        float $quantity,
        ?string $reason = null,
        ?Model $reference = null,
        ?User $user = null,
    ): StockMovement {
        $delta = round($type->signedQuantity($quantity), 3);

        if ($delta == 0.0) {
            throw new InvalidArgumentException('A stock movement must change the balance.');
        }

        return DB::transaction(function () use ($material, $type, $delta, $reason, $reference, $user): StockMovement {
            /** @var Material $locked */
            $locked = Material::query()->lockForUpdate()->findOrFail($material->getKey());

            $newBalance = round($locked->stock_qty + $delta, 3);

            if ($newBalance < 0) {
                throw new InsufficientStock($locked, abs($delta));
            }

            $wasBelowMinimum = $locked->isBelowMinimum();

            $locked->stock_qty = $newBalance;
            $locked->save();

            $movement = $locked->stockMovements()->create([
                'type' => $type,
                'quantity' => $delta,
                'balance_after' => $newBalance,
                'reason' => $reason,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'user_id' => $user?->getKey(),
            ]);

            $material->stock_qty = $newBalance;
            $material->syncOriginalAttribute('stock_qty');

            if (! $wasBelowMinimum && $locked->isBelowMinimum()) {
                StockBelowMinimum::dispatch($locked);
            }

            return $movement;
        });
    }
}
