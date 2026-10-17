<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Exceptions;

use App\Models\Material;
use DomainException;

class InsufficientStock extends DomainException
{
    public function __construct(
        public readonly Material $material,
        public readonly float $requested,
    ) {
        parent::__construct(sprintf(
            'Not enough stock of %s (%s): requested %s %s, available %s %s.',
            $material->name,
            $material->sku,
            self::format($requested),
            $material->unit->label(),
            self::format($material->stock_qty),
            $material->unit->label(),
        ));
    }

    private static function format(float $quantity): string
    {
        return rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.');
    }
}
