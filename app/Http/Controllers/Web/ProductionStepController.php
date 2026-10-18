<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Production\Actions\CompleteStep;
use App\Domain\Production\Actions\PauseStep;
use App\Domain\Production\Actions\StartStep;
use App\Http\Controllers\Controller;
use App\Http\Requests\Production\CompleteStepRequest;
use App\Models\ProductionOrderStep;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Shop-floor step actions. Business rules live in the actions; violations are rendered as flash messages.
 */
class ProductionStepController extends Controller
{
    public function start(Request $request, ProductionOrderStep $step, StartStep $startStep): RedirectResponse
    {
        $this->authorize('use-shop-floor');

        $startStep->execute($step, $this->operator($request));

        return back()->with('success', "Started “{$step->name}”.");
    }

    public function pause(Request $request, ProductionOrderStep $step, PauseStep $pauseStep): RedirectResponse
    {
        $this->authorize('use-shop-floor');

        $pauseStep->execute($step, $this->operator($request));

        return back()->with('success', "Paused “{$step->name}”.");
    }

    public function complete(CompleteStepRequest $request, ProductionOrderStep $step, CompleteStep $completeStep): RedirectResponse
    {
        $completed = $completeStep->execute($step, $this->operator($request), $request->scrapQuantity());

        return back()->with('success', "Completed “{$completed->name}”: {$completed->good_qty} good, {$completed->scrap_qty} scrap.");
    }

    private function operator(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
