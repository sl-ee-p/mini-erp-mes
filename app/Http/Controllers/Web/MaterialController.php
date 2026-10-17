<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Inventory\Enums\MaterialType;
use App\Domain\Inventory\Enums\StockMovementType;
use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Http\Controllers\Controller;
use App\Http\Requests\Materials\MaterialRequest;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MaterialController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Material::class);

        $materials = Material::query()
            ->search($request->string('search')->toString())
            ->when($request->enum('type', MaterialType::class), fn ($query, MaterialType $type) => $query->where('type', $type))
            ->when($request->boolean('low_stock'), fn ($query) => $query->belowMinimum())
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('materials.index', [
            'materials' => $materials,
            'types' => MaterialType::options(),
        ]);
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

    public function show(Material $material): View
    {
        $this->authorize('view', $material);

        return view('materials.show', [
            'material' => $material->load('product'),
            'movements' => $material->stockMovements()->with(['user', 'reference'])->latest('id')->paginate(20),
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
