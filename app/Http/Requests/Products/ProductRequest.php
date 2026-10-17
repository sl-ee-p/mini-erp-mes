<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use App\Domain\Inventory\Enums\MaterialType;
use App\Models\Material;
use App\Models\Product;
use App\Models\WorkCenter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product
            ? $this->user()?->can('update', $product) ?? false
            : $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|string|object>>
     */
    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'sku' => [
                'required', 'string', 'max:40',
                Rule::unique(Product::class)->ignore($product),
                Rule::unique(Material::class)->ignore($product?->material_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'is_active' => ['boolean'],

            'bom' => ['array'],
            'bom.*.material_id' => ['required', 'integer', 'distinct', Rule::exists(Material::class, 'id')->where('type', MaterialType::Raw->value)],
            'bom.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],

            'routing' => ['required', 'array', 'min:1'],
            'routing.*.work_center_id' => ['required', 'integer', Rule::exists(WorkCenter::class, 'id')],
            'routing.*.name' => ['required', 'string', 'max:100'],
            'routing.*.planned_minutes_per_unit' => ['required', 'numeric', 'gt:0', 'max:10000'],
            'routing.*.consumes_materials' => ['boolean'],
        ];
    }

    /**
     * The bill of materials is consumed once, so at most one routing step may be flagged.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $flagged = collect((array) $this->input('routing', []))
                    ->filter(fn ($step) => is_array($step) && filter_var($step['consumes_materials'] ?? false, FILTER_VALIDATE_BOOLEAN));

                if ($flagged->count() > 1) {
                    $validator->errors()->add('routing', 'Only one routing step can consume the bill of materials.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'bom.*.material_id' => 'material',
            'bom.*.quantity' => 'quantity',
            'routing.*.work_center_id' => 'work center',
            'routing.*.name' => 'step name',
            'routing.*.planned_minutes_per_unit' => 'planned minutes',
        ];
    }

    /**
     * Validated data normalised to the shape SaveProduct expects.
     *
     * @return array{sku: string, name: string, description: string|null, price: float, is_active: bool, bom: list<array{material_id: int, quantity: float}>, routing: list<array{work_center_id: int, name: string, planned_minutes_per_unit: float, consumes_materials: bool}>}
     */
    public function productData(): array
    {
        $data = $this->validated();

        return [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => (float) $data['price'],
            'is_active' => $this->boolean('is_active'),
            'bom' => array_values(array_map(fn (array $line): array => [
                'material_id' => (int) $line['material_id'],
                'quantity' => (float) $line['quantity'],
            ], $data['bom'] ?? [])),
            'routing' => array_values(array_map(fn (array $step): array => [
                'work_center_id' => (int) $step['work_center_id'],
                'name' => $step['name'],
                'planned_minutes_per_unit' => (float) $step['planned_minutes_per_unit'],
                'consumes_materials' => (bool) ($step['consumes_materials'] ?? false),
            ], $data['routing'])),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sku' => strtoupper((string) $this->input('sku'))]);
    }
}
