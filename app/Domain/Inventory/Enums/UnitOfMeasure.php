<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Enums;

enum UnitOfMeasure: string
{
    case Piece = 'pcs';
    case Meter = 'm';
    case SquareMeter = 'm2';
    case Kilogram = 'kg';
    case Liter = 'l';

    public function label(): string
    {
        return match ($this) {
            self::Piece => 'pcs',
            self::Meter => 'm',
            self::SquareMeter => 'm²',
            self::Kilogram => 'kg',
            self::Liter => 'L',
        };
    }

    /**
     * Whole-unit items cannot be split, e.g. you cannot consume 0.5 hinges.
     */
    public function isDiscrete(): bool
    {
        return $this === self::Piece;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $unit) => ['value' => $unit->value, 'label' => $unit->label()], self::cases()), 'label', 'value');
    }
}
