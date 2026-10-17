<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Catalog\Actions\SaveProduct;
use App\Domain\Inventory\Enums\MaterialType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ProductRequest;
use App\Models\Material;
use App\Models\Product;
use App\Models\WorkCenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::query()
            ->with('material')
            ->withCount(['bomItems', 'routingSteps'])
            ->withSum('routingSteps', 'planned_minutes_per_unit')
            ->search($request->string('search')->toString())
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('products.index', ['products' => $products]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('products.create', [...$this->formOptions(), 'product' => new Product(['is_active' => true])]);
    }

    public function store(ProductRequest $request, SaveProduct $saveProduct): RedirectResponse
    {
        $product = $saveProduct->execute($request->productData());

        return redirect()->route('products.show', $product)->with('success', 'Product created.');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        return view('products.show', [
            'product' => $product->load(['material', 'bomItems.material', 'routingSteps.workCenter']),
        ]);
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('products.edit', [
            ...$this->formOptions(),
            'product' => $product->load(['bomItems', 'routingSteps']),
        ]);
    }

    public function update(ProductRequest $request, Product $product, SaveProduct $saveProduct): RedirectResponse
    {
        $saveProduct->execute($request->productData(), $product);

        return redirect()->route('products.show', $product)->with('success', 'Product updated.');
    }

    /**
     * @return array{materials: array<int, string>, workCenters: array<int, string>}
     */
    private function formOptions(): array
    {
        return [
            'materials' => Material::query()
                ->where('type', MaterialType::Raw)
                ->orderBy('name')
                ->get(['id', 'sku', 'name', 'unit'])
                ->mapWithKeys(fn (Material $material) => [$material->id => "{$material->name} ({$material->sku}, {$material->unit->label()})"])
                ->all(),
            'workCenters' => WorkCenter::query()->orderBy('id')->pluck('name', 'id')->all(),
        ];
    }
}
