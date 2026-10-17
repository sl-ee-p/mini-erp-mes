<?php

declare(strict_types=1);

namespace App\Domain\Production\Enums;

use App\Domain\Shared\GuardsTransitions;

enum StepStatus: string
{
    use GuardsTransitions;

    case Pending = 'pending';
    case Running = 'running';
    case Paused = 'paused';
    case Completed = 'completed';

    /**
     * pending → running ⇄ paused; running → completed. Completing requires the clock to be running,
     * so every completed step has an accurate time record.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Running],
            self::Running => [self::Paused, self::Completed],
            self::Paused => [self::Running],
            self::Completed => [],
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'stone',
            self::Running => 'emerald',
            self::Paused => 'amber',
            self::Completed => 'indigo',
        };
    }
}
