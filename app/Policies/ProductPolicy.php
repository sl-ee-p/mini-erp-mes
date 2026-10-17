<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;

/**
 * Everyone can look up products; planners maintain them.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Planner);
    }

    public function update(User $user, Product $product): bool
    {
        return $user->hasRole(UserRole::Planner);
    }
}
