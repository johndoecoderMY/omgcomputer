@extends('layouts.app')

@section('title', 'Email Layout Preview')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="Email layout preview"
        description="Preview the quotation email customers receive, including the accept and reject actions."
        :back-url="route('reports.integrations.notifications')" back-label="Email tests" />

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <h2 class="h5 mb-1">Choose an email layout</h2>
                <div class="small text-body-secondary">All customer and account emails use the shared OMG Computer layout.</div>
            </div>
            <span class="badge text-bg-light border text-dark">Sample content only</span>
        </div>
        <div class="card-body">
            <div class="row g-2" id="email-layout-options">
                @foreach ($previews as $key => $preview)
                    <div class="col-sm-4">
                        <button class="btn w-100 text-start border {{ $loop->first ? 'btn-primary' : 'btn-outline-secondary' }}" type="button" data-email-preview="{{ $key }}">
                            <span class="fw-semibold d-block">{{ $preview['label'] }}</span>
                            <span class="small d-block {{ $loop->first ? '' : 'text-body-secondary' }} text-truncate">{{ $preview['subject'] }}</span>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header bg-body-tertiary d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div>
                <div class="small text-body-secondary">Subject</div>
                <div class="fw-semibold" id="email-preview-subject">{{ $previews['quotation']['subject'] }}</div>
            </div>
            <div class="btn-group" role="group" aria-label="Preview width">
                <button class="btn btn-sm btn-outline-secondary active" type="button" data-preview-width="100%">
                    <span class="bi bi-display me-1"></span>Desktop
                </button>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-preview-width="390px">
                    <span class="bi bi-phone me-1"></span>Mobile
                </button>
            </div>
        </div>
        <div class="bg-body-secondary-subtle p-3 p-md-4 overflow-auto">
            <div class="mx-auto" style="max-width:100%" id="email-preview-shell">
                <iframe id="quotation-email-preview" title="Quotation email preview" class="bg-white border-0 rounded-3 mx-auto d-block" style="width:100%;max-width:100%;height:780px;"></iframe>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const frame = document.getElementById('quotation-email-preview');
    const previews = @json($previews);
    const subject = document.getElementById('email-preview-subject');
    frame.srcdoc = previews.quotation.html;

    document.querySelectorAll('[data-email-preview]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelectorAll('[data-email-preview]').forEach((item) => {
                item.classList.remove('btn-primary');
                item.classList.add('btn-outline-secondary');
            });
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-primary');

            const preview = previews[button.dataset.emailPreview];
            frame.srcdoc = preview.html;
            subject.textContent = preview.subject;
        });
    });

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
