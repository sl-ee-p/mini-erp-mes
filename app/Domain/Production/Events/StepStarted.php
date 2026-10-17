<?php

declare(strict_types=1);

namespace App\Domain\Production\Events;

use App\Models\ProductionOrderStep;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched inside the step transaction: listeners run atomically with the status change,
 * so a failing side effect (e.g. not enough stock) rolls the whole operation back.
 */
class StepStarted
{
    use Dispatchable;

    public function __construct(
        public readonly ProductionOrderStep $step,
        public readonly User $user,
    ) {}
}
