<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One search box for the whole portal. A query that is exactly a document number or SKU (typed or scanned) opens
 * that record straight away; anything else lists the matches the user is allowed to see, grouped by kind.
 */
class SearchController extends Controller
{
    public const MIN_LENGTH = 2;

    public const PER_GROUP = 8;

    public function __invoke(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $term = trim($request->string('q')->toString());

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return view('search.index', ['term' => $term, 'groups' => [], 'total' => 0]);
        }

        if ($exact = $this->exactMatch($term, $user)) {
            return redirect($exact);
        }

        $groups = array_filter([
            'Sales orders' => $user->can('viewAny', SalesOrder::class)
                ? SalesOrder::query()->with('customer')->search($term)->latest('id')->limit(self::PER_GROUP)->get()
                    ->map(fn (SalesOrder $order): array => [
                        'url' => route('sales-orders.show', $order),
                        'title' => $order->number,
                        'meta' => $order->customer->name,
                        'badge' => [$order->status->label(), $order->status->color()],
                    ])
                : null,
            'Production orders' => ProductionOrder::query()->with(['product', 'salesOrder'])
                ->where(fn (Builder $query) => $query->where('number', 'like', "%{$term}%")->orWhereHas('product', fn (Builder $query) => $query->search($term)))
                ->latest('id')->limit(self::PER_GROUP)->get()
                ->map(fn (ProductionOrder $order): array => [
                    'url' => route('production-orders.show', $order),
                    'title' => $order->number,
                    'meta' => "{$order->quantity} × {$order->product->name} · {$order->salesOrder->number}",
                    'badge' => [$order->status->label(), $order->status->color()],
                ]),
            'Customers' => $user->can('viewAny', Customer::class)
                ? Customer::query()->search($term)->orderBy('name')->limit(self::PER_GROUP)->get()
                    ->map(fn (Customer $customer): array => [
                        'url' => route('customers.show', $customer),
                        'title' => $customer->name,
                        'meta' => trim("{$customer->code} · {$customer->city}", ' ·'),
                        'badge' => null,
                    ])
                : null,
            'Products' => Product::query()->search($term)->orderBy('name')->limit(self::PER_GROUP)->get()
                ->map(fn (Product $product): array => [
                    'url' => route('products.show', $product),
                    'title' => $product->name,
                    'meta' => $product->sku,
                    'badge' => $product->is_active ? null : ['Inactive', 'stone'],
                ]),
            'Materials' => Material::query()->search($term)->orderBy('name')->limit(self::PER_GROUP)->get()
                ->map(fn (Material $material): array => [
                    'url' => route('materials.show', $material),
                    'title' => $material->name,
                    'meta' => $material->sku,
                    'badge' => $material->isBelowMinimum() ? ['Low stock', 'red'] : null,
                ]),
        ], fn ($results) => $results !== null && $results->isNotEmpty());

        return view('search.index', [
            'term' => $term,
            'groups' => $groups,
            'total' => array_sum(array_map(fn ($results) => $results->count(), $groups)),
        ]);
    }

    /**
     * The record whose number or SKU is exactly the search term, if the user may open it.
     */
    private function exactMatch(string $term, User $user): ?string
    {
        if ($user->can('viewAny', SalesOrder::class) && $order = SalesOrder::query()->where('number', $term)->first()) {
            return route('sales-orders.show', $order);
        }

        if ($productionOrder = ProductionOrder::query()->where('number', $term)->first()) {
            return route('production-orders.show', $productionOrder);
        }

        if ($product = Product::query()->where('sku', $term)->first()) {
            return route('products.show', $product);
        }

        if ($material = Material::query()->where('sku', $term)->first()) {
            return route('materials.show', $material);
        }

        return null;
    }
}
