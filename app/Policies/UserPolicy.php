<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Only administrators manage accounts; they are granted access by the global Gate::before hook.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return false;
    }
}
