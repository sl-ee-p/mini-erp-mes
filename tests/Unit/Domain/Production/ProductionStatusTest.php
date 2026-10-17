<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Production;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Enums\StepStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProductionStatusTest extends TestCase
{
    /**
     * @return array<string, array{StepStatus, StepStatus, bool}>
     */
    public static function stepTransitions(): array
    {
        return [
            'start a pending step' => [StepStatus::Pending, StepStatus::Running, true],
            'pause a running step' => [StepStatus::Running, StepStatus::Paused, true],
            'resume a paused step' => [StepStatus::Paused, StepStatus::Running, true],
            'complete a running step' => [StepStatus::Running, StepStatus::Completed, true],
            'cannot complete a paused step' => [StepStatus::Paused, StepStatus::Completed, false],
            'cannot complete a pending step' => [StepStatus::Pending, StepStatus::Completed, false],
            'cannot pause a pending step' => [StepStatus::Pending, StepStatus::Paused, false],
            'cannot restart a completed step' => [StepStatus::Completed, StepStatus::Running, false],
        ];
    }

    #[DataProvider('stepTransitions')]
    public function test_step_transitions(StepStatus $from, StepStatus $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    /**
     * @return array<string, array{ProductionOrderStatus, ProductionOrderStatus, bool}>
     */
    public static function orderTransitions(): array
    {
        return [
            'planned → in progress' => [ProductionOrderStatus::Planned, ProductionOrderStatus::InProgress, true],
            'planned → cancelled' => [ProductionOrderStatus::Planned, ProductionOrderStatus::Cancelled, true],
            'in progress → completed' => [ProductionOrderStatus::InProgress, ProductionOrderStatus::Completed, true],
            'in progress → cancelled' => [ProductionOrderStatus::InProgress, ProductionOrderStatus::Cancelled, false],
            'planned → completed' => [ProductionOrderStatus::Planned, ProductionOrderStatus::Completed, false],
            'completed is final' => [ProductionOrderStatus::Completed, ProductionOrderStatus::InProgress, false],
        ];
    }

    #[DataProvider('orderTransitions')]
    public function test_production_order_transitions(ProductionOrderStatus $from, ProductionOrderStatus $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }
}
