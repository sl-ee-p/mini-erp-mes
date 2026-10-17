<?php

declare(strict_types=1);

namespace App\Domain\Shared;

/**
 * Turns a backed enum into a small state machine. The enum declares its graph in `transitions()`.
 *
 * @phpstan-require-implements \BackedEnum
 */
trait GuardsTransitions
{
    /**
     * @return list<self>
     */
    abstract public function transitions(): array;

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->transitions(), true);
    }

    /**
     * @throws InvalidStateTransition
     */
    public function assertCanTransitionTo(self $target, string $subject = 'Record'): void
    {
        if (! $this->canTransitionTo($target)) {
            throw InvalidStateTransition::between($this, $target, $subject);
        }
    }

    public function isFinal(): bool
    {
        return $this->transitions() === [];
    }
}
