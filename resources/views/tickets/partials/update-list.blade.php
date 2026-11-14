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
            @if ($update->notes)
                <p class="text-pre-line mt-2 mb-0">{{ $update->notes }}</p>
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
