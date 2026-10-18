<?php

declare(strict_types=1);

namespace App\Domain\Production\Exceptions;

use App\Models\Product;
use App\Models\ProductionOrderStep;
use App\Models\SalesOrder;
use DomainException;

class ProductionException extends DomainException
{
    public static function productHasNoRouting(Product $product): self
    {
        return new self("{$product->name} has no routing and cannot be produced.");
    }

    public static function previousStepNotCompleted(ProductionOrderStep $step, ProductionOrderStep $previous): self
    {
        return new self("“{$step->name}” cannot start before “{$previous->name}” is completed.");
    }

    public static function orderNotActive(ProductionOrderStep $step): self
    {
        $order = $step->productionOrder;

        return new self("{$order->number} is ".strtolower($order->status->label()).'; its steps can no longer be worked on.');
    }

    public static function operatorBusy(ProductionOrderStep $runningStep): self
    {
        return new self("You are already working on “{$runningStep->name}” ({$runningStep->productionOrder->number}). Pause or complete it first.");
    }

    public static function stepRunByAnotherOperator(ProductionOrderStep $step): self
    {
        $operator = $step->openTimeLog?->user->name ?? 'another operator';

        return new self("“{$step->name}” is being worked on by {$operator}; only they or a planner can pause or complete it.");
    }

    public static function scrapExceedsInput(ProductionOrderStep $step, int $scrap): self
    {
        return new self("Scrap ({$scrap}) cannot exceed the {$step->input_qty} units processed at “{$step->name}”.");
    }

    public static function productionAlreadyStarted(SalesOrder $order): self
    {
        return new self("Production for {$order->number} has already started; the order can no longer be cancelled.");
    }
}
