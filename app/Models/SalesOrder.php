<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Audit\Auditable;
use App\Domain\Sales\Enums\SalesOrderStatus;
use Database\Factories\SalesOrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property int $customer_id
 * @property SalesOrderStatus $status
 * @property Carbon $order_date
 * @property Carbon $due_date
 * @property float $total
 * @property string|null $notes
 * @property int|null $created_by
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $shipped_at
 * @property Carbon|null $cancelled_at
 * @property string|null $pdf_path
 * @property Carbon|null $pdf_generated_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Customer $customer
 * @property-read User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SalesOrderLine> $lines
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductionOrder> $productionOrders
 */
#[Fillable(['number', 'customer_id', 'status', 'order_date', 'due_date', 'total', 'notes', 'created_by', 'pdf_path', 'pdf_generated_at'])]
class SalesOrder extends Model
{
    /** @use HasFactory<SalesOrderFactory> */
    use Auditable, HasFactory;

    /**
     * @var list<string>
     */
    protected array $auditExclude = ['pdf_path', 'pdf_generated_at'];

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<SalesOrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SalesOrderLine::class);
    }

    /**
     * @return HasMany<ProductionOrder, $this>
     */
    public function productionOrders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    /**
     * Moves the order to a new status, enforcing the workflow and stamping the matching timestamp.
     */
    public function transitionTo(SalesOrderStatus $status): void
    {
        $this->status->assertCanTransitionTo($status, "Sales order {$this->number}");

        $this->status = $status;

        $timestamp = match ($status) {
            SalesOrderStatus::Confirmed => 'confirmed_at',
            SalesOrderStatus::Completed => 'completed_at',
            SalesOrderStatus::Shipped => 'shipped_at',
            SalesOrderStatus::Cancelled => 'cancelled_at',
            default => null,
        };

        if ($timestamp !== null) {
            $this->{$timestamp} = now();
        }

        $this->save();
    }

    public function isEditable(): bool
    {
        return $this->status === SalesOrderStatus::Draft;
    }

    public function isLate(): bool
    {
        return in_array($this->status, SalesOrderStatus::open(), true) && $this->due_date->lt(today());
    }

    public function hasCurrentPdf(): bool
    {
        return $this->pdf_path !== null && $this->pdf_generated_at !== null && $this->pdf_generated_at->gte($this->updated_at);
    }

    /**
     * @param  Builder<SalesOrder>  $query
     */
    public function scopeLate(Builder $query): void
    {
        $query->whereIn('status', SalesOrderStatus::open())->whereDate('due_date', '<', today());
    }

    /**
     * @param  Builder<SalesOrder>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, fn (Builder $query, string $term) => $query->where(fn (Builder $query) => $query
            ->whereContains('number', $term)
            ->orWhereHas('customer', fn (Builder $query) => $query->whereContains('name', $term))));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'order_date' => 'date',
            'due_date' => 'date',
            'total' => 'float',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'shipped_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'pdf_generated_at' => 'datetime',
        ];
    }
}
