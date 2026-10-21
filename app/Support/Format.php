<?php

declare(strict_types=1);

namespace App\Support;

use App\Domain\Inventory\Enums\UnitOfMeasure;
use Carbon\CarbonInterface;

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

    /**
     * Renders a stored (UTC) timestamp in the workshop's display timezone (`app.display_timezone`).
     * Date-only attributes (due dates, order dates) must not go through here: they have no time to shift.
     */
    public static function dateTime(?CarbonInterface $moment, string $format = 'd M Y H:i'): ?string
    {
        return $moment?->copy()->setTimezone((string) config('app.display_timezone', 'UTC'))->format($format);
    }

    public static function minutes(float|int|null $minutes): string
    {
        $minutes = (int) round((float) $minutes);

        return $minutes >= 60 ? sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60) : "{$minutes}m";
    }

    /**
     * Clock-style duration that keeps counting past 24 hours (unlike gmdate), e.g. 97510 -> 27:05:10.
     */
    public static function duration(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60);
    }
}
