<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ProductionOrder::class);

        $productionOrders = ProductionOrder::query()
            ->with(['product', 'salesOrder', 'steps:id,production_order_id,status,actual_seconds'])
            ->when($request->string('search')->toString(), fn ($query, string $term) => $query->where(fn ($query) => $query
                ->whereContains('number', $term)
                ->orWhereHas('product', fn ($query) => $query->search($term))
                ->orWhereHas('salesOrder', fn ($query) => $query->whereContains('number', $term))))
            ->when($request->enum('status', ProductionOrderStatus::class), fn ($query, ProductionOrderStatus $status) => $query->where('status', $status))
            ->when($request->boolean('late'), fn ($query) => $query->late())
            ->orderByRaw('case when status in (?, ?) then 0 else 1 end', [ProductionOrderStatus::InProgress->value, ProductionOrderStatus::Planned->value])
            ->orderBy('due_date')
            ->paginate(25)
            ->withQueryString();

        return view('production-orders.index', [
            'productionOrders' => $productionOrders,
            'statuses' => ProductionOrderStatus::options(),
        ]);
    }

    public function show(ProductionOrder $productionOrder): View
    {
        $this->authorize('view', $productionOrder);

        $productionOrder->load([
            'product',
            'salesOrder.customer',
            'steps.workCenter',
            'steps.operator',
            'steps.openTimeLog',
            'steps.timeLogs.user',
            'materials.material',
        ]);

        return view('production-orders.show', ['productionOrder' => $productionOrder]);
    }
}
