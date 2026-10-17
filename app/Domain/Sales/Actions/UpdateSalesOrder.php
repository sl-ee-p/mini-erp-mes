<?php

declare(strict_types=1);

namespace App\Domain\Sales\Actions;

use App\Domain\Sales\Exceptions\SalesOrderException;
use App\Models\SalesOrder;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type SalesOrderInput from CreateSalesOrder
 */
class UpdateSalesOrder
{
    public function __construct(private readonly SyncSalesOrderLines $syncLines) {}

    /**
     * @param  SalesOrderInput  $data
     *
     * @throws SalesOrderException when the order is no longer a draft
     */
    public function execute(SalesOrder $order, array $data): SalesOrder
    {
        if (! $order->isEditable()) {
            throw SalesOrderException::notEditable($order);
        }

        return DB::transaction(function () use ($order, $data): SalesOrder {
            $order->update([
                'customer_id' => $data['customer_id'],
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLines->execute($order, $data['lines']);

            return $order->refresh();
        });
    }
}
