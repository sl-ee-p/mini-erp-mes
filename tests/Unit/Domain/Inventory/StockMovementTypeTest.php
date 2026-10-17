<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory;

use App\Domain\Inventory\Enums\StockMovementType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StockMovementTypeTest extends TestCase
{
    /**
     * @return array<string, array{StockMovementType, float, float}>
     */
    public static function signedQuantities(): array
    {
        return [
            'in is always positive' => [StockMovementType::In, 5.0, 5.0],
            'in ignores sign typos' => [StockMovementType::In, -5.0, 5.0],
            'out is always negative' => [StockMovementType::Out, 3.25, -3.25],
            'out ignores sign typos' => [StockMovementType::Out, -3.25, -3.25],
            'adjustment keeps positive delta' => [StockMovementType::Adjustment, 2.0, 2.0],
            'adjustment keeps negative delta' => [StockMovementType::Adjustment, -2.0, -2.0],
        ];
    }

    #[DataProvider('signedQuantities')]
    public function test_signed_quantity(StockMovementType $type, float $input, float $expected): void
    {
        $this->assertSame($expected, $type->signedQuantity($input));
    }
}
