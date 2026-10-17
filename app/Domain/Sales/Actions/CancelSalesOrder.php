<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

class CancelSalesOrder
{
    public function execute(SalesOrder $order): SalesOrder
    {
        return DB::transaction(function () use ($order): SalesOrder {
            $order = SalesOrder::query()->lockForUpdate()->findOrFail($order->id);

            $order->transitionTo(SalesOrderStatus::Cancelled);

            return $order;
        });
    }
}
