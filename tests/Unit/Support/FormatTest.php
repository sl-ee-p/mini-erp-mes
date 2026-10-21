<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class FormatTest extends TestCase
{
    public function test_quantity_trims_trailing_zeros_and_appends_unit(): void
    {
        $this->assertSame('12.5 m²', Format::quantity(12.500, UnitOfMeasure::SquareMeter));
        $this->assertSame('3', Format::quantity(3.0));
        $this->assertSame('1,250.125', Format::quantity(1250.125));
    }

    public function test_money_uses_two_decimals(): void
    {
        $this->assertSame('€ 1,499.90', Format::money(1499.9));
    }

    public function test_minutes_are_humanised(): void
    {
        $this->assertSame('45m', Format::minutes(45));
        $this->assertSame('2h 05m', Format::minutes(125));
    }

    public function test_duration_keeps_counting_past_24_hours(): void
    {
        $this->assertSame('00:00:00', Format::duration(0));
        $this->assertSame('00:00:59', Format::duration(59));
        $this->assertSame('01:00:00', Format::duration(3600));
        $this->assertSame('27:05:10', Format::duration(97510));
    }

    public function test_date_time_is_rendered_in_the_display_timezone(): void
    {
        $moment = CarbonImmutable::parse('2026-07-01 22:30:00', 'UTC');

        config()->set('app.display_timezone', 'UTC');
        $this->assertSame('01 Jul 2026 22:30', Format::dateTime($moment));

        config()->set('app.display_timezone', 'Europe/Rome');
        $this->assertSame('02 Jul 2026 00:30', Format::dateTime($moment));
        $this->assertSame('2026-07-02', Format::dateTime($moment, 'Y-m-d'));
        $this->assertSame('22:30', $moment->format('H:i'), 'the original instance is not mutated');
        $this->assertNull(Format::dateTime(null));
    }
}
