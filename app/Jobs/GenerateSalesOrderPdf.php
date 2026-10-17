<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\SalesOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

/**
 * Renders the order confirmation PDF off the request cycle and stores it on the private disk.
 */
class GenerateSalesOrderPdf implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 60;

    public function __construct(public readonly SalesOrder $salesOrder) {}

    public function uniqueId(): string
    {
        return (string) $this->salesOrder->id;
    }

    public function handle(): void
    {
        $order = $this->salesOrder->fresh(['customer', 'lines.product', 'creator']);

        if ($order === null) {
            return;
        }

        $path = "sales-orders/{$order->number}.pdf";

        Storage::disk('local')->put($path, Pdf::loadView('pdf.sales-order', ['order' => $order])->output());

        // Not a business change: keep updated_at and the audit trail untouched.
        $order->timestamps = false;
        $order->forceFill(['pdf_path' => $path, 'pdf_generated_at' => now()])->saveQuietly();
    }
}
