<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Actions;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\Material;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Books the difference between a physical count and the system balance as an adjustment.
 */
class AdjustStockToCount
{
    public function __construct(private readonly RecordStockMovement $recordStockMovement) {}

    /**
     * @return StockMovement|null null when the count already matches the system balance
     */
    public function execute(Material $material, float $countedQuantity, string $reason, ?User $user = null): ?StockMovement
    {
        if ($countedQuantity < 0) {
            throw new InvalidArgumentException('A counted quantity cannot be negative.');
        }

        // The difference is taken against the balance under lock, not the one loaded with the request, so a
        // movement booked in the meantime cannot leave the stock off the counted quantity.
        return DB::transaction(function () use ($material, $countedQuantity, $reason, $user): ?StockMovement {
            /** @var Material $locked */
            $locked = Material::query()->lockForUpdate()->findOrFail($material->getKey());

            $delta = round($countedQuantity - $locked->stock_qty, 3);

            if ($delta == 0.0) {
                $material->stock_qty = $locked->stock_qty;
                $material->syncOriginalAttribute('stock_qty');

                return null;
            }

            return $this->recordStockMovement->execute($material, StockMovementType::Adjustment, $delta, $reason, user: $user);
        });
    }
}
