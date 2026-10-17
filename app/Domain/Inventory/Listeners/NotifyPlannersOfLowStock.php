<?php

declare(strict_types=1);

namespace App\Domain\Inventory\Listeners;

use App\Domain\Inventory\Events\StockBelowMinimum;
use App\Domain\Inventory\Notifications\LowStockNotification;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class NotifyPlannersOfLowStock
{
    public function handle(StockBelowMinimum $event): void
    {
        $recipients = User::query()
            ->active()
            ->whereIn('role', [UserRole::Admin, UserRole::Planner])
            ->get();

        Notification::send($recipients, new LowStockNotification($event->material));
    }
}
