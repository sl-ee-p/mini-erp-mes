<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customers\CustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()
            ->search($request->string('search')->toString())
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', ['customers' => $customers]);
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create', ['customer' => new Customer(['country' => 'IT'])]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $totals = $customer->salesOrders()
            ->selectRaw('count(*) as orders')
            ->selectRaw('coalesce(sum(case when status = ? then total else 0 end), 0) as shipped', [SalesOrderStatus::Shipped->value])
            ->selectRaw('coalesce(sum(case when status in (?, ?, ?) then total else 0 end), 0) as open', array_map(fn (SalesOrderStatus $status) => $status->value, SalesOrderStatus::open()))
            ->toBase()
            ->first();

        return view('customers.show', [
            'customer' => $customer,
            'orders' => $customer->salesOrders()->latest('id')->limit(10)->get(),
            'stats' => [
                'orders' => (int) ($totals->orders ?? 0),
                'shipped' => (float) ($totals->shipped ?? 0),
                'open' => (float) ($totals->open ?? 0),
            ],
        ]);
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', ['customer' => $customer]);
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        if ($customer->salesOrders()->exists()) {
            return back()->with('error', 'Customers with sales orders cannot be deleted.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted.');
    }
}
