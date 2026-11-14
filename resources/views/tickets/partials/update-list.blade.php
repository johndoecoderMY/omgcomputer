<div class="list-group list-group-flush">
    @forelse ($ticket->updates as $update)
        <article class="list-group-item p-3">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h3 class="h6 mb-1">{{ $update->title }}</h3>
                    <x-status-badge :status="$update->status"
                        :label="$statuses[$update->status] ?? ucfirst($update->status)" />
                </div>
                <div class="small text-body-secondary text-end">
                    {{ $update->created_at->format('M j, Y g:i A') }}<br>{{ $update->user?->name ?? 'System' }}
                </div>
            </div>
            @unless (auth()->user()->hasRole('customer'))
                <button class="btn btn-sm btn-outline-primary mt-2" type="button"
                    data-bs-toggle="modal" data-bs-target="#ticket-step-photo-modal-{{ $update->id }}"
                    aria-label="Add photo to {{ $update->title }}">
                    <span class="bi bi-camera me-1" aria-hidden="true"></span>Add photo
                </button>
            @endunless
            @if ($update->notes)
                <p class="text-pre-line mt-2 mb-0">{{ $update->notes }}</p>
            @endif
            @if ($update->customer_details)
                <div class="mt-2">
                    <div class="small fw-semibold text-body-secondary">Customer-facing update</div>
                    <p class="text-pre-line mb-0">{{ $update->customer_details }}</p>
                </div>
            @endif
            @if ($update->attachments->isNotEmpty())
                <div id="ticket-step-photos-{{ $update->id }}"
                    class="carousel slide ticket-step-photo-carousel mt-3"
                    data-ticket-step-carousel data-bs-touch="true" data-bs-interval="false">
                    <div class="carousel-inner rounded border bg-body-tertiary">
                        @foreach ($update->attachments->sortBy('id')->values() as $attachment)
                            @php($photoCaption = $attachment->caption ?: $attachment->original_name)
                            <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                <a class="attachment-lightbox d-block"
                                    href="{{ route('attachments.show', $attachment) }}"
                                    data-type="image"
                                    data-gallery="ticket-step-{{ $update->id }}"
                                    data-description="{{ $photoCaption }}">
                                    <img class="ticket-step-photo-image" src="{{ route('attachments.show', $attachment) }}"
                                        alt="{{ $photoCaption }}" loading="lazy">
                                </a>
                                <div class="ticket-step-photo-caption text-center p-2">
                                    <div class="small">{{ $photoCaption }}</div>
                                    <div class="small text-body-secondary">Photo {{ $loop->iteration }} of {{ $loop->count }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if ($update->attachments->count() > 1)
                        <button class="carousel-control-prev" type="button"
                            data-bs-target="#ticket-step-photos-{{ $update->id }}" data-bs-slide="prev"
                            aria-label="Previous photo">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous photo</span>
                        </button>
                        <button class="carousel-control-next" type="button"
                            data-bs-target="#ticket-step-photos-{{ $update->id }}" data-bs-slide="next"
                            aria-label="Next photo">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next photo</span>
                        </button>
                    @endif
                </div>
            @endif
        </article>
        @unless (auth()->user()->hasRole('customer'))
            <div class="modal fade" id="ticket-step-photo-modal-{{ $update->id }}" tabindex="-1"
                aria-labelledby="ticket-step-photo-title-{{ $update->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form id="ticket-step-photo-form-{{ $update->id }}" method="POST"
                            action="{{ route('tickets.steps.attachments.store', [$ticket, $update]) }}"
                            enctype="multipart/form-data" data-ticket-ajax-form data-ticket-step-photo-form>
                            @csrf
                            <div class="modal-header">
                                <div>
                                    <h2 class="modal-title fs-5" id="ticket-step-photo-title-{{ $update->id }}">Add photo to repair step</h2>
                                    <div class="small text-body-secondary">{{ $update->title }}</div>
                                </div>
                                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="alert alert-danger d-none" role="alert" data-ticket-form-error></div>
                                <div class="mb-3">
                                    <label class="form-label" for="ticket-step-photo-file-{{ $update->id }}">Photo</label>
                                    <x-image-upload-input :id="'ticket-step-photo-file-'.$update->id" name="photos[]" :required="true" />
                                </div>
                                <div>
                                    <label class="form-label" for="ticket-step-photo-caption-{{ $update->id }}">Caption</label>
                                    <input class="form-control" id="ticket-step-photo-caption-{{ $update->id }}" name="caption"
                                        maxlength="255" placeholder="Describe this photo" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-primary" type="submit">Add photo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endunless
    @empty
        <div class="list-group-item text-body-secondary">No repair steps recorded.</div>
    @endforelse
</div>

@once
    @push('styles')
        <style>
            .ticket-step-photo-image {
                display: block;
                width: 100%;
                height: clamp(14rem, 55vw, 28rem);
                object-fit: contain;
                background: var(--falcon-tertiary-bg);
            }

            .ticket-step-photo-caption {
                overflow-wrap: anywhere;
            }

            .ticket-step-photo-carousel .carousel-control-prev-icon,
            .ticket-step-photo-carousel .carousel-control-next-icon {
                width: 2.25rem;
                height: 2.25rem;
                border-radius: 50%;
                background-color: rgba(0, 0, 0, .65);
                background-size: 55% 55%;
            }
        </style>
    @endpush
@endonce
