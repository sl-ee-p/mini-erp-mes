<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Sales\Actions\CreateSalesOrder;
use App\Domain\Sales\Actions\UpdateSalesOrder;
use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SalesOrders\SalesOrderRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', SalesOrder::class);

        $orders = SalesOrder::query()
            ->with('customer')
            ->withCount('lines')
            ->search($request->string('search')->toString())
            ->when($request->enum('status', SalesOrderStatus::class), fn ($query, SalesOrderStatus $status) => $query->where('status', $status))
            ->when($request->boolean('late'), fn ($query) => $query->late())
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('sales-orders.index', ['orders' => $orders, 'statuses' => SalesOrderStatus::options()]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', SalesOrder::class);

        return view('sales-orders.create', [
            ...$this->formOptions(),
            'order' => new SalesOrder([
                'customer_id' => $request->integer('customer_id') ?: null,
                'due_date' => today()->addWeeks(4),
            ]),
        ]);
    }

    public function store(SalesOrderRequest $request, CreateSalesOrder $createSalesOrder): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $order = $createSalesOrder->execute($request->orderData(), $user);

        return redirect()->route('sales-orders.show', $order)->with('success', "Sales order {$order->number} created as draft.");
    }

    public function show(SalesOrder $salesOrder): View
    {
        $this->authorize('view', $salesOrder);

        return view('sales-orders.show', [
            'order' => $salesOrder->load(['customer', 'creator', 'lines.product.material']),
        ]);
    }

    public function edit(SalesOrder $salesOrder): View|RedirectResponse
    {
        $this->authorize('update', $salesOrder);

        if (! $salesOrder->isEditable()) {
            return redirect()->route('sales-orders.show', $salesOrder)->with('error', 'Only draft orders can be edited.');
        }

        return view('sales-orders.edit', [...$this->formOptions(), 'order' => $salesOrder->load('lines')]);
    }

    public function update(SalesOrderRequest $request, SalesOrder $salesOrder, UpdateSalesOrder $updateSalesOrder): RedirectResponse
    {
        $updateSalesOrder->execute($salesOrder, $request->orderData());

        return redirect()->route('sales-orders.show', $salesOrder)->with('success', 'Sales order updated.');
    }

    /**
     * @return array{customers: array<int, string>, products: array<int, array{id: int, label: string, price: float}>}
     */
    private function formOptions(): array
    {
        return [
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'code'])
                ->mapWithKeys(fn (Customer $customer) => [$customer->id => "{$customer->name} ({$customer->code})"])
                ->all(),
            'products' => Product::query()->active()->orderBy('name')->get(['id', 'sku', 'name', 'price'])
                ->map(fn (Product $product) => ['id' => $product->id, 'label' => "{$product->name} ({$product->sku})", 'price' => $product->price])
                ->values()
                ->all(),
        ];
    }
}
