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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Material::class);

        $movements = StockMovement::query()
            ->with(['material', 'user', 'reference'])
            ->when($request->enum('type', StockMovementType::class), fn ($query, StockMovementType $type) => $query->where('type', $type))
            ->when($request->string('search')->toString(), fn ($query, string $term) => $query->whereHas('material', fn ($query) => $query->search($term)))
            ->when($request->date('from'), fn ($query, $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($request->date('to'), fn ($query, $to) => $query->where('created_at', '<=', $to->endOfDay()))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('stock-movements.index', [
            'movements' => $movements,
            'types' => StockMovementType::options(),
        ]);
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
