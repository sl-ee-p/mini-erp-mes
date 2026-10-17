<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSalesOrderPdf;
use App\Models\SalesOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesOrderPdfController extends Controller
{
    public function store(SalesOrder $salesOrder): RedirectResponse
    {
        $this->authorize('view', $salesOrder);

        GenerateSalesOrderPdf::dispatch($salesOrder);

        return back()->with('success', 'The PDF is being generated. Refresh in a moment to download it.');
    }

    public function show(SalesOrder $salesOrder): StreamedResponse
    {
        $this->authorize('view', $salesOrder);

        abort_unless($salesOrder->pdf_path && Storage::disk('local')->exists($salesOrder->pdf_path), 404);

        return Storage::disk('local')->download($salesOrder->pdf_path, "{$salesOrder->number}.pdf");
    }
}
