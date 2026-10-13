<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; color: #263238; font-size: 10pt; }
        h1 { color: #f34303; font-size: 23pt; margin-bottom: 4px; }
        h2 { font-size: 12pt; margin-bottom: 6px; }
        .muted { color: #667085; }
        .row { width: 100%; }
        .col { display: inline-block; width: 48%; vertical-align: top; }
        table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        th { background: #f2f4f7; text-align: left; }
        th, td { padding: 9px 8px; border-bottom: 1px solid #e4e7ec; }
        .right { text-align: right; }
        .totals { width: 48%; margin-left: auto; }
        .grand { font-size: 13pt; font-weight: bold; color: #f34303; }
        .notes { margin-top: 28px; padding: 12px; background: #f8fafc; }
    </style>
</head>
<body>
    <div class="row">
        <div class="col">
            <h1>OMG Computer</h1>
            <div class="muted">PC &amp; laptop repair and support</div>
        </div>
        <div class="col right">
            <h1>{{ strtoupper($document->type) }}</h1>
            <strong>{{ $document->document_number }}</strong><br>
            <span class="muted">Status: {{ ucfirst($document->status) }}</span>
        </div>
    </div>
    <div class="row" style="margin-top: 28px;">
        <div class="col">
            <h2>Bill to</h2>
            <strong>{{ $document->customer_name }}</strong><br>
            @if($document->customer_email){{ $document->customer_email }}<br>@endif
            @if($document->customer_phone){{ $document->customer_phone }}@endif
        </div>
        <div class="col right">
            <strong>Issued:</strong> {{ $document->issued_at->format('M j, Y') }}<br>
            <strong>Due:</strong> {{ $document->due_date?->format('M j, Y') ?? '—' }}
        </div>
    </div>

    <table>
        <thead><tr><th>Description</th><th class="right">Qty</th><th class="right">Unit price</th><th class="right">Amount</th></tr></thead>
        <tbody>
            @foreach($document->items as $item)
                <tr><td>{{ $item->description }}</td><td class="right">{{ $item->quantity }}</td><td class="right">RM{{ number_format((float) $item->unit_price, 2) }}</td><td class="right">RM{{ number_format((float) $item->line_total, 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>
    <table class="totals">
        <tbody>
            <tr><td>Subtotal</td><td class="right">RM{{ number_format((float) $document->subtotal, 2) }}</td></tr>
            <tr><td>Tax ({{ number_format((float) $document->tax_rate, 2) }}%)</td><td class="right">RM{{ number_format((float) $document->tax_amount, 2) }}</td></tr>
            <tr class="grand"><td>Total</td><td class="right">RM{{ number_format((float) $document->total, 2) }}</td></tr>
            @if($document->type === 'invoice')
                <tr><td>Paid</td><td class="right">RM{{ number_format($document->paid_total, 2) }}</td></tr>
                <tr><td>Balance due</td><td class="right">RM{{ number_format($document->balance_due, 2) }}</td></tr>
            @endif
        </tbody>
    </table>
    @if($document->notes)
        <div class="notes"><strong>Notes / terms</strong><br>{{ $document->notes }}</div>
    @endif
    <p class="muted" style="margin-top: 36px;">Thank you for choosing OMG Computer.</p>
</body>
</html>
