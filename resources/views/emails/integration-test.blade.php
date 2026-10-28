@extends('emails.layout')

@php
    $emailTitle = 'Email delivery test';
    $emailKicker = 'EMAIL TEST';
@endphp

@section('email-content')
<tr>
    <td style="padding:36px;">
        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">Hello {{ $recipientName }},</p>
        <h1 style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">Email delivery is working</h1>
        <p style="margin:0;font-size:16px;line-height:26px;color:#475569;">This is a test email from OMG Computer. Your configured email delivery service is connected and ready to send messages.</p>
    </td>
</tr>
@endsection

@section('email-footer')
@include('emails.partials.footer')
@endsection
