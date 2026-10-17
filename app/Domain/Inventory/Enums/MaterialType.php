<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Enums;

enum MaterialType: string
{
    case Raw = 'raw';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Raw => 'Raw material',
            self::Finished => 'Finished good',
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
