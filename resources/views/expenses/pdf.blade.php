<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $expense->name }} - Expense details</title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #222; }
        h1 { font-size: 20pt; margin-bottom: 4pt; }
        h2 { font-size: 14pt; margin: 20pt 0 8pt; border-bottom: 1px solid #888; padding-bottom: 4pt; }
        p { margin: 4pt 0; }
        table { width: 100%; border-collapse: collapse; margin: 6pt 0 12pt; }
        th, td { border: 1px solid #bbb; text-align: left; padding: 5pt; vertical-align: top; }
        th { background: #f0f0f0; }
        .muted { color: #666; }
        .photo { max-width: 100%; max-height: 220mm; }
        .photo-page { page-break-inside: avoid; margin: 12pt 0; }
    </style>
</head>
<body>
    <h1>{{ $expense->name }}</h1>
    <p class="muted">{{ \App\Models\BusinessExpense::CATEGORIES[$expense->category] }} · Generated {{ now()->format('F j, Y') }}</p>

    <h2>Expense or renewal details</h2>
    <table>
        <tbody>
            <tr><th>Provider or payee</th><td>{{ $expense->vendor ?: '—' }}</td></tr>
            <tr><th>Frequency</th><td>{{ \App\Models\BusinessExpense::FREQUENCIES[$expense->frequency] }}</td></tr>
            <tr><th>Next due</th><td>{{ $expense->due_date?->format('F j, Y') ?? '—' }}</td></tr>
            <tr><th>Amount per cycle</th><td>RM {{ number_format((float) $expense->amount, 2) }}</td></tr>
            <tr><th>Paid this cycle</th><td>RM {{ number_format((float) $expense->cycle_paid_amount, 2) }}</td></tr>
            <tr><th>Remaining</th><td>RM {{ number_format($expense->remaining_amount, 2) }}</td></tr>
            <tr><th>Status</th><td>{{ ucfirst($expense->payment_status) }}</td></tr>
        </tbody>
    </table>
    @if ($expense->notes)
        <h2>Notes</h2>
        <p style="white-space: pre-wrap">{{ $expense->notes }}</p>
    @endif

    <h2>Payment history</h2>
    @if ($expense->payments->isEmpty())
        <p class="muted">No payments have been recorded.</p>
    @else
        <table>
            <thead><tr><th>Paid</th><th>Due date</th><th>Amount</th><th>Reference</th><th>Recorded by</th><th>Notes</th></tr></thead>
            <tbody>
                @foreach ($expense->payments as $payment)
                    <tr>
                        <td>{{ $payment->paid_at->format('M j, Y') }}</td>
                        <td>{{ $payment->period_due_date?->format('M j, Y') ?? '—' }}</td>
                        <td>RM {{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ $payment->reference ?: '—' }}</td>
                        <td>{{ $payment->user?->name ?? '—' }}</td>
                        <td>{{ $payment->notes ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($photos->isNotEmpty())
        <h2>Photos</h2>
        @foreach ($photos as $photo)
            <div class="photo-page">
                <img class="photo" src="{{ $photo['src'] }}" alt="{{ $photo['caption'] }}">
                <p class="muted">{{ $photo['caption'] }}</p>
            </div>
        @endforeach
    @endif
    @if ($documents->isNotEmpty())
        <h2>Attached PDF files</h2>
        <ul>
            @foreach ($documents as $document)
                <li>{{ $document->caption ?: $document->original_name }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
