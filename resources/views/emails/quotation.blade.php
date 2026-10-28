<p>Hello {{ $document->customer_name }},</p>
<p>Please find attached quotation <strong>{{ $document->number }}</strong> from OMG Computer.</p>
<p>Quotation total: <strong>RM {{ number_format((float) $document->total, 2) }}</strong></p>
<p>If you have any questions or would like to accept this quotation, please reply to this email.</p>
<p>Thank you,<br>OMG Computer</p>
