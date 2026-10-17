<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Inventory\Events\StockBelowMinimum;
use App\Domain\Inventory\Listeners\NotifyPlannersOfLowStock;
use App\Domain\Sales\Events\SalesOrderConfirmed;
use App\Domain\Sales\Events\SalesOrderShipped;
use App\Domain\Sales\Listeners\QueueSalesOrderPdf;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
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

        Relation::enforceMorphMap([
            'user' => User::class,
            'customer' => Customer::class,
            'material' => Material::class,
            'product' => Product::class,
            'sales_order' => SalesOrder::class,
        ]);

        $this->defineGates();
        $this->registerDomainListeners();
    }

    /**
     * Domain listeners live outside app/Listeners, so they are wired explicitly in one place.
     */
    private function registerDomainListeners(): void
    {
        Event::listen(StockBelowMinimum::class, NotifyPlannersOfLowStock::class);
        Event::listen([SalesOrderConfirmed::class, SalesOrderShipped::class], QueueSalesOrderPdf::class);
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
