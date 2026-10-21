<?php

declare(strict_types=1);

namespace App\Http\Requests\Materials;

use App\Domain\Inventory\Enums\StockMovementType;
use App\Models\Material;
use App\Support\Format;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Manual stock booking: goods receipt, manual issue, or physical count.
 */
class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-stock') ?? false;
    }

    /**
     * For adjustments, `quantity` is the counted quantity on hand; otherwise it is the amount moved.
     *
     * @return array<string, array<int, ValidationRule|string|object>>
     */
    public function rules(): array
    {
        $isAdjustment = $this->input('type') === StockMovementType::Adjustment->value;

        return [
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', 'numeric', $isAdjustment ? 'min:0' : 'gt:0', 'max:'.Material::MAX_STOCK],
            'reason' => [Rule::requiredIf($this->input('type') !== StockMovementType::In->value), 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $quantity = $this->input('quantity');

                if ($this->material()->unit->isDiscrete() && is_numeric($quantity) && floor((float) $quantity) != (float) $quantity) {
                    $validator->errors()->add('quantity', 'This material is counted in whole pieces.');
                }

                if (
                    $this->input('type') === StockMovementType::In->value
                    && is_numeric($quantity)
                    && ! $validator->errors()->has('quantity')
                    && round($this->material()->stock_qty + (float) $quantity, 3) > Material::MAX_STOCK
                ) {
                    $validator->errors()->add('quantity', 'The receipt would push the stock above the maximum of '.Format::quantity(Material::MAX_STOCK).'.');
                }
            },
        ];
    }

    public function movementType(): StockMovementType
    {
        return StockMovementType::from($this->string('type')->toString());
    }

    public function material(): Material
    {
        /** @var Material */
        return $this->route('material');
    }
}
