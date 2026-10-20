<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Inventory\Enums\MaterialType;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Domain\Inventory\Queries\MaterialRequirements;
use App\Http\Controllers\Controller;
use App\Http\Requests\Materials\MaterialRequest;
use App\Models\Material;
use App\Support\CsvExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function index(Request $request, MaterialRequirements $requirements): View
    {
        $this->authorize('viewAny', Material::class);

        $materials = $this->filtered($request, $requirements)
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('materials.index', [
            'materials' => $materials,
            'allocated' => $requirements->allocated($materials->getCollection()->modelKeys()),
            'types' => MaterialType::options(),
        ]);
    }

    /**
     * The stock list with the current filters, for a spreadsheet or a stock-take sheet.
     */
    public function export(Request $request, MaterialRequirements $requirements): StreamedResponse
    {
        $this->authorize('viewAny', Material::class);

        $allocated = $requirements->allocated();

        return CsvExport::download(
            'materials-'.now()->format('Y-m-d').'.csv',
            $this->filtered($request, $requirements),
            ['SKU', 'Name', 'Type', 'Unit', 'On hand', 'Allocated', 'Available', 'Minimum', 'Unit cost (EUR)', 'Stock value (EUR)'],
            fn (Material $material): array => [
                $material->sku,
                $material->name,
                $material->type->label(),
                $material->unit->label(),
                $material->stock_qty,
                $allocated->get($material->id, 0.0),
                round($material->stock_qty - $allocated->get($material->id, 0.0), 3),
                $material->min_stock,
                $material->unit_cost,
                round($material->stock_qty * $material->unit_cost, 2),
            ],
        );
    }

    /**
     * @return Builder<Material>
     */
    private function filtered(Request $request, MaterialRequirements $requirements): Builder
    {
        return Material::query()
            ->search($request->string('search')->toString())
            ->when($request->enum('type', MaterialType::class), fn ($query, MaterialType $type) => $query->where('type', $type))
            ->when($request->boolean('low_stock'), fn ($query) => $query->belowMinimum())
            ->when($request->boolean('shortage'), fn ($query) => $query->whereKey($requirements->shortages()->map(fn (array $row): int => $row['material']->id)->all()));
    }

    public function create(): View
    {
        $this->authorize('create', Material::class);

        return view('materials.create', ['material' => new Material, 'units' => UnitOfMeasure::options()]);
    }

    public function store(MaterialRequest $request): RedirectResponse
    {
        $material = Material::create([...$request->validated(), 'type' => MaterialType::Raw]);

        return redirect()->route('materials.show', $material)->with('success', 'Material created.');
    }

    public function show(Material $material, MaterialRequirements $requirements): View
    {
        $this->authorize('view', $material);

        return view('materials.show', [
            'material' => $material->load('product'),
            'allocated' => $requirements->allocated([$material->id])->get($material->id, 0.0),
            'demand' => $requirements->openDemand($material),
            'movements' => $material->stockMovements()->withReference()->with('user')->latest('id')->paginate(20),
            'movementTypes' => StockMovementType::options(),
        ]);
    }

    public function edit(Material $material): View
    {
        $this->authorize('update', $material);

        return view('materials.edit', ['material' => $material, 'units' => UnitOfMeasure::options()]);
    }

    public function update(MaterialRequest $request, Material $material): RedirectResponse
    {
        $data = $request->validated();

        if ($material->type === MaterialType::Finished) {
            // Finished goods take their identity from the product and are always counted in pieces.
            $data = array_intersect_key($data, array_flip(['min_stock', 'unit_cost']));
        }

        $material->update($data);

        return redirect()->route('materials.show', $material)->with('success', 'Material updated.');
    }
}
