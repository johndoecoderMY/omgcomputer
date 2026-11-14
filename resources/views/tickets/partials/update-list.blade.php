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
                <div class="mt-3">
                    @include('attachments.gallery', [
                        'attachments' => $update->attachments,
                        'emptyMessage' => '',
                        'lightbox' => true,
                        'galleryName' => 'ticket-step-'.$update->id,
                    ])
                </div>
            @endif
        </article>
    @empty
        <div class="list-group-item text-body-secondary">No repair steps recorded.</div>
    @endforelse
</div>
