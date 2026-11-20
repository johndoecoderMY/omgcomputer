@extends('emails.layout')

@php
    $emailTitle = __('billing.email.invoice_subject', ['number' => $document->number]);
    $emailKicker = __('billing.pdf.invoice');
    $emailReference = $document->number;
@endphp

@section('email-content')
<tr>
    <td class="email-content-padding" style="padding:36px 36px 28px;">
        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">{{ __('billing.email.hello', ['name' => $document->customer_name]) }}</p>
        <h1 class="email-heading" style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">{{ __('billing.email.invoice_title') }}</h1>
        <p style="margin:0;font-size:16px;line-height:26px;color:#475569;">{{ __('billing.email.invoice_intro') }}</p>
    </td>
</tr>
<tr>
    <td class="email-section-padding" style="padding:0 36px 28px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
            <tr>
                <td class="email-column-stack" width="50%" style="padding:18px;border-right:1px solid #e2e8f0;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">{{ __('billing.email.invoice_number') }}</div>
                    <div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $document->number }}</div>
                </td>
                <td class="email-column-stack" width="50%" style="padding:18px;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">{{ __('billing.email.due_date') }}</div>
                    <div style="margin-top:5px;font-weight:bold;color:#12263f;">
                        {{ $document->due_date?->locale(app()->getLocale())->translatedFormat('d M Y') ?? __('billing.pdf.on_receipt') }}
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding:18px;border-top:1px solid #e2e8f0;background:#fff;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">{{ __('billing.email.invoice_total') }}</div>
                    <div style="margin-top:3px;font-size:30px;line-height:38px;font-weight:bold;color:#12263f;">RM {{ number_format((float) $document->total, 2) }}</div>
                    <div style="margin-top:12px;font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">{{ __('billing.email.balance_due') }}</div>
                    <div style="margin-top:3px;font-size:20px;line-height:28px;font-weight:bold;color:#12263f;">RM {{ number_format((float) $document->balance, 2) }}</div>
                </td>
            </tr>
        </table>
    </td>
</tr>
@endsection

@section('email-footer')
<p style="margin:0 0 8px;font-size:14px;line-height:22px;color:#475569;">{{ __('billing.email.invoice_pdf_note') }}</p>
<p style="margin:0;font-size:14px;line-height:22px;color:#475569;">{{ __('billing.email.reply_for_help') }}</p>
<p style="margin:18px 0 0;font-size:14px;color:#12263f;"><strong>OMG Computer</strong><br><span style="color:#64748b;">{{ __('billing.email.signature_title') }}</span></p>
@endsection
