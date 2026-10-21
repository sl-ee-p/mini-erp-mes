<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Audit\Auditable;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $vat_number
 * @property string|null $address
 * @property string|null $city
 * @property string $country
 * @property string|null $notes
 */
#[Fillable(['code', 'name', 'email', 'phone', 'vat_number', 'address', 'city', 'country', 'notes'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use Auditable, HasFactory;

    /**
     * @return HasMany<SalesOrder, $this>
     */
    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    /**
     * @param  Builder<Customer>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $query, string $term) => $query->where(fn (Builder $query) => $query
            ->whereContains('name', $term)
            ->orWhereContains('code', $term)
            ->orWhereContains('email', $term)
            ->orWhereContains('city', $term)));
    }
}
