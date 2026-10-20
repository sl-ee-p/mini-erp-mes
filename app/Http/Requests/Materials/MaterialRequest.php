<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Domain\Inventory\Enums\MaterialType;
use App\Domain\Inventory\Enums\UnitOfMeasure;
use App\Models\Material;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    /**
     * Changing the unit of a material with a ledger or a bill of materials would silently turn 64 m² into 64 pieces.
     * (Finished goods are always counted in pieces; their unit is ignored on update.)
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $material = $this->route('material');

                if (! $material instanceof Material || $material->type !== MaterialType::Raw || $this->input('unit') === $material->unit->value) {
                    return;
                }

                if ($material->isInUse()) {
                    $validator->errors()->add('unit', 'The unit cannot be changed once stock has been booked or the material is used in a bill of materials.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['sku' => strtoupper((string) $this->input('sku'))]);
    }
}
