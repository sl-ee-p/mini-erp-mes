<?php

declare(strict_types=1);

namespace App\Domain\Sales\Exceptions;

use App\Models\SalesOrder;
use DomainException;

class SalesOrderException extends DomainException
{
    public static function notEditable(SalesOrder $order): self
    {
        return new self("Sales order {$order->number} is {$order->status->label()} and can no longer be edited.");
    }

    public static function hasNoLines(SalesOrder $order): self
    {
        return new self("Sales order {$order->number} has no lines and cannot be confirmed.");
    }

    /**
     * @param  list<string>  $productNames
     */
    public static function containsInactiveProducts(SalesOrder $order, array $productNames): self
    {
        return new self("Sales order {$order->number} contains discontinued products: ".implode(', ', $productNames).'.');
    }
}
