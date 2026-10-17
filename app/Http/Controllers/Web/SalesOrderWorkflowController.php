<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Sales\Actions\CancelSalesOrder;
use App\Domain\Sales\Actions\ConfirmSalesOrder;
use App\Domain\Sales\Actions\ShipSalesOrder;
use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Status transitions of a sales order. Each endpoint delegates to the matching domain action.
 */
class SalesOrderWorkflowController extends Controller
{
    public function confirm(SalesOrder $salesOrder, ConfirmSalesOrder $confirmSalesOrder): RedirectResponse
    {
        $this->authorize('confirm', $salesOrder);

        $confirmSalesOrder->execute($salesOrder);

        return back()->with('success', "Sales order {$salesOrder->number} confirmed.");
    }

    public function cancel(SalesOrder $salesOrder, CancelSalesOrder $cancelSalesOrder): RedirectResponse
    {
        $this->authorize('cancel', $salesOrder);

        $cancelSalesOrder->execute($salesOrder);

        return back()->with('success', "Sales order {$salesOrder->number} cancelled.");
    }

    public function ship(Request $request, SalesOrder $salesOrder, ShipSalesOrder $shipSalesOrder): RedirectResponse
    {
        $this->authorize('ship', $salesOrder);

        /** @var User $user */
        $user = $request->user();

        $shipSalesOrder->execute($salesOrder, $user);

        return back()->with('success', "Sales order {$salesOrder->number} shipped; finished goods issued from stock.");
    }
}
