<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Reporting\DashboardSummary;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardSummary $summary): View
    {
        return view('dashboard.index', [
            'openOrders' => $summary->openOrders(),
            'draftCount' => $summary->draftCount(),
            'lateOrders' => $summary->lateOrders(),
            'lateOrderCount' => $summary->lateOrderCount(),
            'readyToShip' => $summary->readyToShip(),
            'readyToShipCount' => $summary->readyToShipCount(),
            'production' => $summary->production(),
            'scrapRate' => $summary->scrapRate(),
            'workCenterLoad' => $summary->workCenterLoad(),
            'shortages' => $summary->materialShortages(),
            'lowStock' => $summary->lowStock(),
            'lowStockCount' => $summary->lowStockCount(),
            'recentlyCompleted' => $summary->recentlyCompleted(),
        ]);
    }
}
