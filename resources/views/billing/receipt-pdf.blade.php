<!doctype html>
<html lang="en">
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
                <h1>Payment receipt</h1>
                <div class="muted">{{ $payment->receipt_number }}</div>
                <div style="margin-top:12px">{{ $payment->paid_at->format('F j, Y g:i A') }}</div>
            </td>
        </tr>
    </table>

    <table width="100%">
        <tr>
            <td width="55%" valign="top">
                <div class="muted">RECEIVED FROM</div>
                <strong>{{ $payment->document->customer_name }}</strong>
                @if ($payment->document->customer_email)<br>{{ $payment->document->customer_email }}@endif
                @if ($payment->document->customer_phone)<br>{{ $payment->document->customer_phone }}@endif
                @if ($payment->document->customer_address)<br>{!! nl2br(e($payment->document->customer_address)) !!}@endif
            </td>
            <td class="right" valign="top">
                <div class="muted">AMOUNT RECEIVED</div>
                <div class="amount">RM {{ number_format((float) $payment->amount, 2) }}</div>
            </td>
        </tr>
    </table>

    <table class="details">
        <tbody>
            <tr><th>Invoice</th><td>{{ $payment->document->number }}</td></tr>
            <tr><th>Payment method</th><td>{{ str_replace('_', ' ', ucfirst($payment->method)) }}</td></tr>
            <tr><th>Payment reference</th><td>{{ $payment->reference ?: '—' }}</td></tr>
            <tr><th>Payment date</th><td>{{ $payment->paid_at->format('F j, Y g:i A') }}</td></tr>
            <tr><th>Invoice total</th><td>RM {{ number_format((float) $payment->document->total, 2) }}</td></tr>
        </tbody>
    </table>

    <div class="footer muted">This receipt confirms the payment recorded against the invoice above. This is a computer-generated receipt.</div>
</body>
</html>
