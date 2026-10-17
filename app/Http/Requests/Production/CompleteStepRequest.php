<?php

declare(strict_types=1);

namespace App\Http\Requests\Production;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('use-shop-floor') ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'scrap_qty' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    public function scrapQuantity(): int
    {
        return (int) $this->validated('scrap_qty', 0);
    }
}
