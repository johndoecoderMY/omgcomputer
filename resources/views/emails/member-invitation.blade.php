@extends('emails.layout')

@php
    $emailTitle = 'Set up your OMG Computer account';
    $emailKicker = 'ACCOUNT INVITATION';
@endphp

@section('email-content')
<tr>
    <td style="padding:36px;">
        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">Hello {{ $recipientName }},</p>
        <h1 style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">Welcome to OMG Computer</h1>
        <p style="margin:0 0 24px;font-size:16px;line-height:26px;color:#475569;">Your membership has been created. Verify your email address and choose your own account password using the secure button below.</p>
        <a href="{{ $invitationUrl }}" style="display:inline-block;padding:14px 22px;background:#2d7ff9;color:#fff;text-align:center;text-decoration:none;border-radius:8px;font-size:15px;font-weight:bold;">Verify email and set password</a>
        <p style="margin:24px 0 0;font-size:13px;line-height:20px;color:#64748b;">This link expires and can only be used once. No password is included in this email.</p>
    </td>
</tr>
@endsection

@section('email-footer')
@include('emails.partials.footer')
@endsection
