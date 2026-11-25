@extends('emails.layout')

@php
    $emailTitle = __('billing.email.quotation_subject', ['number' => $document->number]);
    $emailKicker = __('billing.pdf.quotation');
    $emailReference = $document->number;
    $acceptUrl = \Illuminate\Support\Facades\URL::signedRoute('billing.quotations.respond', ['document' => $document, 'status' => 'accepted']);
    $rejectUrl = \Illuminate\Support\Facades\URL::signedRoute('billing.quotations.respond', ['document' => $document, 'status' => 'rejected']);
    $issueDate = $document->issue_date
        ? \Illuminate\Support\Carbon::parse($document->issue_date)->locale(app()->getLocale())->translatedFormat('d M Y')
        : now()->locale(app()->getLocale())->translatedFormat('d M Y');
@endphp

@section('email-content')
<tr>
    <td class="email-content-padding" style="padding:36px 36px 28px;">
        <p style="margin:0 0 8px;font-size:14px;color:#64748b;">{{ __('billing.email.hello', ['name' => $document->customer_name]) }}</p>
        <h1 class="email-heading" style="margin:0 0 14px;font-size:28px;line-height:36px;color:#12263f;">{{ __('billing.email.quotation_title') }}</h1>
        <p style="margin:0;font-size:16px;line-height:26px;color:#475569;">{{ __('billing.email.quotation_intro') }}</p>
    </td>
</tr>
<tr>
    <td class="email-section-padding" style="padding:0 36px 28px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
            <tr>
                <td class="email-column-stack" width="50%" style="padding:18px;border-right:1px solid #e2e8f0;"><div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">{{ __('billing.email.quotation_number') }}</div><div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $document->number }}</div></td>
                <td class="email-column-stack" width="50%" style="padding:18px;"><div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">{{ __('billing.email.issued_on') }}</div><div style="margin-top:5px;font-weight:bold;color:#12263f;">{{ $issueDate }}</div></td>
            </tr>
            <tr>
                <td colspan="2" style="padding:18px;border-top:1px solid #e2e8f0;background:#fff;"><div style="font-size:11px;color:#64748b;text-transform:uppercase;font-weight:bold;">{{ __('billing.email.quotation_total') }}</div><div style="margin-top:3px;font-size:30px;line-height:38px;font-weight:bold;color:#12263f;">RM {{ number_format((float) $document->total, 2) }}</div></td>
            </tr>
        </table>
    </td>
</tr>
<tr>
    <td class="email-section-padding" style="padding:0 36px 34px;">
        <div style="margin-bottom:14px;font-size:16px;font-weight:bold;color:#12263f;">{{ __('billing.email.proceed_prompt') }}</div>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
            <tr>
                <td class="email-button-cell" width="50%" style="padding-right:6px;"><a class="email-button" href="{{ ($preview ?? false) ? '#' : ($acceptUrl ?? '#') }}" @if ($preview ?? false) aria-disabled="true" @endif style="display:block;padding:14px 10px;background:#16805b;color:#fff;text-align:center;text-decoration:none;border-radius:8px;font-size:15px;font-weight:bold;">✓ {{ __('billing.email.accept_quotation') }}</a></td>
                <td class="email-button-cell" width="50%" style="padding-left:6px;"><a class="email-button" href="{{ ($preview ?? false) ? '#' : ($rejectUrl ?? '#') }}" @if ($preview ?? false) aria-disabled="true" @endif style="display:block;padding:14px 10px;background:#fff;color:#b42318;text-align:center;text-decoration:none;border:1px solid #d92d20;border-radius:8px;font-size:15px;font-weight:bold;">× {{ __('billing.email.reject_quotation') }}</a></td>
            </tr>
        </table>
    </td>
</tr>
@endsection

@section('email-footer')
<p style="margin:0 0 8px;font-size:14px;line-height:22px;color:#475569;">{{ __('billing.email.quotation_pdf_note') }}</p>
<p style="margin:0;font-size:14px;line-height:22px;color:#475569;">{{ __('billing.email.reply_for_help') }}</p>
<p style="margin:18px 0 0;font-size:14px;color:#12263f;"><strong>OMG Computer</strong><br><span style="color:#64748b;">{{ __('billing.email.signature_title') }}</span></p>
@endsection
