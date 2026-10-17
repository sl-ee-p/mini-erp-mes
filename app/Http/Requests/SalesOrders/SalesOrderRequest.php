<?php

declare(strict_types=1);

namespace App\Http\Requests\SalesOrders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('sales_order');

        return $order instanceof SalesOrder
            ? $this->user()?->can('update', $order) ?? false
            : $this->user()?->can('create', SalesOrder::class) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|string|object>>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists(Customer::class, 'id')],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.product_id' => ['required', 'integer', 'distinct', Rule::exists(Product::class, 'id')->where('is_active', true)],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'lines.*.product_id' => 'product',
            'lines.*.quantity' => 'quantity',
            'lines.*.unit_price' => 'unit price',
        ];
    }

    /**
     * @return array{customer_id: int, due_date: string, notes: string|null, lines: list<array{product_id: int, quantity: int, unit_price: float|null}>}
     */
    public function orderData(): array
    {
        $data = $this->validated();

        return [
            'customer_id' => (int) $data['customer_id'],
            'due_date' => $data['due_date'],
            'notes' => $data['notes'] ?? null,
            'lines' => array_values(array_map(fn (array $line): array => [
                'product_id' => (int) $line['product_id'],
                'quantity' => (int) $line['quantity'],
                'unit_price' => isset($line['unit_price']) && $line['unit_price'] !== '' ? (float) $line['unit_price'] : null,
            ], $data['lines'])),
        ];
    }
}
