<div class="row g-3">
    @forelse ($attachments as $attachment)
        <div class="col-6 col-md-4 col-xl-3">
            <figure class="attachment-preview mb-0">
                <a href="{{ route('attachments.show', $attachment) }}" target="_blank" rel="noopener">
                    <img src="{{ route('attachments.show', $attachment) }}" alt="{{ $attachment->caption ?: $attachment->original_name }}" loading="lazy">
                </a>
                <figcaption class="small mt-2 text-body-secondary">{{ $attachment->caption ?: $attachment->original_name }}</figcaption>
            </figure>
        </div>
    @empty
        <div class="col-12"><p class="small text-body-secondary mb-0">{{ $emptyMessage }}</p></div>
    @endforelse
</div>

@once
    @push('styles')
        <style>
            .attachment-preview img{display:block;width:100%;aspect-ratio:4/3;object-fit:cover;border:1px solid var(--falcon-border-color);border-radius:4px;background:var(--falcon-tertiary-bg)}
            .attachment-preview figcaption{overflow-wrap:anywhere}
        </style>
    @endpush
@endonce