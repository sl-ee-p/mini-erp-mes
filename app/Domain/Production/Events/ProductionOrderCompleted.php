<?php

declare(strict_types=1);

namespace App\Domain\Production\Events;

use App\Models\ProductionOrder;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched inside the completing step's transaction.
 */
class ProductionOrderCompleted
{
    use Dispatchable;

    public function __construct(public readonly ProductionOrder $productionOrder) {}
}
