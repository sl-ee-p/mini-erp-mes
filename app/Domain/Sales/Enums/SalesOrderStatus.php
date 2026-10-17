<?php

declare(strict_types=1);

namespace App\Domain\Sales\Enums;

use App\Domain\Shared\GuardsTransitions;

enum SalesOrderStatus: string
{
    use GuardsTransitions;

    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case InProduction = 'in_production';
    case Completed = 'completed';
    case Shipped = 'shipped';
    case Cancelled = 'cancelled';

    /**
     * draft → confirmed → in production → completed → shipped; draft/confirmed → cancelled.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Draft => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::InProduction, self::Cancelled],
            self::InProduction => [self::Completed],
            self::Completed => [self::Shipped],
            self::Shipped, self::Cancelled => [],
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::InProduction => 'In production',
            default => ucfirst($this->value),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'stone',
            self::Confirmed => 'sky',
            self::InProduction => 'amber',
            self::Completed => 'indigo',
            self::Shipped => 'emerald',
            self::Cancelled => 'red',
        };
    }

    /**
     * Statuses in which the order is a commitment to the customer that still has to be delivered.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Confirmed, self::InProduction, self::Completed];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases()), 'label', 'value');
    }
}
