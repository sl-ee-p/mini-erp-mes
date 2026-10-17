<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::Sales, UserRole::Planner);
    }

    public function view(User $user, Customer $customer): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Sales);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->hasRole(UserRole::Sales);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasRole(UserRole::Sales);
    }
}
