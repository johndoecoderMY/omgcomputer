@extends('emails.layout')

@php
    $emailTitle = __('billing.email.receipt_subject', ['number' => $payment->receipt_number]);
    $emailKicker = __('billing.pdf.payment_receipt');
    $emailReference = $payment->receipt_number;
@endphp

@section('email-content')
<tr>
    <td class="email-content-padding" style="padding:36px 36px 28px;">
        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">{{ __('billing.email.hello', ['name' => $payment->document->customer_name]) }}</p>
        <h1 class="email-heading" style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">{{ __('billing.email.receipt_title') }}</h1>
        <p style="margin:0;font-size:16px;line-height:26px;color:#475569;">{{ __('billing.email.receipt_intro') }}</p>
    </td>
</tr>
<tr>
    <td class="email-section-padding" style="padding:0 36px 34px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
            <tr>
                <td width="50%" style="padding:18px;border-right:1px solid #e2e8f0;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">{{ __('billing.email.invoice') }}</div>
                    <div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $payment->document->number }}</div>
                </td>
                <td width="50%" style="padding:18px;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">{{ __('billing.email.payment_date') }}</div>
                    <div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $payment->paid_at->locale(app()->getLocale())->translatedFormat('d M Y, g:i A') }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding:18px;border-top:1px solid #e2e8f0;background:#fff;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">{{ __('billing.email.amount_received') }}</div>
                    <div style="margin-top:3px;font-size:30px;line-height:38px;font-weight:bold;color:#12263f;">RM {{ number_format((float) $payment->amount, 2) }}</div>
                </td>
            </tr>
        </table>
        <h2 style="margin:24px 0 10px;font-size:18px;color:#12263f;">{{ __('billing.pdf.items') }}</h2>
        <table role="table" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;font-size:14px;">
            <thead>
                <tr style="background:#f1f5f9;color:#475569;text-align:left;">
                    <th scope="col" style="padding:10px 8px;">{{ __('billing.pdf.description') }}</th>
                    <th scope="col" style="padding:10px 8px;text-align:right;">{{ __('billing.pdf.quantity') }}</th>
                    <th scope="col" style="padding:10px 8px;text-align:right;">{{ __('billing.pdf.unit_price') }}</th>
                    <th scope="col" style="padding:10px 8px;text-align:right;">{{ __('billing.pdf.amount') }}</th>
                    <th scope="col" style="padding:10px 8px;text-align:right;">{{ __('billing.pdf.item_discount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payment->document->items as $item)
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <td style="padding:10px 8px;color:#12263f;">{{ $item->description }}</td>
                        <td style="padding:10px 8px;text-align:right;color:#12263f;">{{ $item->quantity }}</td>
                        <td style="padding:10px 8px;text-align:right;color:#12263f;">RM {{ number_format((float) $item->unit_price, 2) }}</td>
                        <td style="padding:10px 8px;text-align:right;color:#12263f;">RM {{ number_format((float) $item->line_total, 2) }}</td>
                        <td style="padding:10px 8px;text-align:right;color:#12263f;">{{ (float) $item->discount_amount > 0 ? '- RM '.number_format((float) $item->discount_amount, 2) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top:14px;font-size:14px;">
            <tr>
                <td style="padding:5px 8px;color:#475569;">{{ __('billing.pdf.subtotal') }}</td>
                <td style="padding:5px 8px;text-align:right;color:#12263f;">RM {{ number_format((float) $payment->document->subtotal, 2) }}</td>
            </tr>
            @if ($payment->document->items->sum('discount_amount') > 0)
                <tr>
                    <td style="padding:5px 8px;color:#475569;">{{ __('billing.pdf.item_discount') }}</td>
                    <td style="padding:5px 8px;text-align:right;color:#12263f;">- RM {{ number_format($payment->document->items->sum('discount_amount'), 2) }}</td>
                </tr>
            @endif
            @if ((float) $payment->document->discount_amount > 0)
                <tr>
                    <td style="padding:5px 8px;color:#475569;">{{ __('billing.pdf.discount') }}</td>
                    <td style="padding:5px 8px;text-align:right;color:#12263f;">- RM {{ number_format((float) $payment->document->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if ((float) $payment->document->tax_amount > 0)
                <tr>
                    <td style="padding:5px 8px;color:#475569;">{{ __('billing.pdf.tax_rate', ['rate' => number_format((float) $payment->document->tax_rate, 2)]) }}</td>
                    <td style="padding:5px 8px;text-align:right;color:#12263f;">RM {{ number_format((float) $payment->document->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td style="padding:9px 8px;border-top:1px solid #cbd5e1;font-weight:bold;color:#12263f;">{{ __('billing.pdf.invoice_total') }}</td>
                <td style="padding:9px 8px;border-top:1px solid #cbd5e1;text-align:right;font-weight:bold;color:#12263f;">RM {{ number_format((float) $payment->document->total, 2) }}</td>
            </tr>
        </table>
    </td>
</tr>
@endsection

@section('email-footer')
<p style="margin:0 0 8px;font-size:14px;line-height:22px;color:#475569;">{{ __('billing.email.receipt_pdf_note') }}</p>
<p style="margin:0;font-size:14px;line-height:22px;color:#475569;">{{ __('billing.email.reply_for_help') }}</p>
<p style="margin:18px 0 0;font-size:14px;color:#12263f;"><strong>OMG Computer</strong><br><span style="color:#64748b;">{{ __('billing.email.signature_title') }}</span></p>
@endsection
