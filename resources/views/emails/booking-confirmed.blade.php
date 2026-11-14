@extends('emails.layout')

@php
    $emailTitle = 'Booking confirmed';
    $emailKicker = 'BOOKING CONFIRMED';
    $emailReference = $booking->reference;
    $appointmentTime = $booking->starts_at->copy()->setTimezone('Asia/Kuala_Lumpur')->format('j M Y, g:i A');
@endphp

@section('email-content')
<tr>
    <td class="email-content-padding" style="padding:36px 36px 28px;">
        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">Hello {{ $booking->customer_name }},</p>
        <h1 class="email-heading" style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">Your booking is confirmed</h1>
        <p style="margin:0;font-size:16px;line-height:26px;color:#475569;">We’ve confirmed your appointment with OMG Computer. Here are the details:</p>
    </td>
</tr>
<tr>
    <td style="padding:0 36px 30px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
            <tr>
                <td style="padding:18px;border-bottom:1px solid #e2e8f0;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">Service</div>
                    <div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $booking->service_name }}</div>
                </td>
            </tr>
            <tr>
                <td style="padding:18px;border-bottom:1px solid #e2e8f0;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">Appointment</div>
                    <div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $appointmentTime }} (Malaysia time)</div>
                </td>
            </tr>
            <tr>
                <td style="padding:18px;">
                    <div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">Booking reference</div>
                    <div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $booking->reference }}</div>
                </td>
            </tr>
        </table>
    </td>
</tr>
@endsection

@section('email-footer')
<p style="margin:0;font-size:14px;line-height:22px;color:#475569;">If you have questions or need to make a change, please contact OMG Computer and include your booking reference.</p>
<p style="margin:18px 0 0;font-size:14px;color:#12263f;"><strong>OMG Computer</strong><br><span style="color:#64748b;">Customer Support</span></p>
@endsection
