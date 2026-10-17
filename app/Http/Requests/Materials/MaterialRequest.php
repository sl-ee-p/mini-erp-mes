<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Models\Material;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Raw materials are created here; finished goods are created with their product.
 */
class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('material');

        return $material instanceof Material
            ? $this->user()?->can('update', $material) ?? false
            : $this->user()?->can('create', Material::class) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|string|object>>
     */
    public function rules(): array
    {
        return [
            'sku' => ['required', 'string', 'max:40', Rule::unique(Material::class)->ignore($this->route('material'))],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', Rule::enum(UnitOfMeasure::class)],
            'min_stock' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sku' => strtoupper((string) $this->input('sku'))]);
    }
}
