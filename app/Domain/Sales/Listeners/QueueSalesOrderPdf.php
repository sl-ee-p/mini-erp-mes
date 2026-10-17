<?php

declare(strict_types=1);

namespace App\Domain\Sales\Listeners;

use App\Domain\Sales\Events\SalesOrderConfirmed;
use App\Domain\Sales\Events\SalesOrderShipped;
use App\Jobs\GenerateSalesOrderPdf;

/**
 * The customer-facing document is refreshed whenever the order reaches a milestone.
 */
class QueueSalesOrderPdf
{
    public function handle(SalesOrderConfirmed|SalesOrderShipped $event): void
    {
        GenerateSalesOrderPdf::dispatch($event->salesOrder);
    }
}
