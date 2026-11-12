@extends('layouts.app')

@section('title', $editing ? 'Edit Draft' : 'Create Billing Document')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Billing" :title="$editing ? (($document->status === 'rejected' ? 'Edit rejected quotation ' : 'Edit draft ') . $document->number) : 'Create ' . $type" :back-url="$editing ? route('billing.documents.show', $document) : ($project ? route('projects.show', $project) : route('billing.index'))" :back-label="$editing ? 'Back to document' : ($project ? 'Back to project' : 'Back to billing')" />
    @if (session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
    @if ($ticket)<div class="alert alert-info">Quotation for repair ticket <a href="{{ route('tickets.show', $ticket) }}">{{ $ticket->ticket_number }}</a>. Customer and issue details have been prefilled.</div>@endif
    @if ($project)<div class="alert alert-info">Billing document for project <a href="{{ route('projects.show', $project) }}">{{ $project->name }}</a>. Customer details have been prefilled where available.</div>@endif
    @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $formAction }}">
        @csrf
        @if ($editing)@method('PUT')@endif
        <input type="hidden" name="type" value="{{ $type }}">
        @if ($ticket)<input type="hidden" name="repair_ticket_id" value="{{ $ticket->id }}">@endif
        @if ($project)<input type="hidden" name="project_id" value="{{ $project->id }}">@endif
        <div class="card mb-3"><div class="card-header fw-semibold">Customer and dates</div><div class="card-body row g-3">
            @if (! empty($customerRecipients))
                <div class="col-12">
                    <label class="form-label" for="customer-recipient">Select customer record or account</label>
                    <select class="form-select" id="customer-recipient" name="customer_recipient_key">
                        <option value="">Choose a customer...</option>
                        @foreach (['Customer records', 'Customer accounts'] as $groupName)
                            @php($groupRecipients = collect($customerRecipients)->where('group', $groupName))
                            @if ($groupRecipients->isNotEmpty())
                                <optgroup label="{{ $groupName }}">
                                    @foreach ($groupRecipients as $recipient)
                                        <option value="{{ $recipient['key'] }}"
                                            data-name="{{ $recipient['name'] }}"
                                            data-email="{{ $recipient['email'] }}"
                                            data-phone="{{ $recipient['phone'] }}"
                                            data-address="{{ $recipient['address'] }}"
                                            @selected(old('customer_recipient_key', $selectedCustomerRecipientKey) === $recipient['key'])>
                                            {{ $recipient['name'] }}{{ $recipient['email'] ? ' · '.$recipient['email'] : '' }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    <div class="form-text">Choose a saved customer record or registered customer account to fill the details below.</div>
                </div>
            @endif
            @if ($projectRecipients->isNotEmpty())
                <div class="col-12">
                    <label class="form-label" for="project-recipient">Customer recipient from this project</label>
                    <select class="form-select" id="project-recipient" name="recipient_key">
                        @foreach ($projectRecipients as $recipient)
                            <option value="{{ $recipient['key'] }}"
                                data-name="{{ $recipient['name'] }}"
                                data-email="{{ $recipient['email'] }}"
                                data-phone="{{ $recipient['phone'] }}"
                                data-address="{{ $recipient['address'] }}"
                                @selected(old('recipient_key', $selectedRecipientKey) === $recipient['key'])>
                                {{ $recipient['name'] }}{{ $recipient['email'] ? ' · '.$recipient['email'] : '' }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Selecting a linked customer fills in the quotation recipient details below.</div>
                </div>
            @endif
            <div class="col-md-6"><label class="form-label" for="customer_name">Customer name</label><input class="form-control" id="customer_name" name="customer_name" value="{{ old('customer_name', $document->customer_name) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="customer_email">Email</label><input class="form-control" id="customer_email" name="customer_email" type="email" value="{{ old('customer_email', $document->customer_email) }}"></div>
            <div class="col-md-3"><label class="form-label" for="customer_phone">Phone</label><input class="form-control" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $document->customer_phone) }}"></div>
            <div class="col-md-6"><label class="form-label" for="customer_address">Billing address</label><textarea class="form-control" id="customer_address" name="customer_address" rows="2">{{ old('customer_address', $document->customer_address) }}</textarea></div>
            <div class="col-md-3"><label class="form-label" for="issue_date">Issue date</label><input class="form-control" id="issue_date" name="issue_date" type="date" value="{{ old('issue_date', $document->issue_date?->format('Y-m-d') ?? now()->toDateString()) }}" required></div>
            <div class="col-md-3"><label class="form-label" for="due_date">Due date</label><input class="form-control" id="due_date" name="due_date" type="date" value="{{ old('due_date', $document->due_date?->format('Y-m-d')) }}">@if ($project && $type !== 'invoice')<div class="form-text">Quotation/payment date, separate from the project target date.</div>@endif</div>
        </div></div>
        @if ($project && $type !== 'invoice')
            <div class="card mb-3">
                <div class="card-header fw-semibold">Project scope and terms</div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label" for="project_scope">Included scope</label>
                        <textarea class="form-control" id="project_scope" name="project_scope" rows="3" maxlength="5000" placeholder="List the agreed pages, features, and deliverables.">{{ old('project_scope', $document->project_scope) }}</textarea>
                        <div class="form-text">Be specific about included deliverables. Additional requirements can be quoted separately.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="included_revision_rounds">Revision rounds included (maximum 3)</label>
                        <input class="form-control" id="included_revision_rounds" name="included_revision_rounds" type="number" min="0" max="3" step="1" value="{{ old('included_revision_rounds', $document->included_revision_rounds ?? 3) }}" required>
                        <div class="form-text">One round means one consolidated set of feedback.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="project_due_date">Target completion date</label>
                        <input class="form-control" id="project_due_date" name="project_due_date" type="date" value="{{ old('project_due_date', $document->project_due_date?->format('Y-m-d')) }}">
                        <div class="form-text">Starts after agreed initial payment and receipt of required content/access.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="payment_terms">Payment schedule</label>
                        <textarea class="form-control" id="payment_terms" name="payment_terms" rows="2" maxlength="5000" placeholder="Enter the agreed deposit and milestone payments.">{{ old('payment_terms', $document->payment_terms) }}</textarea>
                        <div class="form-text">If left blank, the quotation says payment schedule is to be agreed before work starts.</div>
                    </div>
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            The quotation will also state that client delays can move the target date, additional work may cost extra, hosting/domain/maintenance are excluded unless listed, and final handover follows payment of the agreed balance.
                        </div>
                    </div>
                </div>
            </div>
        @endif
        <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center"><span class="fw-semibold">Line items</span><button class="btn btn-sm btn-outline-primary" id="add-line" type="button"><span class="bi bi-plus me-1"></span>Add line</button></div>
            <div class="table-responsive"><table class="table align-middle mb-0 table-bordered"><thead><tr><th style="min-width:260px">Inventory or service</th><th style="min-width:200px">Description</th><th style="width:110px">Quantity</th><th style="width:160px">Unit price (RM)</th><th style="width:56px"></th></tr></thead><tbody id="lines">
                @php($currentLines = old('items', $lines))
                @foreach ($currentLines as $index => $line)
                    @php($selectedPart = empty($line['inventory_part_id']) ? null : $parts->get($line['inventory_part_id']))
                    @php($selectedProduct = empty($line['inventory_part_id']) ? $products->firstWhere('name', $line['description'] ?? '') : null)
                    @php($selectedCatalogValue = !empty($line['inventory_part_id']) ? 'part:'.$line['inventory_part_id'] : ($selectedProduct ? 'service:'.$selectedProduct['id'] : ''))
                    <tr class="billing-line">
                        <td>
                            <select class="form-select catalog-select" data-search-url="{{ route('billing.parts.search') }}" aria-label="Choose inventory item or service">
                                <option value="" @selected($selectedCatalogValue === '')>Custom line</option>
                                @if ($selectedPart)
                                    <option value="part:{{ $selectedPart->id }}" data-name="{{ $selectedPart->name }}" data-price="{{ $selectedPart->selling_price }}" selected>
                                        {{ $selectedPart->name }} ({{ $selectedPart->stock_quantity }} available)
                                    </option>
                                @endif
                                <optgroup label="Website services">
                                    @foreach ($products->where('category', 'website') as $product)
                                        <option value="service:{{ $product['id'] }}" data-name="{{ $product['name'] }}" data-price="{{ $product['billing_price'] }}" @selected($selectedCatalogValue === 'service:'.$product['id'])>{{ $product['name'] }} · RM{{ number_format($product['billing_price'], 2) }}</option>
                                    @endforeach
                                </optgroup>
                                <optgroup label="Products & services">
                                    @foreach ($products->where('category', '!=', 'website') as $product)
                                        <option value="service:{{ $product['id'] }}" data-name="{{ $product['name'] }}" data-price="{{ $product['billing_price'] }}" @selected($selectedCatalogValue === 'service:'.$product['id'])>{{ $product['name'] }} · from RM{{ number_format($product['billing_price'], 2) }}</option>
                                    @endforeach
                                </optgroup>
                            </select>
                            <div class="form-text">Search inventory by name or SKU, or choose a listed service.</div>
                            <input class="line-part-id" name="items[{{ $index }}][inventory_part_id]" value="{{ $line['inventory_part_id'] ?? '' }}" type="hidden">
                            <input class="line-component-id" name="items[{{ $index }}][custom_pc_component_id]" value="{{ $line['custom_pc_component_id'] ?? '' }}" type="hidden">
                            <input class="line-product-id" name="items[{{ $index }}][product_id]" value="{{ $line['product_id'] ?? $selectedProduct['id'] ?? '' }}" type="hidden">
                        </td>
                        <td><input class="form-control line-description" name="items[{{ $index }}][description]" value="{{ $line['description'] ?? '' }}" placeholder="Service or item description"></td>
                        <td><input class="form-control" name="items[{{ $index }}][quantity]" type="number" min="1" value="{{ $line['quantity'] ?? 1 }}" required></td>
                        <td><input class="form-control line-price" name="items[{{ $index }}][unit_price]" type="number" min="0" step="0.01" value="{{ $line['unit_price'] ?? '' }}" placeholder="0.00"></td>
                        <td><button class="btn btn-outline-secondary remove-line" type="button" aria-label="Remove line"><span class="bi bi-x-lg"></span></button></td>
                    </tr>
                @endforeach
            </tbody></table></div>
            <div class="card-body row g-3"><div class="col-md-3"><label class="form-label" for="tax_rate">Tax rate (%)</label><input class="form-control" id="tax_rate" name="tax_rate" type="number" min="0" max="100" step="0.01" value="{{ old('tax_rate', $document->tax_rate ?? 0) }}" required></div><div class="col-md-9"><label class="form-label" for="notes">Notes</label><textarea class="form-control" id="notes" name="notes" rows="2">{{ old('notes', $document->notes) }}</textarea></div></div>
        </div>
        <div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ $editing ? route('billing.documents.show', $document) : route('billing.index') }}">Cancel</a><button class="btn btn-primary" type="submit">{{ $editing ? ($document->status === 'rejected' ? 'Save revised quotation as draft' : 'Save draft changes') : ($type === 'invoice' ? 'Create and issue invoice' : 'Save quotation') }}</button></div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const customerRecipientSelect = document.getElementById('customer-recipient');
    if (!customerRecipientSelect) return;

    customerRecipientSelect.addEventListener('change', () => {
        const option = customerRecipientSelect.selectedOptions[0];
        document.getElementById('customer_name').value = option.dataset.name || '';
        document.getElementById('customer_email').value = option.dataset.email || '';
        document.getElementById('customer_phone').value = option.dataset.phone || '';
        document.getElementById('customer_address').value = option.dataset.address || '';
    });
})();

(() => {
    const recipientSelect = document.getElementById('project-recipient');
    if (!recipientSelect) return;

    recipientSelect.addEventListener('change', () => {
        const option = recipientSelect.selectedOptions[0];
        document.getElementById('customer_name').value = option.dataset.name || '';
        document.getElementById('customer_email').value = option.dataset.email || '';
        document.getElementById('customer_phone').value = option.dataset.phone || '';
        document.getElementById('customer_address').value = option.dataset.address || '';
    });
})();

(() => {
    const lines = document.getElementById('lines');
    const initializeCatalogSelect = (select) => {
        if (!window.jQuery || !window.jQuery.fn.select2) return;
        const $select = window.jQuery(select);
        $select.select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Search inventory by name or SKU',
            minimumInputLength: 2,
            ajax: {
                url: select.dataset.searchUrl,
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term }),
                processResults: (payload) => payload,
            },
        });
        $select.on('select2:select', (event) => {
            const data = event.params.data;
            const row = select.closest('.billing-line');
            row.querySelector('.line-part-id').value = data.id.startsWith('part:') ? data.id.slice(5) : '';
            row.querySelector('.line-component-id').value = '';
            row.querySelector('.line-product-id').value = data.id.startsWith('service:') ? data.id.slice(8) : '';
            row.querySelector('.line-description').value = data.name || '';
            row.querySelector('.line-price').value = data.price || '';
        });
    };
    lines.querySelectorAll('.catalog-select').forEach(initializeCatalogSelect);
    const renumber = () => lines.querySelectorAll('.billing-line').forEach((row, index) => {
        row.querySelectorAll('[name]').forEach((field) => field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`));
    });
    document.getElementById('add-line').addEventListener('click', () => {
        const row = lines.querySelector('.billing-line').cloneNode(true);
        row.querySelector('.select2-container')?.remove();
        const select = row.querySelector('.catalog-select');
        select.classList.remove('select2-hidden-accessible');
        select.removeAttribute('data-select2-id');
        select.removeAttribute('aria-hidden');
        select.removeAttribute('tabindex');
        select.querySelectorAll('option').forEach((option) => option.removeAttribute('data-select2-id'));
        row.querySelectorAll('input').forEach((field) => field.value = field.type === 'number' && field.name.includes('[quantity]') ? '1' : '');
        select.value = '';
        lines.append(row);
        initializeCatalogSelect(select);
        renumber();
    });
    lines.addEventListener('change', (event) => {
        if (!event.target.matches('.catalog-select')) return;
        const option = event.target.selectedOptions[0];
        const row = event.target.closest('.billing-line');
        const [kind, id] = event.target.value.split(':');
        row.querySelector('.line-part-id').value = kind === 'part' ? id : '';
        row.querySelector('.line-component-id').value = '';
        row.querySelector('.line-product-id').value = kind === 'service' ? id : '';
        row.querySelector('.line-description').value = option.dataset.name || '';
        row.querySelector('.line-price').value = option.dataset.price || '';
    });
    lines.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-line');
        if (!button) return;
        if (lines.querySelectorAll('.billing-line').length > 1) button.closest('.billing-line').remove();
        renumber();
    });
})();
</script>
@endpush