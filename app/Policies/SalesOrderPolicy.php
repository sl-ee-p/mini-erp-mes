<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\SalesOrder;
use App\Models\User;

/**
 * Role-based access only. Whether the order's status allows an action is a business rule enforced by the actions.
 */
class SalesOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Sales, UserRole::Planner);
    }

    public function view(User $user, SalesOrder $salesOrder): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Sales);
    }

    public function update(User $user, SalesOrder $salesOrder): bool
    {
        return $user->hasRole(UserRole::Sales);
    }

    public function confirm(User $user, SalesOrder $salesOrder): bool
    {
        return $user->hasRole(UserRole::Sales, UserRole::Planner);
    }

    public function cancel(User $user, SalesOrder $salesOrder): bool
    {
        return $user->hasRole(UserRole::Sales);
    }

    public function ship(User $user, SalesOrder $salesOrder): bool
    {
        return $user->hasRole(UserRole::Sales);
    }
}
