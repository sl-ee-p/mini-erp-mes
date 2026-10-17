<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory;

use App\Models\Material;
use PHPUnit\Framework\TestCase;

class MaterialStockLevelTest extends TestCase
{
    public function test_material_below_minimum_reports_shortage(): void
    {
        $material = $this->material(stock: 4.5, minimum: 10);

        $this->assertTrue($material->isBelowMinimum());
        $this->assertSame(5.5, $material->shortage());
    }

    public function test_material_exactly_at_minimum_is_not_low(): void
    {
        $material = $this->material(stock: 10, minimum: 10);

        $this->assertFalse($material->isBelowMinimum());
        $this->assertSame(0.0, $material->shortage());
    }

    public function test_zero_minimum_disables_alerts(): void
    {
        $this->assertFalse($this->material(stock: 0, minimum: 0)->isBelowMinimum());
    }

    public function test_shortage_is_rounded_to_three_decimals(): void
    {
        $this->assertSame(0.3, $this->material(stock: 0.1, minimum: 0.4)->shortage());
    }

    private function material(float $stock, float $minimum): Material
    {
        $material = new Material;
        $material->setRawAttributes(['stock_qty' => $stock, 'min_stock' => $minimum]);

        return $material;
    }
}
