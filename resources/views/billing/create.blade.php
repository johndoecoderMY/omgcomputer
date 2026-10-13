@extends('layouts.app')

@section('title', $type === 'invoice' ? 'New Invoice' : 'New Quotation')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div><p class="text-primary fw-bold text-uppercase fs-10 mb-1">Billing</p><h1 class="h2 mb-0">New {{ $type === 'invoice' ? 'invoice' : 'quotation' }}</h1></div>
            <a href="{{ route('billing.index') }}" class="btn btn-outline-secondary">Back to documents</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('billing.store') }}" id="billing-form">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <div class="card mb-4">
                <div class="card-header"><h2 class="h5 mb-0">Customer and document details</h2></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="customer-name">Customer name</label><input class="form-control" id="customer-name" name="customer_name" value="{{ old('customer_name') }}" maxlength="180" required></div>
                        <div class="col-md-3"><label class="form-label" for="customer-email">Email</label><input class="form-control" id="customer-email" type="email" name="customer_email" value="{{ old('customer_email') }}"></div>
                        <div class="col-md-3"><label class="form-label" for="customer-phone">Phone</label><input class="form-control" id="customer-phone" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="50"></div>
                        <div class="col-md-3"><label class="form-label" for="issued-at">Date</label><input class="form-control" id="issued-at" type="date" name="issued_at" value="{{ old('issued_at', now()->toDateString()) }}" required></div>
                        <div class="col-md-3"><label class="form-label" for="due-date">Due date</label><input class="form-control" id="due-date" type="date" name="due_date" value="{{ old('due_date') }}"></div>
                        <div class="col-md-3"><label class="form-label" for="tax-rate">Tax rate (%)</label><input class="form-control" id="tax-rate" type="number" name="tax_rate" min="0" max="100" step="0.01" value="{{ old('tax_rate', '0.00') }}" required><small class="text-600">Defaults to no tax. Enter only the applicable rate.</small></div>
                        <div class="col-12"><label class="form-label" for="notes">Terms / notes</label><textarea class="form-control" id="notes" name="notes" rows="2" maxlength="3000">{{ old('notes') }}</textarea></div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center gap-2"><h2 class="h5 mb-0">Line items</h2><button class="btn btn-sm btn-outline-primary" type="button" id="add-line"><span class="fas fa-plus me-1"></span>Add line</button></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th style="min-width:220px">Description / stock part</th><th style="width:120px">Qty</th><th style="width:180px">Unit price (RM)</th><th class="text-end" style="width:160px">Line total</th><th></th></tr></thead>
                        <tbody id="billing-lines">
                            <tr class="billing-line">
                                <td>
                                    <select class="form-select form-select-sm part-choice mb-2" name="items[0][inventory_part_id]">
                                        <option value="">Service / custom item</option>
                                        @foreach ($parts as $part)
                                            <option value="{{ $part->id }}" data-name="{{ $part->name }}" data-price="{{ $part->sale_price }}" data-stock="{{ $part->stock_quantity }}">{{ $part->sku }} — {{ $part->name }} ({{ $part->stock_quantity }} in stock)</option>
                                        @endforeach
                                    </select>
                                    <input class="form-control form-control-sm line-description" name="items[0][description]" maxlength="255" required placeholder="Describe service or part">
                                </td>
                                <td><input class="form-control form-control-sm line-quantity" name="items[0][quantity]" type="number" min="1" value="1" required></td>
                                <td><input class="form-control form-control-sm line-price" name="items[0][unit_price]" type="number" min="0" max="99999999.99" step="0.01" value="0.00" required></td>
                                <td class="text-end line-total">RM0.00</td>
                                <td><button class="btn btn-sm btn-link text-danger remove-line" type="button" aria-label="Remove line item"><span class="fas fa-trash"></span></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-body border-top">
                    <div class="row justify-content-end">
                        <div class="col-sm-6 col-lg-4">
                            <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong id="subtotal">RM0.00</strong></div>
                            <div class="d-flex justify-content-between mb-2"><span>Tax</span><strong id="tax-total">RM0.00</strong></div>
                            <div class="d-flex justify-content-between border-top pt-2"><span class="h5">Total</span><strong class="h5" id="grand-total">RM0.00</strong></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="alert alert-info" role="note">
                @if ($type === 'invoice')
                    Creating this invoice issues it and deducts any linked parts from inventory immediately.
                @else
                    This quotation is saved as a draft. Converting it to an invoice will deduct linked stock.
                @endif
            </div>
            <button class="btn btn-primary btn-lg" type="submit"><span class="fas fa-check me-2"></span>Create {{ $type === 'invoice' ? 'invoice' : 'quotation' }}</button>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const body = document.getElementById('billing-lines');
            const taxInput = document.getElementById('tax-rate');
            let lineIndex = body.querySelectorAll('.billing-line').length;
            const parts = @json($parts->map(fn ($part) => [
                'id' => $part->id,
                'sku' => $part->sku,
                'name' => $part->name,
                'price' => (float) $part->sale_price,
                'stock' => $part->stock_quantity,
            ])->values());

            function recalculate() {
                let subtotal = 0;
                body.querySelectorAll('.billing-line').forEach(function (row) {
                    const quantity = Number(row.querySelector('.line-quantity').value) || 0;
                    const price = Number(row.querySelector('.line-price').value) || 0;
                    const total = Math.round(quantity * price * 100) / 100;
                    subtotal += total;
                    row.querySelector('.line-total').textContent = 'RM' + total.toFixed(2);
                });
                subtotal = Math.round(subtotal * 100) / 100;
                const tax = Math.round(subtotal * (Number(taxInput.value) || 0)) / 100;
                document.getElementById('subtotal').textContent = 'RM' + subtotal.toFixed(2);
                document.getElementById('tax-total').textContent = 'RM' + tax.toFixed(2);
                document.getElementById('grand-total').textContent = 'RM' + (subtotal + tax).toFixed(2);
            }

            function renumberRows() {
                body.querySelectorAll('.billing-line').forEach(function (row, index) {
                    row.querySelector('.part-choice').name = 'items[' + index + '][inventory_part_id]';
                    row.querySelector('.line-description').name = 'items[' + index + '][description]';
                    row.querySelector('.line-quantity').name = 'items[' + index + '][quantity]';
                    row.querySelector('.line-price').name = 'items[' + index + '][unit_price]';
                });
                lineIndex = body.querySelectorAll('.billing-line').length;
            }

            document.getElementById('add-line').addEventListener('click', function () {
                const row = document.createElement('tr');
                row.className = 'billing-line';
                row.innerHTML = '<td><select class="form-select form-select-sm part-choice mb-2"><option value="">Service / custom item</option>' +
                    parts.map(function (part) {
                        return '<option value="' + part.id + '" data-name="' + escapeHtml(part.name) + '" data-price="' + part.price.toFixed(2) + '">' +
                            escapeHtml(part.sku + ' — ' + part.name + ' (' + part.stock + ' in stock)') + '</option>';
                    }).join('') +
                    '</select><input class="form-control form-control-sm line-description" maxlength="255" required placeholder="Describe service or part"></td>' +
                    '<td><input class="form-control form-control-sm line-quantity" type="number" min="1" value="1" required></td>' +
                    '<td><input class="form-control form-control-sm line-price" type="number" min="0" max="99999999.99" step="0.01" value="0.00" required></td>' +
                    '<td class="text-end line-total">RM0.00</td><td><button class="btn btn-sm btn-link text-danger remove-line" type="button" aria-label="Remove line item"><span class="fas fa-trash"></span></button></td>';
                body.appendChild(row);
                renumberRows();
                recalculate();
            });

            function escapeHtml(value) {
                return value.replace(/[&<>"']/g, function (character) {
                    return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character];
                });
            }

            body.addEventListener('change', function (event) {
                if (event.target.classList.contains('part-choice')) {
                    const option = event.target.options[event.target.selectedIndex];
                    const row = event.target.closest('.billing-line');
                    if (option.value !== '') {
                        row.querySelector('.line-description').value = option.dataset.name;
                        row.querySelector('.line-price').value = Number(option.dataset.price).toFixed(2);
                    }
                    recalculate();
                }
            });

            body.addEventListener('input', recalculate);
            body.addEventListener('click', function (event) {
                const button = event.target.closest('.remove-line');
                if (!button) return;
                if (body.querySelectorAll('.billing-line').length === 1) {
                    const row = button.closest('.billing-line');
                    row.querySelectorAll('input').forEach(function (input) { input.value = input.type === 'number' ? '1' : ''; });
                    row.querySelector('.line-price').value = '0.00';
                    row.querySelector('.part-choice').value = '';
                } else {
                    button.closest('.billing-line').remove();
                    renumberRows();
                }
                recalculate();
            });
            taxInput.addEventListener('input', recalculate);
            recalculate();
        })();
    </script>
@endpush
