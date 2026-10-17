<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Enums;

enum StockMovementType: string
{
    /** Goods receipt or finished goods booked in from production. */
    case In = 'in';

    /** Consumption by production or shipment to a customer. */
    case Out = 'out';

    /** Correction after a physical count; the signed delta is stored. */
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Stock in',
            self::Out => 'Stock out',
            self::Adjustment => 'Adjustment',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::In => 'emerald',
            self::Out => 'amber',
            self::Adjustment => 'sky',
        };
    }

    /**
     * Converts a user-entered quantity into the signed delta applied to the stock balance.
     */
    public function signedQuantity(float $quantity): float
    {
        return match ($this) {
            self::In => abs($quantity),
            self::Out => -abs($quantity),
            self::Adjustment => $quantity,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $type) => ['value' => $type->value, 'label' => $type->label()], self::cases()), 'label', 'value');
    }
}
