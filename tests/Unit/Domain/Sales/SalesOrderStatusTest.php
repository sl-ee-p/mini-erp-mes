<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Sales;

use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Domain\Shared\InvalidStateTransition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SalesOrderStatusTest extends TestCase
{
    /**
     * Every pair of statuses with the expected verdict, so an accidental new edge fails the suite.
     *
     * @return iterable<string, array{SalesOrderStatus, SalesOrderStatus, bool}>
     */
    public static function transitionMatrix(): iterable
    {
        $allowed = [
            'draft' => ['confirmed', 'cancelled'],
            'confirmed' => ['in_production', 'cancelled'],
            'in_production' => ['completed'],
            'completed' => ['shipped'],
            'shipped' => [],
            'cancelled' => [],
        ];

        foreach (SalesOrderStatus::cases() as $from) {
            foreach (SalesOrderStatus::cases() as $to) {
                yield "{$from->value} → {$to->value}" => [$from, $to, in_array($to->value, $allowed[$from->value], true)];
            }
        }
    }

    #[DataProvider('transitionMatrix')]
    public function test_transition_rules(SalesOrderStatus $from, SalesOrderStatus $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    public function test_invalid_transition_throws_with_readable_message(): void
    {
        $this->expectException(InvalidStateTransition::class);
        $this->expectExceptionMessage('Sales order SO-1 cannot move from "shipped" to "draft".');

        SalesOrderStatus::Shipped->assertCanTransitionTo(SalesOrderStatus::Draft, 'Sales order SO-1');
    }

    public function test_shipped_and_cancelled_are_final(): void
    {
        $this->assertTrue(SalesOrderStatus::Shipped->isFinal());
        $this->assertTrue(SalesOrderStatus::Cancelled->isFinal());
        $this->assertFalse(SalesOrderStatus::Draft->isFinal());
    }

    public function test_open_statuses_are_the_undelivered_commitments(): void
    {
        $this->assertSame(
            [SalesOrderStatus::Confirmed, SalesOrderStatus::InProduction, SalesOrderStatus::Completed],
            SalesOrderStatus::open(),
        );
    }
}
