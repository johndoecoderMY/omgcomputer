<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        body{font-family:dejavusans,sans-serif;color:#202b36;font-size:10pt}
        h1{font-size:22pt;margin:0;color:#145b73}
        .muted{color:#687782}
        .top{border-bottom:2px solid #145b73;padding-bottom:14px;margin-bottom:22px}
        .right{text-align:right}
        .brand-logo{width:145px;height:auto;max-height:60px;object-fit:contain}
        .details{width:100%;border-collapse:collapse;margin-top:24px}
        .details th{background:#edf3f5;text-align:left}
        .details th,.details td{padding:10px;border:1px solid #d9e1e5}
        .amount{font-size:17pt;font-weight:bold;color:#145b73}
        .footer{margin-top:30px;padding-top:12px;border-top:1px solid #d9e1e5}
    </style>
</head>
<body>
    <table width="100%" class="top">
        <tr>
            <td width="55%">
                @if ($logoData)<img class="brand-logo" src="data:image/png;base64,{{ $logoData }}" alt="OMG Computer"><br>@else<strong>OMG Computer</strong><br>@endif
                <strong>{{ $companyProfile->legal_name ?: 'OMG Computer' }}</strong>
                @if ($companyProfile->address)<br>{!! nl2br(e($companyProfile->address)) !!}@endif
                @if ($companyProfile->phone)<br>{{ $companyProfile->phone }}@endif
                @if ($companyProfile->email)<br>{{ $companyProfile->email }}@endif
                @if ($companyProfile->website_url)<br>{{ $companyProfile->website_url }}@endif
                @if ($companyProfile->ssm_registration_number)<br>SSM registration no.: {{ $companyProfile->ssm_registration_number }}@endif
                @if ($companyProfile->tax_identification_number)<br>TIN: {{ $companyProfile->tax_identification_number }}@endif
                @if ($companyProfile->sst_registration_number)<br>SST registration no.: {{ $companyProfile->sst_registration_number }}@endif
            </td>
            <td class="right">
                <h1>{{ __('billing.pdf.payment_receipt') }}</h1>
                <div class="muted">{{ $payment->receipt_number }}</div>
                <div style="margin-top:12px">{{ $payment->paid_at->locale(app()->getLocale())->translatedFormat('F j, Y g:i A') }}</div>
            </td>
        </tr>
    </table>

    <table width="100%">
        <tr>
            <td width="55%" valign="top">
                <div class="muted">{{ __('billing.pdf.received_from') }}</div>
                <strong>{{ $payment->document->customer_name }}</strong>
                @if ($payment->document->customer_email)<br>{{ $payment->document->customer_email }}@endif
                @if ($payment->document->customer_phone)<br>{{ $payment->document->customer_phone }}@endif
                @if ($payment->document->customer_address)<br>{!! nl2br(e($payment->document->customer_address)) !!}@endif
            </td>
            <td class="right" valign="top">
                <div class="muted">{{ __('billing.pdf.amount_received') }}</div>
                <div class="amount">RM {{ number_format((float) $payment->amount, 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="details">
        <tbody>
            <tr><th>{{ __('billing.pdf.invoice') }}</th><td>{{ $payment->document->number }}</td></tr>
            <tr><th>{{ __('billing.pdf.payment_method') }}</th><td>{{ __('billing.pdf.payment_method_'.$payment->method) }}</td></tr>
            <tr><th>{{ __('billing.pdf.payment_reference') }}</th><td>{{ $payment->reference ?: '—' }}</td></tr>
            <tr><th>{{ __('billing.pdf.payment_date') }}</th><td>{{ $payment->paid_at->locale(app()->getLocale())->translatedFormat('F j, Y g:i A') }}</td></tr>
            <tr><th>{{ __('billing.pdf.invoice_subtotal') }}</th><td>RM {{ number_format((float) $payment->document->subtotal, 2) }}</td></tr>
            @if ((float) $payment->document->discount_amount > 0)
                <tr><th>{{ __('billing.pdf.discount') }}</th><td>- RM {{ number_format((float) $payment->document->discount_amount, 2) }}</td></tr>
            @endif
            @if ((float) $payment->document->tax_amount > 0)
                <tr><th>{{ __('billing.pdf.tax_rate', ['rate' => number_format((float) $payment->document->tax_rate, 2)]) }}</th><td>RM {{ number_format((float) $payment->document->tax_amount, 2) }}</td></tr>
            @endif
            <tr><th>{{ __('billing.pdf.invoice_total') }}</th><td>RM {{ number_format((float) $payment->document->total, 2) }}</td></tr>
        </tbody>
    </table>

    <div class="footer muted">{{ __('billing.pdf.computer_generated_receipt') }}</div>
</body>
</html>
