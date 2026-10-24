@extends('layouts.app')

@section('title', $categoryLabel)

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendors/glightbox/glightbox.min.css') }}">
@endpush

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Operating costs" :title="$categoryLabel">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('expenses.create', $category) }}">
                <i class="bi bi-plus me-2" aria-hidden="true"></i>Add {{ strtolower($categoryLabel) }} item
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please check the form details.</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6">
            <div class="card h-100"><div class="card-body">
                <div class="small text-body-secondary">Due in the next 30 days</div>
                <div class="fs-4 fw-semibold">{{ $upcomingCount }}</div>
            </div></div>
        </div>
        <div class="col-sm-6">
            <div class="card h-100"><div class="card-body">
                <div class="small text-body-secondary">Overdue</div>
                <div class="fs-4 fw-semibold {{ $overdueCount ? 'text-danger' : '' }}">{{ $overdueCount }}</div>
            </div></div>
        </div>
    </div>

    <div class="card p-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered">
                <thead><tr><th>Item</th><th>Frequency</th><th>Next due</th><th>Amount</th><th>Status</th><th>Payment history &amp; record payment</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $expense->name }}</div>
                                @if ($expense->vendor)<div class="small text-body-secondary">{{ $expense->vendor }}</div>@endif
                                @if ($expense->notes)<div class="small text-body-secondary">{{ $expense->notes }}</div>@endif
                            </td>
                            <td>{{ \App\Models\BusinessExpense::FREQUENCIES[$expense->frequency] }}</td>
                            <td>{{ $expense->due_date?->format('M j, Y') ?? '—' }}</td>
                            <td>
                                <div>RM {{ number_format((float) $expense->amount, 2) }}</div>
                                @if ((float) $expense->cycle_paid_amount > 0)
                                    <div class="small text-body-secondary">RM {{ number_format((float) $expense->cycle_paid_amount, 2) }} paid</div>
                                @endif
                            </td>
                            <td>
                                <span @class([
                                    'badge',
                                    'text-bg-success' => $expense->payment_status === 'paid',
                                    'text-bg-danger' => $expense->payment_status === 'overdue',
                                    'text-bg-warning' => $expense->payment_status === 'partially paid',
                                    'text-bg-secondary' => $expense->payment_status === 'due',
                                ])>{{ ucfirst($expense->payment_status) }}</span>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary mb-2" href="{{ route('expenses.pdf', [$category, $expense]) }}" target="_blank" rel="noopener">
                                    <i class="bi bi-filetype-pdf me-1" aria-hidden="true"></i>Export PDF
                                </a>
                                <details>
                                    <summary>{{ $expense->payments->count() }} payment(s)</summary>
                                    @if ($expense->payments->isNotEmpty())
                                        <ul class="small mt-2 mb-3 ps-3">
                                            @foreach ($expense->payments as $payment)
                                                <li>
                                                    {{ $payment->paid_at->format('M j, Y') }} · RM {{ number_format((float) $payment->amount, 2) }}
                                                    @if ($payment->reference) · {{ $payment->reference }}@endif
                                                    @if ($payment->user) · {{ $payment->user->name }}@endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </details>
                                @if ($expense->remaining_amount > 0)
                                    <form class="mt-2" method="POST" action="{{ route('expenses.payments.store', [$category, $expense]) }}">
                                        @csrf
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <label class="visually-hidden" for="payment-amount-{{ $expense->id }}">Payment amount for {{ $expense->name }}</label>
                                                <input class="form-control form-control-sm" id="payment-amount-{{ $expense->id }}" name="amount"
                                                    type="number" min="0.01" max="{{ number_format($expense->remaining_amount, 2, '.', '') }}" step="0.01"
                                                    value="{{ number_format($expense->remaining_amount, 2, '.', '') }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="visually-hidden" for="payment-date-{{ $expense->id }}">Payment date for {{ $expense->name }}</label>
                                                <input class="form-control form-control-sm" id="payment-date-{{ $expense->id }}" name="paid_at"
                                                    type="date" value="{{ now()->toDateString() }}" required>
                                            </div>
                                            <div class="col-8">
                                                <label class="visually-hidden" for="payment-reference-{{ $expense->id }}">Payment reference for {{ $expense->name }}</label>
                                                <input class="form-control form-control-sm" id="payment-reference-{{ $expense->id }}" name="reference" maxlength="255" placeholder="Reference (optional)">
                                            </div>
                                            <div class="col-4 d-grid">
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Record payment</button>
                                            </div>
                                        </div>
                                    </form>
                                @endif
                                <details class="mt-2">
                                    <summary>{{ $expense->attachments->count() }} file(s) and references</summary>
                                    <form class="mt-2" method="POST" action="{{ route('expenses.attachments.store', [$category, $expense]) }}" enctype="multipart/form-data">
                                        @csrf
                                        <label class="form-label small" for="expense-documents-{{ $expense->id }}">PDFs or pictures</label>
                                        <input class="form-control form-control-sm" id="expense-documents-{{ $expense->id }}"
                                            name="documents[]" type="file" accept=".pdf,.jpg,.jpeg,.png,.webp,application/pdf,image/jpeg,image/png,image/webp"
                                            multiple required>
                                        <label class="form-label small mt-2" for="expense-caption-{{ $expense->id }}">Caption</label>
                                        <div class="input-group input-group-sm">
                                            <input class="form-control" id="expense-caption-{{ $expense->id }}" name="caption"
                                                maxlength="255" placeholder="Bill, receipt, or renewal proof">
                                            <button class="btn btn-outline-primary" type="submit">Upload</button>
                                        </div>
                                        <div class="form-text">Up to 8 PDF, JPG, PNG, or WebP files, up to 10 MB each.</div>
                                    </form>
                                    <div class="mt-3">
                                        @include('attachments.gallery', [
                                            'attachments' => $expense->attachments,
                                            'emptyMessage' => 'No files attached yet.',
                                            'lightbox' => true,
                                            'editableCaptions' => true,
                                            'galleryName' => 'expense-'.$expense->id,
                                            'captionRoute' => 'expenses.attachments.caption',
                                            'captionRouteParameters' => [$category, $expense],
                                        ])
                                    </div>
                                </details>
                            </td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('expenses.edit', [$category, $expense]) }}">Edit</a>
                                <form class="d-inline" method="POST" action="{{ route('expenses.destroy', [$category, $expense]) }}"
                                    onsubmit="return confirm('Delete this item and its payment history?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-body-secondary py-4">No {{ strtolower($categoryLabel) }} items yet. Add an item to track its due date and payments.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('vendors/glightbox/glightbox.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.GLightbox) {
                window.GLightbox({ selector: '.attachment-lightbox' });
            }
        });
    </script>
@endpush
