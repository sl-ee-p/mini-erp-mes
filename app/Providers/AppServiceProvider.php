<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        $this->defineGates();
    }

    /**
     * Screen-level abilities. Model-level rules live in App\Policies.
     */
    private function defineGates(): void
    {
        Gate::before(fn (User $user): ?bool => $user->isAdmin() ? true : null);

        Gate::define('view-dashboard', fn (User $user): bool => $user->hasRole(UserRole::Planner, UserRole::Sales));
        Gate::define('use-shop-floor', fn (User $user): bool => $user->hasRole(UserRole::Planner, UserRole::Operator));
        Gate::define('manage-stock', fn (User $user): bool => $user->hasRole(UserRole::Planner));
        Gate::define('import-data', fn (User $user): bool => $user->hasRole(UserRole::Planner));
        Gate::define('view-audit-log', fn (User $user): bool => false);
    }
}
