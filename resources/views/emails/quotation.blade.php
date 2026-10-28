@extends('emails.layout')

@php
    $emailTitle = 'Quotation '.$document->number;
    $emailKicker = 'QUOTATION';
    $emailReference = $document->number;
    $acceptUrl = \Illuminate\Support\Facades\URL::signedRoute('billing.quotations.respond', ['document' => $document, 'status' => 'accepted']);
    $rejectUrl = \Illuminate\Support\Facades\URL::signedRoute('billing.quotations.respond', ['document' => $document, 'status' => 'rejected']);
    $issueDate = $document->issue_date ? \Illuminate\Support\Carbon::parse($document->issue_date)->format('d M Y') : now()->format('d M Y');
@endphp

@section('email-content')
<tr>
    <td style="padding:36px 36px 28px;">
        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">Hello {{ $document->customer_name }},</p>
        <h1 style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">Your quotation is ready</h1>
        <p style="margin:0;font-size:16px;line-height:26px;color:#475569;">We've prepared the quotation for your review. You can respond securely below or review the attached PDF.</p>
    </td>
</tr>
<tr>
    <td style="padding:0 36px 28px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
            <tr>
                <td width="50%" style="padding:18px;border-right:1px solid #e2e8f0;"><div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">Quotation number</div><div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $document->number }}</div></td>
                <td width="50%" style="padding:18px;"><div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">Issued on</div><div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $issueDate }}</div></td>
            </tr>
            <tr>
                <td colspan="2" style="padding:18px;border-top:1px solid #e2e8f0;background:#fff;"><div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">Quotation total</div><div style="margin-top:3px;font-size:30px;line-height:38px;font-weight:bold;color:#12263f;">RM {{ number_format((float) $document->total, 2) }}</div></td>
            </tr>
        </table>
    </td>
</tr>
<tr>
    <td style="padding:0 36px 34px;">
        <div style="margin-bottom:14px;font-size:16px;font-weight:bold;color:#12263f;">Would you like to proceed?</div>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td width="50%" style="padding-right:6px;"><a href="{{ $acceptUrl }}" style="display:block;padding:14px 10px;background:#16805b;color:#fff;text-align:center;text-decoration:none;border-radius:8px;font-size:15px;font-weight:bold;">✓ Accept quotation</a></td>
                <td width="50%" style="padding-left:6px;"><a href="{{ $rejectUrl }}" style="display:block;padding:14px 10px;background:#fff;color:#b42318;text-align:center;text-decoration:none;border:1px solid #d92d20;border-radius:8px;font-size:15px;font-weight:bold;">× Reject quotation</a></td>
            </tr>
        </table>
    </td>
</tr>
@endsection

@section('email-footer')
<p style="margin:0 0 8px;font-size:14px;line-height:22px;color:#475569;">The full details and item breakdown are included in the attached PDF.</p>
<p style="margin:0;font-size:14px;line-height:22px;color:#475569;">Questions? Simply reply to this email and our team will assist you.</p>
<p style="margin:18px 0 0;font-size:14px;color:#12263f;"><strong>OMG Computer</strong><br><span style="color:#64748b;">Sales &amp; Customer Support</span></p>
@endsection
