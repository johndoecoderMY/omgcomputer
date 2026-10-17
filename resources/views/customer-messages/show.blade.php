@extends('layouts.app')

@section('title', $template->name)

@section('content')
<div class="container-fluid py-3">
    <x-page-header :eyebrow="$eyebrow" :title="$template->name" :back-url="route('customer-messages.index')" back-label="All templates"><x-slot name="actions"><a class="btn btn-outline-primary" href="{{ route('customer-messages.edit', $template) }}"><span class="fas fa-pen me-2"></span>Edit</a><form method="POST" action="{{ route('customer-messages.destroy', $template) }}" onsubmit="return confirm('Delete this message template?')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><span class="fas fa-trash me-2"></span>Delete</button></form></x-slot></x-page-header>
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="alert alert-info">Preview values are examples. Replace them with the customer's actual details before sending. WhatsApp opens a draft only; review it and press Send yourself.</div>
    <div class="row g-3"><div class="col-xl-8"><div class="card">-2"><div class="card-header d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Message preview</h2><span class="badge text-bg-{{ $template->is_active ? 'success' : 'secondary' }}">{{ $template->is_active ? 'Available' : 'Archived' }}</span></div><div class="card-body">
        @if ($subjectPreview)<div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><div class="small text-body-secondary">Subject</div><div id="preview-subject" class="fw-semibold">{{ $subjectPreview }}</div></div><button class="btn btn-sm btn-outline-secondary copy-button" type="button" data-copy-target="preview-subject"><span class="fas fa-copy me-1"></span>Copy subject</button></div>@endif
        <div class="d-flex justify-content-between align-items-start gap-3"><div><div class="small text-body-secondary">{{ \App\Models\CustomerMessageTemplate::CHANNELS[$template->channel] }} copy</div><pre id="preview-body" class="message-template-preview mb-0">{{ $bodyPreview }}</pre></div><button class="btn btn-sm btn-outline-success copy-button flex-shrink-0" type="button" data-copy-target="preview-body"><span class="fas fa-copy me-1"></span>Copy for WhatsApp</button></div>
        <pre id="preview-complete" class="visually-hidden">{{ $subjectPreview ? $subjectPreview . "\n\n" : '' }}{{ $bodyPreview }}</pre>
        <div class="mt-3 d-flex flex-wrap align-items-center gap-2"><button class="btn btn-primary copy-button" type="button" data-copy-target="preview-complete"><span class="fas fa-copy me-2"></span>Copy complete message</button><a class="btn btn-success" href="https://wa.me/?text={{ rawurlencode($bodyPreview) }}" target="_blank" rel="noopener"><span class="fab fa-whatsapp me-2"></span>Open WhatsApp draft</a><span class="small text-success" id="copy-feedback" aria-live="polite"></span></div>
    </div></div></div>
        <aside class="col-xl-4"><div class="card">-2"><div class="card-header fw-semibold">Placeholders</div><div class="list-group list-group-flush">@foreach ($placeholders as $key => $label)<div class="list-group-item"><code>&#123;&#123;{{ $key }}&#125;&#125;</code><div class="small text-body-secondary">{{ $label }}</div></div>@endforeach</div></div></aside>
    </div>
    <div class="card mt-3">-2"><div class="card-header fw-semibold">Reference photos</div><form class="card-body border-bottom" method="POST" action="{{ route('customer-messages.attachments.store', $template) }}" enctype="multipart/form-data">@csrf<div class="row g-2 align-items-end"><div class="col-md-5"><label class="form-label" for="copy-photos">Photos</label><input class="form-control" id="copy-photos" name="photos[]" type="file" accept="image/jpeg,image/png,image/webp" multiple required></div><div class="col-md-5"><label class="form-label" for="copy-caption">Caption</label><input class="form-control" id="copy-caption" name="caption" maxlength="255" placeholder="Context for this wording"></div><div class="col-md-2 d-grid"><button class="btn btn-outline-primary">Upload</button></div></div><div class="form-text">Private reference images for this customer communication template.</div></form><div class="card-body">@include('attachments.gallery', ['attachments' => $template->attachments, 'emptyMessage' => 'No reference photos added.'])</div></div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.copy-button').forEach((button) => button.addEventListener('click', async () => {
    const content = document.getElementById(button.dataset.copyTarget)?.textContent || '';
    try {
        await navigator.clipboard.writeText(content.trim());
        document.getElementById('copy-feedback').textContent = 'Copied';
    } catch {
        const field = document.createElement('textarea');
        field.value = content.trim();
        document.body.append(field);
        field.select();
        document.execCommand('copy');
        field.remove();
        document.getElementById('copy-feedback').textContent = 'Copied';
    }
}));
</script>
<style>
.message-template-preview{font:inherit;white-space:pre-wrap;overflow-wrap:anywhere;color:inherit;background:var(--falcon-body-bg,#fff);border:1px solid var(--falcon-border-color,#d8e0e8);border-radius:4px;padding:1rem;max-width:100%}
</style>
@endpush