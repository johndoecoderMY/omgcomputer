@extends('layouts.app')

@section('title', $document->number)

@section('content')
<div class="container-fluid py-3" @if ($document->type === 'quotation') data-project-ajax-root @endif>
    <x-page-header eyebrow="{{ ucfirst($document->type) }}" :title="$document->number" :back-url="route('billing.index')" back-label="Back to billing">
        <x-slot name="actions">
            <a class="btn btn-outline-primary" href="{{ route('billing.documents.pdf', $document) }}" target="_blank"><span class="bi bi-filetype-pdf me-2"></span>PDF</a>
            @if (! auth()->user()->hasRole('customer') && ($document->status === 'draft' || ($document->type === 'quotation' && $document->status === 'rejected')))<a class="btn btn-outline-primary" href="{{ route('billing.documents.edit', $document) }}"><span class="bi bi-pencil me-2"></span>{{ $document->status === 'rejected' ? 'Edit rejected quotation' : 'Edit draft' }}</a>@endif
            @if ($document->repairTicket)<a class="btn btn-outline-secondary" href="{{ route('tickets.show', $document->repairTicket) }}"><span class="bi bi-ticket-perforated me-2"></span>{{ $document->repairTicket->ticket_number }}</a>@endif
            @if (! auth()->user()->hasRole('customer') && $document->type === 'quotation' && in_array($document->status, ['draft', 'sent', 'rejected'], true))
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#send-quotation-modal">
                    <span class="bi bi-send me-2"></span>{{ $document->status === 'draft' ? 'Send quotation' : 'Resend quotation' }}
                </button>
            @endif
            @if (! auth()->user()->hasRole('customer') && $document->type === 'quotation' && $document->status === 'draft')
                <form method="POST" action="{{ route('billing.documents.quotation-status', $document) }}" class="d-inline">
                    @csrf
                    <input type="hidden" name="status" value="accepted">
                    <button class="btn btn-success" onclick="return confirm('Record that the customer accepted this quotation outside the website?')">
                        <span class="bi bi-check-circle me-2"></span>Record customer accepted
                    </button>
                </form>
            @endif
            @if (auth()->user()->hasRole('customer') && $document->type === 'quotation' && $document->status === 'sent')
                <form method="POST" action="{{ route('billing.documents.quotation-status', $document) }}" class="d-inline">@csrf<input type="hidden" name="status" value="accepted"><button class="btn btn-success"><span class="bi bi-check-circle me-2"></span>Accept quotation</button></form>
                <form method="POST" action="{{ route('billing.documents.quotation-status', $document) }}" class="d-inline">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-outline-danger"><span class="bi bi-x-circle me-2"></span>Reject quotation</button></form>
            @endif
            @if (! auth()->user()->hasRole('customer') && $document->type === 'quotation' && $document->status === 'sent')
                <form method="POST" action="{{ route('billing.documents.quotation-status', $document) }}">@csrf<input type="hidden" name="status" value="accepted"><button class="btn btn-success">Mark accepted</button></form>
                <form method="POST" action="{{ route('billing.documents.quotation-status', $document) }}">@csrf<input type="hidden" name="status" value="rejected"><button class="btn btn-outline-danger">Mark rejected</button></form>
            @endif
            @if (! auth()->user()->hasRole('customer') && $document->type === 'invoice' && in_array($document->status, ['issued', 'partial', 'part_paid', 'paid'], true))
                <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#send-invoice-modal">
                    <span class="bi bi-send me-2" aria-hidden="true"></span>Email invoice
                </button>
            @endif
            @if (! auth()->user()->hasRole('customer') && $document->type === 'quotation' && $document->status === 'accepted')<form method="POST" action="{{ route('billing.documents.convert', $document) }}">@csrf<button class="btn btn-primary" onclick="return confirm('Issue this invoice and deduct listed stock?')">Convert to invoice</button></form>@endif
            @if (! auth()->user()->hasRole('customer') && ! in_array($document->status, ['void', 'paid'], true) && (float) $document->paid_amount === 0.0)<form method="POST" action="{{ route('billing.documents.void', $document) }}">@csrf<button class="btn btn-outline-danger" onclick="return confirm('Void this document?')">Void</button></form>@endif
        </x-slot>
    </x-page-header>

    @if (! auth()->user()->hasRole('customer') && $document->type === 'quotation' && in_array($document->status, ['draft', 'sent', 'rejected'], true))
        @include('billing._send-modal', ['sendModal' => [
            'id' => 'send-quotation-modal',
            'title' => $document->status === 'draft' ? 'Send quotation' : 'Resend quotation',
            'description' => 'Send the quotation PDF by email or WhatsApp. The private WhatsApp PDF link works for guests without an account and expires after 7 days.',
            'action' => route('billing.documents.send', $document),
            'email' => $document->customer_email,
            'email_preview_url' => route('billing.documents.email-preview', $document),
            'pdf_preview_url' => route('billing.documents.pdf', $document),
            'phone' => $document->customer_phone,
            'whatsapp_key' => 'billing.whatsapp.quotation',
            'whatsapp_replacements' => [
                'name' => $document->customer_name,
                'number' => $document->number,
                'total' => number_format((float) $document->total, 2),
            ],
            'whatsapp_urls' => $guestDocumentUrls,
            'whatsapp_action_urls' => $whatsappActionUrls,
            'filename' => $document->number,
            'submit_label' => 'Send quotation',
            'mark_sent_url' => route('billing.documents.quotation-status', $document),
        ]])
    @endif
    @if (! auth()->user()->hasRole('customer') && $document->type === 'invoice' && in_array($document->status, ['issued', 'partial', 'part_paid', 'paid'], true))
        @include('billing._send-modal', ['sendModal' => [
            'id' => 'send-invoice-modal',
            'title' => 'Email invoice',
            'description' => $canSharePaymentLink
                ? 'Email the invoice PDF or share its private WhatsApp PDF and payment links. The payment link works for guests without an account and expires after 7 days.'
                : 'Email the invoice PDF or share its private WhatsApp PDF link. The link works for guests without an account and expires after 7 days.',
            'action' => route('billing.documents.send', $document),
            'email' => $document->customer_email,
            'email_preview_url' => route('billing.documents.email-preview', $document),
            'pdf_preview_url' => route('billing.documents.pdf', $document),
            'phone' => $document->customer_phone,
            'whatsapp_key' => $canSharePaymentLink ? 'billing.whatsapp.invoice_with_payment_link' : 'billing.whatsapp.invoice',
            'whatsapp_replacements' => [
                'name' => $document->customer_name,
                'number' => $document->number,
                'total' => number_format((float) $document->total, 2),
                'balance' => number_format((float) $document->balance, 2),
                'payment_url' => '__GUEST_PAYMENT_URL__',
            ],
            'whatsapp_urls' => $guestDocumentUrls,
            'payment_url_placeholder' => '__GUEST_PAYMENT_URL__',
            'payment_link_url' => $canSharePaymentLink ? route('billing.guest-payment-link', $document) : null,
            'filename' => $document->number,
            'submit_label' => 'Email invoice',
            'mark_sent_url' => null,
        ]])
    @endif

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
    @if (! auth()->user()->hasRole('customer')
        && in_array(config('mail.default'), ['log', 'array'], true)
        && !(config('mail.default') === 'array' && app()->environment('testing')))
        <div class="alert alert-warning" role="alert">
            Outgoing email is not configured, so billing emails will not be delivered.
            @if (auth()->user()->hasRole('admin'))
                <a class="alert-link" href="{{ route('email.settings') }}">Configure SMTP settings</a> and send a test email.
            @else
                Ask an administrator to configure SMTP settings and send a test email.
            @endif
        </div>
    @endif
    @if (! auth()->user()->hasRole('customer') && $document->type === 'quotation' && $document->status === 'draft')
        <div class="alert alert-info">Sending this quotation through the website is optional. If the customer already accepted it by phone, in person, or through another form, use <strong>Record customer accepted</strong> to automatically issue and email the invoice.</div>
    @endif
    @if ($document->type === 'quotation' && $document->status === 'accepted')
        <div class="alert alert-info">This accepted quotation has not yet been issued as an invoice. Use <strong>Convert to invoice</strong> to issue it and email the invoice.</div>
    @endif
    @if ($errors->any())<div class="alert alert-danger">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    <div class="row g-3 mb-3">
        <div class="col-lg-8"><div class="card h-100"><div class="card-header fw-semibold">Document details</div><div class="card-body">
            <div class="row g-3"><div class="col-md-6"><div class="small text-body-secondary">Bill to</div>@if ($document->type === 'quotation' && $customerRecord && ! auth()->user()->hasRole('customer'))<a class="fw-semibold" href="{{ route('customers.show', $customerRecord) }}">{{ $document->customer_name }}</a>@else<strong>{{ $document->customer_name }}</strong>@endif<div>{{ $document->customer_email }}</div><div>{{ $document->customer_phone }}</div><div class="text-pre-line">{{ $document->customer_address }}</div></div><div class="col-md-3"><div class="small text-body-secondary">Issue date</div><div>{{ $document->issue_date->format('M j, Y') }}</div><div class="small text-body-secondary mt-2">Due date</div><div>{{ $document->due_date?->format('M j, Y') ?? 'Not set' }}</div></div><div class="col-md-3"><div class="small text-body-secondary">Status</div><x-status-badge :status="$document->status" :label="str_replace('_', ' ', ucfirst($document->status))" /></div></div>
            <hr><div class="table-responsive"><table class="table align-middle table-bordered"><thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Unit price</th><th class="text-end">Total</th><th class="text-end">{{ __('billing.pdf.item_discount') }}</th></tr></thead><tbody>@foreach ($document->items as $item)<tr><td>{{ $item->description }}</td><td class="text-end">{{ $item->quantity }}</td><td class="text-end">RM {{ number_format($item->unit_price, 2) }}</td><td class="text-end">RM {{ number_format($item->line_total, 2) }}</td><td class="text-end">{{ (float) $item->discount_amount > 0 ? '- RM '.number_format($item->discount_amount, 2) : '—' }}</td></tr>@endforeach</tbody></table></div>
            @if ($document->notes)<div class="small text-body-secondary">{{ $document->notes }}</div>@endif
            @if ($document->type === 'quotation' && $document->project_id)
                <div class="border-top mt-4 pt-3">
                    @include('billing._project-quotation-terms', ['document' => $document])
                </div>
            @endif
        </div></div></div>
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header fw-semibold">Document summary</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span>RM {{ number_format($document->subtotal, 2) }}</span></div>
                    @if ($document->items->sum('discount_amount') > 0)
                        <div class="d-flex justify-content-between text-success"><span>{{ __('billing.pdf.item_discount') }}</span><span>- RM {{ number_format($document->items->sum('discount_amount'), 2) }}</span></div>
                    @endif
                    @if ((float) $document->discount_amount > 0)
                        <div class="d-flex justify-content-between text-success"><span>Discount</span><span>- RM {{ number_format($document->discount_amount, 2) }}</span></div>
                    @endif
                    @if ((float) $document->tax_amount > 0)
                        <div class="d-flex justify-content-between"><span>Tax ({{ number_format($document->tax_rate, 2) }}%)</span><span>RM {{ number_format($document->tax_amount, 2) }}</span></div>
                    @endif
                    <hr>
                    <div class="d-flex justify-content-between fw-bold"><span>Total</span><span>RM {{ number_format($document->total, 2) }}</span></div>
                    @if ($document->type === 'invoice')
                        <div class="d-flex justify-content-between mt-2"><span>Paid</span><span>RM {{ number_format($document->paid_amount, 2) }}</span></div>
                        <div class="d-flex justify-content-between fw-semibold"><span>Balance</span><span>RM {{ number_format($document->balance, 2) }}</span></div>
                    @endif
                </div>
            </div>
        </div>
            @if (auth()->user()->hasRole('customer') && $chipEnabled && $document->type === 'invoice' && in_array($document->status, ['issued', 'partial', 'part_paid'], true) && $document->balance > 0)
                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header fw-semibold">Pay securely with CHIP</div>
                        <form method="POST" action="{{ route('billing.chip.checkout', $document) }}">
                            @csrf
                            <div class="card-body">
                                <p>Outstanding balance: <strong>RM {{ number_format($document->balance, 2) }}</strong>.</p>
                                <label class="form-label" for="chip-payment-amount">Payment amount (RM)</label>
                                <input class="form-control mb-3" id="chip-payment-amount" name="amount" type="number" min="0.01"
                                    max="{{ number_format($document->balance, 2, '.', '') }}" step="0.01"
                                    value="{{ number_format($document->balance, 2, '.', '') }}" required>
                                <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Available payment options"><span class="badge text-bg-light border"><span class="bi bi-qr-code-scan me-1" aria-hidden="true"></span>QR pay</span><span class="badge text-bg-light border">DuitNow</span><span class="badge text-bg-light border">Online banking</span><span class="badge text-bg-light border">Card</span></div>
                                <small class="text-body-secondary d-block">Choose any amount up to the balance. Available payment methods depend on the channels enabled for your CHIP merchant account.</small>
                            </div>
                            <div class="card-footer"><button class="btn btn-primary w-100"><span class="bi bi-shield-check me-2" aria-hidden="true"></span>Continue to CHIP checkout</button></div>
                        </form>
                    </div>
                </div>
            @endif
            @if (! auth()->user()->hasRole('customer') && $chipEnabled && $document->type === 'invoice' && in_array($document->status, ['issued', 'partial', 'part_paid'], true) && $document->balance > 0)
                <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Take payment at the counter with CHIP</div>
                    <form method="POST" action="{{ route('billing.chip.counter', $document) }}">
                        @csrf
                        <div class="card-body">
                            @if (filter_var($document->customer_email, FILTER_VALIDATE_EMAIL))
                                <label class="form-label" for="counter-chip-payment-amount">Payment amount (RM)</label>
                                <input class="form-control" id="counter-chip-payment-amount" name="amount" type="number" min="0.01"
                                    max="{{ number_format($document->balance, 2, '.', '') }}" step="0.01"
                                    value="{{ number_format($document->balance, 2, '.', '') }}" required>
                                <p class="small text-body-secondary mt-2 mb-0">Enter an amount up to RM {{ number_format($document->balance, 2) }}. Show the CHIP checkout to the customer to select an available payment method.</p>
                            @else
                                <p class="text-warning-emphasis mb-0">Add a valid customer email to the invoice before starting CHIP checkout.</p>
                            @endif
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary w-100" type="submit" @disabled(! filter_var($document->customer_email, FILTER_VALIDATE_EMAIL))>
                                <span class="bi bi-qr-code-scan me-2" aria-hidden="true"></span>Open CHIP checkout
                            </button>
                        </div>
                    </form>
                </div>
                </div>
            @endif
            @if (auth()->user()->hasRole('admin') && $chipEnabled && $document->type === 'invoice' && in_array($document->status, ['issued', 'partial', 'part_paid'], true) && $document->balance > 0)
                <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header fw-semibold">Share a guest payment link</div>
                    <div class="card-body">
                        <p class="small text-body-secondary">Create a private 7-day link for customers who do not have an account. Generating a replacement revokes the previous link.</p>
                        @if ($document->guest_payment_link_expires_at?->isFuture())
                            <p class="small">Current link expires {{ $document->guest_payment_link_expires_at->format('M j, Y g:i A') }}.</p>
                        @endif
                        @if (session('guest_payment_url'))
                            <label class="form-label" for="guest-payment-url">Copy this link and send it to the customer</label>
                            <div class="input-group mb-3">
                                <input class="form-control" id="guest-payment-url" type="url" value="{{ session('guest_payment_url') }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" data-copy-target="guest-payment-url">Copy link</button>
                            </div>
                        @endif
                        @unless (filter_var($document->customer_email, FILTER_VALIDATE_EMAIL))
                            <div class="alert alert-warning mb-0">A valid customer email is required before creating a payment link.</div>
                        @endunless
                    </div>
                    @if (filter_var($document->customer_email, FILTER_VALIDATE_EMAIL))
                        <div class="card-footer">
                            <form class="w-100" method="POST" action="{{ route('billing.guest-payment-link', $document) }}">
                                @csrf
                                <button class="btn btn-outline-primary w-100" type="submit">
                                    <span class="bi bi-link-45deg me-2" aria-hidden="true"></span>Generate or replace link
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
                </div>
            @endif
        </div>
    @if ($document->type === 'invoice')
        <div class="row g-3 mb-3">
            @if (auth()->user()->hasRole('admin') && in_array($document->status, ['issued', 'partial', 'part_paid'], true))
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header fw-semibold">Record payment</div>
                        <form method="POST" action="{{ route('billing.documents.payments.store', $document) }}">
                            @csrf
                            <div class="card-body">
                                <label class="form-label" for="amount">Payment amount (RM)</label>
                                <input class="form-control mb-2" id="amount" name="amount" type="number" min="0.01"
                                    max="{{ $document->balance }}" step="0.01"
                                    value="{{ number_format($document->balance, 2, '.', '') }}" required>
                                <div class="form-text mb-3">Enter the amount received, up to the outstanding balance of RM {{ number_format($document->balance, 2) }}. The remaining balance stays due.</div>
                                <label class="form-label" for="method">Method</label>
                                <select class="form-select mb-3" id="method" name="method" required>
                                    @foreach (['cash', 'card', 'bank_transfer', 'online', 'other'] as $method)
                                        <option value="{{ $method }}">{{ str_replace('_', ' ', ucfirst($method)) }}</option>
                                    @endforeach
                                </select>
                                <label class="form-label" for="reference">Reference</label>
                                <input class="form-control mb-3" id="reference" name="reference" maxlength="255">
                                <label class="form-label" for="receipt-delivery">Receipt email</label>
                                <select class="form-select mb-3" id="receipt-delivery" name="receipt_delivery">
                                    <option value="send" selected>Email automatically after recording</option>
                                    <option value="preview">Record payment, then preview before sending</option>
                                </select>
                                <label class="form-label" for="receipt-locale">Receipt email and PDF language</label>
                                <select class="form-select" id="receipt-locale" name="receipt_locale">
                                    @foreach (\App\Models\CustomerMessageTemplate::LANGUAGES as $locale => $label)
                                        <option value="{{ $locale }}" @selected(app()->getLocale() === $locale)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-primary w-100">Record payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            <div class="{{ auth()->user()->hasRole('admin') && in_array($document->status, ['issued', 'partial', 'part_paid'], true) ? 'col-lg-6' : 'col-12' }}">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Payment history</div>
                    <div class="table-responsive">
                        <table class="table mb-0 table-bordered">
                            <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="text-end">Amount</th><th>Receipt</th></tr></thead>
                            <tbody>
                                @forelse ($document->payments as $payment)
                                    <tr>
                                        <td>{{ $payment->paid_at->format('M j, Y g:i A') }}</td>
                                        <td>{{ str_replace('_', ' ', ucfirst($payment->method)) }}</td>
                                        <td>{{ $payment->reference ?: '—' }}</td>
                                        <td class="text-end">RM {{ number_format($payment->amount, 2) }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('billing.receipts.pdf', $payment) }}" target="_blank" rel="noopener">
                                                <span class="bi bi-filetype-pdf me-1" aria-hidden="true"></span>{{ $payment->receipt_number }}
                                            </a>
                                            @unless (auth()->user()->hasRole('customer'))
                                                <button class="btn btn-sm btn-outline-secondary" type="button"
                                                    data-bs-toggle="modal" data-bs-target="#send-receipt-modal-{{ $payment->id }}">
                                                    <span class="bi bi-envelope me-1" aria-hidden="true"></span>Send receipt
                                                </button>
                                            @endunless
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="text-center text-body-secondary py-4" colspan="5">No payments recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @unless (auth()->user()->hasRole('customer'))
            @foreach ($document->payments as $payment)
                @include('billing._send-modal', ['sendModal' => [
                    'id' => 'send-receipt-modal-'.$payment->id,
                    'title' => $payment->receipt_emailed_at ? 'Resend receipt' : 'Send receipt',
                    'description' => 'Email the receipt PDF or share its private WhatsApp PDF link. The link works for guests without an account and expires after 7 days.',
                    'action' => route('billing.receipts.send', $payment),
                    'email' => $document->customer_email,
                    'email_preview_url' => route('billing.receipts.email-preview', $payment),
                    'pdf_preview_url' => route('billing.receipts.pdf', $payment),
                    'phone' => $document->customer_phone,
                    'whatsapp_key' => 'billing.whatsapp.receipt',
                    'whatsapp_replacements' => [
                        'name' => $document->customer_name,
                        'amount' => number_format((float) $payment->amount, 2),
                        'invoice' => $document->number,
                    ],
                    'whatsapp_urls' => $guestReceiptUrls[$payment->id] ?? [],
                    'filename' => $payment->receipt_number,
                    'submit_label' => 'Send receipt',
                    'mark_sent_url' => null,
                ]])
            @endforeach
        @endunless
    @endif
</div>
@push('scripts')
<script>
document.querySelectorAll('[data-copy-target]').forEach((button) => {
    button.addEventListener('click', async () => {
        const input = document.getElementById(button.dataset.copyTarget);
        if (!input) return;
        input.select();
        try {
            await navigator.clipboard.writeText(input.value);
            button.textContent = 'Copied';
        } catch {
            button.textContent = 'Select and copy';
        }
    });
});
</script>
@endpush
@endsection