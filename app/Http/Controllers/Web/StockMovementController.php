<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Inventory\Actions\AdjustStockToCount;
use App\Domain\Inventory\Actions\RecordStockMovement;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Exceptions\InsufficientStock;
use App\Http\Controllers\Controller;
use App\Http\Requests\Materials\StoreStockMovementRequest;
use App\Models\Material;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\CsvExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Material::class);

        $movements = $this->filtered($request)
            ->withReference()
            ->with(['material', 'user'])
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('stock-movements.index', [
            'movements' => $movements,
            'types' => StockMovementType::options(),
        ]);
    }

    /**
     * The ledger with the current filters, oldest first, as accountants and auditors read it.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Material::class);

        return CsvExport::download(
            'stock-movements-'.now()->format('Y-m-d').'.csv',
            $this->filtered($request)->withReference()->with(['material', 'user']),
            ['Date', 'SKU', 'Material', 'Type', 'Quantity', 'Unit', 'Balance after', 'Reason', 'Reference', 'By'],
            fn (StockMovement $movement): array => [
                $movement->created_at->format('Y-m-d H:i:s'),
                $movement->material->sku,
                $movement->material->name,
                $movement->type->label(),
                $movement->quantity,
                $movement->material->unit->label(),
                $movement->balance_after,
                $movement->reason,
                $movement->referenceLabel(),
                $movement->user->name ?? 'System',
            ],
        );
    }

    /**
     * @return Builder<StockMovement>
     */
    private function filtered(Request $request): Builder
    {
        return StockMovement::query()
            ->when($request->enum('type', StockMovementType::class), fn ($query, StockMovementType $type) => $query->where('type', $type))
            ->when($request->string('search')->toString(), fn ($query, string $term) => $query->whereHas('material', fn ($query) => $query->search($term)))
            ->when($this->dateFilter($request, 'from'), fn ($query, Carbon $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($this->dateFilter($request, 'to'), fn ($query, Carbon $to) => $query->where('created_at', '<=', $to->endOfDay()));
    }

    /**
     * A Y-m-d date from the query string, or null when it is missing or malformed (a hand-edited URL should not
     * crash the ledger).
     */
    private function dateFilter(Request $request, string $key): ?Carbon
    {
        $value = $request->string($key)->toString();

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        $date = Carbon::createFromFormat('!Y-m-d', $value);

        return $date !== null && $date->format('Y-m-d') === $value ? $date : null;
    }

    public function store(
        StoreStockMovementRequest $request,
        Material $material,
        RecordStockMovement $recordStockMovement,
        AdjustStockToCount $adjustStockToCount,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $quantity = (float) $request->validated('quantity');
        $reason = $request->validated('reason');

        try {
            if ($request->movementType() === StockMovementType::Adjustment) {
                $movement = $adjustStockToCount->execute($material, $quantity, (string) $reason, $user);

                if ($movement === null) {
                    return back()->with('success', 'Count matches the system balance; nothing to adjust.');
                }
            } else {
                $recordStockMovement->execute($material, $request->movementType(), $quantity, $reason, user: $user);
            }
        } catch (InsufficientStock $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return back()->with('success', 'Stock movement recorded.');
    }
}
