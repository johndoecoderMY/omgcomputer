<div class="row g-3">
    @forelse ($attachments as $attachment)
        <div class="col-6 col-md-4 col-xl-3">
            <figure class="attachment-preview mb-0">
                @if (str_starts_with($attachment->mime_type, 'image/'))
                    <a href="{{ route('attachments.show', $attachment) }}"
                        @if ($lightbox ?? false)
                            class="attachment-lightbox"
                            data-type="image"
                            data-gallery="{{ $galleryName ?? 'task-photos' }}"
                            data-description="{{ $attachment->caption ?: $attachment->original_name }}"
                        @else
                            target="_blank" rel="noopener"
                        @endif
                    >
                        <img src="{{ route('attachments.show', $attachment) }}" alt="{{ $attachment->caption ?: $attachment->original_name }}" loading="lazy">
                    </a>
                @else
                    <a class="attachment-file d-flex flex-column align-items-center justify-content-center gap-2 text-decoration-none text-body"
                        href="{{ route('attachments.show', $attachment) }}" target="_blank" rel="noopener">
                        <span class="fas fa-file-pdf fs-3 text-danger" aria-hidden="true"></span>
                        <span class="small">Open PDF</span>
                    </a>
                @endif
                <figcaption class="small mt-2 text-body-secondary">{{ $attachment->caption ?: $attachment->original_name }}</figcaption>
                @if ($editableCaptions ?? false)
                    <form class="mt-2" method="POST"
                        action="{{ route($captionRoute ?? 'tasks.attachments.caption', array_merge($captionRouteParameters ?? [$task], [$attachment])) }}">
                        @csrf
                        @method('PATCH')
                        <label class="visually-hidden" for="attachment-caption-{{ $attachment->id }}">Caption for {{ $attachment->original_name }}</label>
                        <div class="input-group input-group-sm">
                            <input class="form-control" id="attachment-caption-{{ $attachment->id }}" name="caption"
                                maxlength="255" value="{{ $attachment->caption }}" placeholder="Add a caption">
                            <button class="btn btn-outline-secondary" type="submit" aria-label="Save caption">
                                <span class="fas fa-save" aria-hidden="true"></span>
                            </button>
                        </div>
                        @error('caption')
                            <div class="small text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </form>
                @endif
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
            .attachment-preview .attachment-file{min-height:7rem;border:1px solid var(--falcon-border-color);border-radius:4px;background:var(--falcon-tertiary-bg)}
            .attachment-preview figcaption{overflow-wrap:anywhere}
        </style>
    @endpush
@endonce