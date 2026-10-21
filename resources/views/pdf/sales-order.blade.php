@use('App\Support\Format')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $order->number }}</title>
    <style>
        @page { margin: 32px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #292524; }
        h1 { font-size: 20px; margin: 0; color: #964925; }
        .muted { color: #78716c; }
        .header { width: 100%; margin-bottom: 28px; }
        .header td { vertical-align: top; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9px; background: #f5f5f4; font-size: 10px; text-transform: uppercase; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.lines th { text-align: left; font-size: 9px; text-transform: uppercase; color: #78716c; border-bottom: 1px solid #d6d3d1; padding: 6px 4px; }
        table.lines td { padding: 8px 4px; border-bottom: 1px solid #f5f5f4; }
        .right { text-align: right; }
        .total td { font-weight: bold; font-size: 13px; border-bottom: none; padding-top: 12px; }
        .footer { position: fixed; bottom: -10px; left: 0; right: 0; font-size: 9px; color: #a8a29e; text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <h1>Oakline Woodworks</h1>
                <div class="muted">Via dei Falegnami 12 · 24100 Bergamo (BG) · Italy<br>orders@oakline.test · VAT IT00000000000</div>
            </td>
            <td class="right">
                <div style="font-size: 16px; font-weight: bold;">Order confirmation</div>
                <div>{{ $order->number }}</div>
                <div class="badge">{{ $order->status->label() }}</div>
            </td>
        </tr>
    </table>

    <table class="header">
        <tr>
            <td style="width: 55%;">
                <div class="muted">Customer</div>
                <strong>{{ $order->customer->name }}</strong><br>
                {{ $order->customer->address }}<br>
                {{ $order->customer->city }} ({{ $order->customer->country }})<br>
                @if ($order->customer->vat_number) VAT {{ $order->customer->vat_number }} @endif
            </td>
            <td>
                <table>
                    <tr><td class="muted">Order date</td><td>{{ $order->order_date->format('d M Y') }}</td></tr>
                    <tr><td class="muted">Requested delivery</td><td>{{ $order->due_date->format('d M Y') }}</td></tr>
                    <tr><td class="muted">Customer code</td><td>{{ $order->customer->code }}</td></tr>
                    @if ($order->shipped_at)
                        <tr><td class="muted">Shipped</td><td>{{ $order->shipped_at->format('d M Y') }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th class="right">Qty</th>
                <th class="right">Unit price</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->lines as $line)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $line->product->name }}</strong><br><span class="muted">{{ $line->product->sku }}</span></td>
                    <td class="right">{{ $line->quantity }}</td>
                    <td class="right">{{ Format::money($line->unit_price) }}</td>
                    <td class="right">{{ Format::money($line->line_total) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="4" class="right">Total (excl. VAT)</td>
                <td class="right">{{ Format::money($order->total) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($order->notes)
        <p style="margin-top: 24px;"><span class="muted">Notes:</span> {{ $order->notes }}</p>
    @endif

    <div class="footer">Oakline Woodworks is a fictional company. Document generated {{ \App\Support\Format::dateTime(now()) }}.</div>
</body>
</html>
