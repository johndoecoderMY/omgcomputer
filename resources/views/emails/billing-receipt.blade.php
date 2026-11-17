@extends('emails.layout')

@php
    $emailTitle = 'Payment receipt '.$payment->receipt_number;
    $emailKicker = 'PAYMENT RECEIPT';
    $emailReference = $payment->receipt_number;
@endphp

@section('email-content')
<tr>
    <td class="email-content-padding" style="padding:36px 36px 28px;">
        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">Hello {{ $payment->document->customer_name }},</p>
        <h1 class="email-heading" style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">Your payment is confirmed</h1>
        <p style="margin:0;font-size:16px;line-height:26px;color:#475569;">Thank you. Your receipt is attached as a PDF for your records.</p>
    </td>
</tr>
<tr>
    <td class="email-section-padding" style="padding:0 36px 34px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
            <tr>
                <td width="50%" style="padding:18px;border-right:1px solid #e2e8f0;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">Invoice</div>
                    <div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $payment->document->number }}</div>
                </td>
                <td width="50%" style="padding:18px;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">Payment date</div>
                    <div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $payment->paid_at->format('d M Y, g:i A') }}</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding:18px;border-top:1px solid #e2e8f0;background:#fff;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">Amount received</div>
                    <div style="margin-top:3px;font-size:30px;line-height:38px;font-weight:bold;color:#12263f;">RM {{ number_format((float) $payment->amount, 2) }}</div>
                </td>
            </tr>
        </table>
    </td>
</tr>
@endsection

@section('email-footer')
<p style="margin:0 0 8px;font-size:14px;line-height:22px;color:#475569;">The attached PDF is your payment receipt. You can save or print it for your records.</p>
<p style="margin:0;font-size:14px;line-height:22px;color:#475569;">Questions? Reply to this email and our team will assist you.</p>
<p style="margin:18px 0 0;font-size:14px;color:#12263f;"><strong>OMG Computer</strong><br><span style="color:#64748b;">Sales &amp; Customer Support</span></p>
@endsection
