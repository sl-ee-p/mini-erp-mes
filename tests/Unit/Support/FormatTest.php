<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Support\Format;
use PHPUnit\Framework\TestCase;

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
}
