@extends('layouts.app')

@section('title', 'Email Layout Preview')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="Email layout preview"
        description="Preview the quotation email customers receive, including the accept and reject actions."
        :back-url="route('reports.integrations.notifications')" back-label="Email tests" />

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-body-secondary small mb-1">Preview document</div>
                    <div class="fw-semibold">{{ $document->number }}</div>
                    <div>{{ $document->customer_name }}</div>
                    <div class="small text-body-secondary">Total: RM {{ number_format((float) $document->total, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge text-bg-success"><span class="bi bi-check-circle me-1"></span>Accept action</span>
                    <span class="badge text-bg-danger"><span class="bi bi-x-circle me-1"></span>Reject action</span>
                    <span class="badge text-bg-light border text-dark">PDF attachment</span>
                    <span class="small text-body-secondary ms-md-auto">This uses sample content only. No email is sent.</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Quotation email</h2>
            <div class="btn-group" role="group" aria-label="Preview width">
                <button class="btn btn-sm btn-outline-secondary active" type="button" data-preview-width="100%">Desktop</button>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-preview-width="390px">Mobile</button>
            </div>
        </div>
        <div class="bg-body-tertiary rounded border p-3 overflow-auto">
            <iframe id="quotation-email-preview" title="Quotation email preview" class="bg-white border rounded mx-auto d-block" style="width:100%;max-width:100%;min-height:620px;border:0"></iframe>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const frame = document.getElementById('quotation-email-preview');
    const html = @json($emailHtml);
    frame.srcdoc = html;

    document.querySelectorAll('[data-preview-width]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-preview-width]').forEach((item) => item.classList.remove('active'));
            button.classList.add('active');
            frame.style.width = button.dataset.previewWidth;
        });
    });
})();
</script>
@endpush
