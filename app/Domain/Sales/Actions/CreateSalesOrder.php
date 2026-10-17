<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Domain\Sales\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use App\Models\User;
use App\Support\DocumentNumber;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type OrderLineInput from SyncSalesOrderLines
 *
 * @phpstan-type SalesOrderInput array{customer_id: int, due_date: string, order_date?: string|null, notes?: string|null, lines: list<OrderLineInput>}
 */
class CreateSalesOrder
{
    public function __construct(
        private readonly DocumentNumber $documentNumber,
        private readonly SyncSalesOrderLines $syncLines,
    ) {}

    /**
     * @param  SalesOrderInput  $data
     */
    public function execute(array $data, ?User $user = null): SalesOrder
    {
        return DB::transaction(function () use ($data, $user): SalesOrder {
            $order = SalesOrder::create([
                'number' => $this->documentNumber->next('SO'),
                'customer_id' => $data['customer_id'],
                'status' => SalesOrderStatus::Draft,
                'order_date' => $data['order_date'] ?? today(),
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user?->id,
            ]);

            $this->syncLines->execute($order, $data['lines']);

            return $order->refresh();
        });
    }
}
