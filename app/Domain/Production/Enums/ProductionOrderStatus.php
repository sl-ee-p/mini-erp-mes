<?php

declare(strict_types=1);

namespace App\Domain\Production\Enums;

use App\Domain\Shared\GuardsTransitions;

enum ProductionOrderStatus: string
{
    use GuardsTransitions;

    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Planned => [self::InProgress, self::Cancelled],
            self::InProgress => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            default => ucfirst($this->value),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planned => 'sky',
            self::InProgress => 'amber',
            self::Completed => 'emerald',
            self::Cancelled => 'red',
        };
    }

    /**
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::Planned, self::InProgress];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases()), 'label', 'value');
    }
}
