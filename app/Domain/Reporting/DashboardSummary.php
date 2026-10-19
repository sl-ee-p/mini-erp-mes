<?php

declare(strict_types=1);

namespace App\Domain\Reporting;

use App\Domain\Inventory\Queries\MaterialRequirements;
use App\Domain\Production\Enums\ProductionOrderStatus;
use App\Domain\Production\Enums\StepStatus;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Models\Material;
use App\Models\ProductionOrder;
use App\Models\ProductionOrderStep;
use App\Models\SalesOrder;
use App\Models\WorkCenter;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The figures and work lists shown on the dashboard. Each method is one small query, so the page stays fast as
 * the order book grows.
 */
class DashboardSummary
{
    public const LIST_LIMIT = 5;

    public const SCRAP_WINDOW_DAYS = 30;

    public function __construct(private readonly MaterialRequirements $materialRequirements) {}

    /**
     * @return array{count: int, value: float}
     */
    public function openOrders(): array
    {
        $totals = SalesOrder::query()
            ->whereIn('status', SalesOrderStatus::open())
            ->selectRaw('count(*) as aggregate, coalesce(sum(total), 0) as value')
            ->first();

        return ['count' => (int) $totals?->getAttribute('aggregate'), 'value' => round((float) $totals?->getAttribute('value'), 2)];
    }

    public function draftCount(): int
    {
        return SalesOrder::query()->where('status', SalesOrderStatus::Draft)->count();
    }

    /**
     * @return EloquentCollection<int, SalesOrder>
     */
    public function lateOrders(): EloquentCollection
    {
        return SalesOrder::query()->late()->with('customer')->orderBy('due_date')->limit(self::LIST_LIMIT)->get();
    }

    public function lateOrderCount(): int
    {
        return SalesOrder::query()->late()->count();
    }

    /**
     * Orders whose production is finished and that are waiting to be shipped, oldest due date first.
     *
     * @return EloquentCollection<int, SalesOrder>
     */
    public function readyToShip(): EloquentCollection
    {
        return SalesOrder::query()->where('status', SalesOrderStatus::Completed)->with('customer')->orderBy('due_date')->limit(self::LIST_LIMIT)->get();
    }

    public function readyToShipCount(): int
    {
        return SalesOrder::query()->where('status', SalesOrderStatus::Completed)->count();
    }

    /**
     * @return array{planned: int, in_progress: int, running_steps: int}
     */
    public function production(): array
    {
        $byStatus = ProductionOrder::query()->active()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');

        return [
            'planned' => (int) ($byStatus[ProductionOrderStatus::Planned->value] ?? 0),
            'in_progress' => (int) ($byStatus[ProductionOrderStatus::InProgress->value] ?? 0),
            'running_steps' => ProductionOrderStep::query()->where('status', StepStatus::Running)->count(),
        ];
    }

    /**
     * Share of units scrapped by production orders completed in the last 30 days, or null when nothing was completed.
     */
    public function scrapRate(): ?float
    {
        $totals = ProductionOrder::query()
            ->where('status', ProductionOrderStatus::Completed)
            ->where('completed_at', '>=', now()->subDays(self::SCRAP_WINDOW_DAYS))
            ->selectRaw('coalesce(sum(good_qty), 0) as good, coalesce(sum(scrap_qty), 0) as scrap')
            ->first();

        $good = (int) $totals?->getAttribute('good');
        $scrap = (int) $totals?->getAttribute('scrap');

        return $good + $scrap === 0 ? null : round($scrap / ($good + $scrap) * 100, 1);
    }

    /**
     * Remaining planned work per work center over every open step of active production orders.
     *
     * @return Collection<int, array{work_center: WorkCenter, remaining_minutes: float, open_steps: int, running: int}>
     */
    public function workCenterLoad(): Collection
    {
        $load = ProductionOrderStep::query()
            ->where('status', '!=', StepStatus::Completed)
            ->whereHas('productionOrder', fn ($query) => $query->active())
            ->selectRaw('work_center_id')
            ->selectRaw('sum(case when planned_minutes * 60 > actual_seconds then planned_minutes - actual_seconds / 60.0 else 0 end) as remaining_minutes')
            ->selectRaw('count(*) as open_steps')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as running', [StepStatus::Running->value])
            ->groupBy('work_center_id')
            ->get()
            ->keyBy('work_center_id');

        return WorkCenter::query()->orderBy('id')->get()->map(fn (WorkCenter $workCenter): array => [
            'work_center' => $workCenter,
            'remaining_minutes' => round((float) $load->get($workCenter->id)?->getAttribute('remaining_minutes'), 1),
            'open_steps' => (int) $load->get($workCenter->id)?->getAttribute('open_steps'),
            'running' => (int) $load->get($workCenter->id)?->getAttribute('running'),
        ]);
    }

    /**
     * @return Collection<int, array{material: Material, allocated: float, shortage: float}>
     */
    public function materialShortages(): Collection
    {
        return $this->materialRequirements->shortages();
    }

    /**
     * @return EloquentCollection<int, Material>
     */
    public function lowStock(): EloquentCollection
    {
        return Material::query()->belowMinimum()->orderByRaw('stock_qty * 1.0 / min_stock')->limit(self::LIST_LIMIT)->get();
    }

    public function lowStockCount(): int
    {
        return Material::query()->belowMinimum()->count();
    }

    /**
     * @return EloquentCollection<int, ProductionOrder>
     */
    public function recentlyCompleted(): EloquentCollection
    {
        return ProductionOrder::query()
            ->where('status', ProductionOrderStatus::Completed)
            ->with('product')
            ->latest('completed_at')
            ->limit(self::LIST_LIMIT)
            ->get();
    }
}
