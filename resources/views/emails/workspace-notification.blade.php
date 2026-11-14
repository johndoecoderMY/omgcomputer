@extends('emails.layout')

@php
    $emailTitle = $notificationTitle;
    $emailKicker = 'WORKSPACE UPDATE';
@endphp

@section('email-content')
<tr>
    <td class="email-content-padding" style="padding:36px;">
        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">Hello {{ $recipientName }},</p>
        <h1 class="email-heading" style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">{{ $notificationTitle }}</h1>
        <p style="margin:0;font-size:16px;line-height:26px;color:#475569;">{{ $notificationMessage }}</p>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-top:24px;">
            <tr>
                <td align="center" bgcolor="#f34303" style="border-radius:8px;">
                    <a class="email-button" href="{{ $actionUrl }}" style="display:inline-block;padding:13px 22px;background:#f34303;border:1px solid #f34303;border-radius:8px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:bold;">
                        Open workspace
                    </a>
                </td>
            </tr>
        </table>
    </td>
</tr>
@endsection

@section('email-footer')
@include('emails.partials.footer')
<p style="margin:14px 0 0;font-size:12px;line-height:18px;color:#64748b;">You can also review this notification in your workspace.</p>
@endsection
