@extends('emails.layout')

@php
    $emailTitle = 'Repair billing verification';
    $emailKicker = 'BILLING VERIFICATION';
    $emailReference = $ticket->ticket_number;
@endphp

@section('email-content')
<tr>
    <td class="email-content-padding" style="padding:36px 36px 28px;">
        <h1 class="email-heading" style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">Verify your repair billing access</h1>
        <p style="margin:0 0 18px;font-size:16px;line-height:26px;color:#475569;">Use this one-time code to view the quotations and invoices linked to repair ticket {{ $ticket->ticket_number }}:</p>
        <p style="margin:0 0 18px;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;text-align:center;font-size:28px;letter-spacing:8px;font-weight:bold;color:#12263f;">{{ $code }}</p>
        <p style="margin:0;font-size:14px;line-height:22px;color:#64748b;">This code expires in 10 minutes. If you did not request it, you can ignore this email.</p>
    </td>
</tr>
@endsection

@section('email-footer')
<p style="margin:0;font-size:14px;line-height:22px;color:#475569;">For your security, do not share this verification code with anyone.</p>
<p style="margin:18px 0 0;font-size:14px;color:#12263f;"><strong>OMG Computer</strong><br><span style="color:#64748b;">Customer Support</span></p>
@endsection
