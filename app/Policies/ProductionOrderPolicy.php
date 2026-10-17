<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ProductionOrder;
use App\Models\User;

/**
 * Production orders are visible to every role; operating steps is governed by the "use-shop-floor" gate.
 */
class ProductionOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProductionOrder $productionOrder): bool
    {
        return true;
    }
}
