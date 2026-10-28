<p>Hello {{ $document->customer_name }},</p>
<p>Please find attached quotation <strong>{{ $document->number }}</strong> from OMG Computer.</p>
<p>Quotation total: <strong>RM {{ number_format((float) $document->total, 2) }}</strong></p>
@php
    $acceptUrl = \Illuminate\Support\Facades\URL::signedRoute('billing.quotations.respond', ['document' => $document, 'status' => 'accepted']);
    $rejectUrl = \Illuminate\Support\Facades\URL::signedRoute('billing.quotations.respond', ['document' => $document, 'status' => 'rejected']);
@endphp
<p>
    <a href="{{ $acceptUrl }}" style="display:inline-block;background:#198754;color:#fff;padding:10px 18px;text-decoration:none;border-radius:4px;margin-right:8px">Accept quotation</a>
    <a href="{{ $rejectUrl }}" style="display:inline-block;background:#dc3545;color:#fff;padding:10px 18px;text-decoration:none;border-radius:4px">Reject quotation</a>
</p>
<p>If you have any questions, please reply to this email.</p>
<p>Thank you,<br>OMG Computer</p>
