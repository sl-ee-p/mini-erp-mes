<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderStep;
use App\Models\User;
use App\Models\WorkCenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tablet screen for operators: the work queue of a work center and the step they are working on.
 */
class ShopFloorController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $workCenterId = $request->integer('work_center') ?: null;

        $actionable = ProductionOrderStep::query()->actionable();

        $queue = (clone $actionable)
            ->with(['productionOrder.product', 'productionOrder.salesOrder.customer', 'workCenter', 'openTimeLog.user', 'operator'])
            ->when($workCenterId, fn ($query, int $id) => $query->where('work_center_id', $id))
            ->orderByRaw("case status when 'running' then 0 when 'paused' then 1 else 2 end")
            ->orderBy(ProductionOrder::select('due_date')->whereColumn('production_orders.id', 'production_order_steps.production_order_id'))
            ->orderBy('sequence')
            ->limit(30)
            ->get();

        $counts = (clone $actionable)->selectRaw('work_center_id, count(*) as aggregate')->groupBy('work_center_id')->pluck('aggregate', 'work_center_id');

        $myStep = ProductionOrderStep::query()
            ->with(['productionOrder.product', 'openTimeLog'])
            ->whereHas('openTimeLog', fn ($query) => $query->where('user_id', $user->id))
            ->first();

        return view('shop-floor.index', [
            'queue' => $queue,
            'myStep' => $myStep,
            'workCenters' => WorkCenter::query()->orderBy('id')->get(),
            'counts' => $counts,
            'selectedWorkCenter' => $workCenterId,
        ]);
    }
}
