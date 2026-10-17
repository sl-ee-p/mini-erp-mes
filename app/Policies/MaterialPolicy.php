<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Material;
use App\Models\User;

/**
 * Everyone can look up materials; planners maintain them.
 */
class MaterialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Material $material): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Planner);
    }

    public function update(User $user, Material $material): bool
    {
        return $user->hasRole(UserRole::Planner);
    }
}
