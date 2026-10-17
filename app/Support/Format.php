<?php

declare(strict_types=1);

namespace App\Support;

use App\Domain\Inventory\Enums\UnitOfMeasure;

/**
 * Display helpers shared by Blade views and PDFs.
 */
final class Format
{
    public static function quantity(float|int|null $quantity, ?UnitOfMeasure $unit = null): string
    {
        $formatted = rtrim(rtrim(number_format((float) $quantity, 3, '.', ','), '0'), '.');

        return $unit ? "{$formatted} {$unit->label()}" : $formatted;
    }

    public static function money(float|int|null $amount): string
    {
        return '€ '.number_format((float) $amount, 2, '.', ',');
    }

    public static function minutes(float|int|null $minutes): string
    {
        $minutes = (int) round((float) $minutes);

        return $minutes >= 60 ? sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60) : "{$minutes}m";
    }
}
