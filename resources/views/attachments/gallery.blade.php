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
                        <span class="bi bi-filetype-pdf fs-3 text-danger" aria-hidden="true"></span>
                        <span class="small">Open PDF</span>
                    </a>
                @endif
                <figcaption class="small mt-2 text-body-secondary">{{ $attachment->caption ?: $attachment->original_name }}</figcaption>
                @if ($editableCaptions ?? false)
                    @php
                        $captionAction = route($captionRoute ?? 'tasks.attachments.caption', array_merge($captionRouteParameters ?? [$task], [$attachment]));
                        $captionModalId = 'attachment-caption-modal-'.($galleryName ?? 'gallery').'-'.$attachment->id;
                    @endphp
                    @if ($captionEditorModal ?? false)
                        <button class="btn btn-sm btn-outline-secondary mt-2" type="button"
                            data-bs-toggle="modal" data-bs-target="#{{ $captionModalId }}">
                            <span class="bi bi-pencil me-1" aria-hidden="true"></span>Edit caption
                        </button>
                        <div class="modal fade" id="{{ $captionModalId }}" tabindex="-1"
                            aria-labelledby="{{ $captionModalId }}-title" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <form method="POST" action="{{ $captionAction }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="modal-header">
                                            <div>
                                                <h2 class="modal-title h5" id="{{ $captionModalId }}-title">Edit photo caption</h2>
                                                <div class="small text-body-secondary">{{ $attachment->original_name }}</div>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            @include('attachments._caption-fields', ['attachment' => $attachment])
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                            <button class="btn btn-primary" type="submit">Save caption</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <form class="mt-2" method="POST" action="{{ $captionAction }}">
                            @csrf
                            @method('PATCH')
                            <label class="visually-hidden" for="attachment-caption-{{ $attachment->id }}">Caption for {{ $attachment->original_name }}</label>
                            <div class="input-group input-group-sm">
                                <input class="form-control" id="attachment-caption-{{ $attachment->id }}" name="caption"
                                    maxlength="255" value="{{ $attachment->caption }}" placeholder="Add a caption">
                                <button class="btn btn-outline-secondary" type="submit" aria-label="Save caption">
                                    <span class="bi bi-floppy" aria-hidden="true"></span>
                                </button>
                            </div>
                            @error('caption')
                                <div class="small text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </form>
                    @endif
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