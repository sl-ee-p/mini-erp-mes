<?php

declare(strict_types=1);

namespace App\Domain\Production\Actions;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Enums\StepStatus;
use App\Domain\Production\Exceptions\ProductionException;
use App\Models\BomItem;
use App\Models\ProductionOrder;
use App\Models\RoutingStep;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Support\DocumentNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Releases one production order per sales order line, snapshotting the current routing and bill of materials.
 */
class GenerateProductionOrders
{
    public function __construct(private readonly DocumentNumber $documentNumber) {}

    /**
     * @return Collection<int, ProductionOrder>
     */
    public function execute(SalesOrder $order): Collection
    {
        return DB::transaction(function () use ($order): Collection {
            $lines = $order->lines()->with(['product.routingSteps', 'product.bomItems'])->get();

            return $lines->map(fn (SalesOrderLine $line): ProductionOrder => $this->release($order, $line));
        });
    }

    private function release(SalesOrder $order, SalesOrderLine $line): ProductionOrder
    {
        $product = $line->product;

        if ($product->routingSteps->isEmpty()) {
            throw ProductionException::productHasNoRouting($product);
        }

        $productionOrder = ProductionOrder::create([
            'number' => $this->documentNumber->next('PO'),
            'sales_order_id' => $order->id,
            'sales_order_line_id' => $line->id,
            'product_id' => $product->id,
            'quantity' => $line->quantity,
            'status' => ProductionOrderStatus::Planned,
            'due_date' => $order->due_date,
            'planned_minutes' => round($product->routingSteps->sum('planned_minutes_per_unit') * $line->quantity, 2),
        ]);

        $productionOrder->steps()->createMany($product->routingSteps->map(fn (RoutingStep $step): array => [
            'routing_step_id' => $step->id,
            'work_center_id' => $step->work_center_id,
            'sequence' => $step->sequence,
            'name' => $step->name,
            'consumes_materials' => $step->consumes_materials,
            'status' => StepStatus::Pending,
            'planned_minutes' => round($step->planned_minutes_per_unit * $line->quantity, 2),
        ])->all());

        $productionOrder->materials()->createMany($product->bomItems->map(fn (BomItem $item): array => [
            'material_id' => $item->material_id,
            'quantity_per_unit' => $item->quantity,
            'required_qty' => round($item->quantity * $line->quantity, 3),
        ])->all());

        return $productionOrder;
    }
}
