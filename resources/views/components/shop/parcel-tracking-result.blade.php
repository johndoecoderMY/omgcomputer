@props(['tracking'])

<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h5 mb-0">Tracking updates</h2>
        <span class="badge text-bg-primary">{{ $tracking['status'] }}</span>
    </div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-4">Tracking number</dt>
            <dd class="col-sm-8 text-break">{{ $tracking['number'] }}</dd>
            @if ($tracking['carrier'])
                <dt class="col-sm-4">Courier</dt>
                <dd class="col-sm-8">{{ $tracking['carrier'] }}</dd>
            @endif
            @if ($tracking['latest_update'])
                <dt class="col-sm-4">Latest update</dt>
                <dd class="col-sm-8">{{ $tracking['latest_update'] }}</dd>
            @endif
        </dl>
        @if ($tracking['status_description'] || $tracking['latest_event'])
            <p class="mb-0">{{ $tracking['status_description'] ?? $tracking['latest_event'] }}</p>
        @endif

        @if ($tracking['events'] !== [])
            <h3 class="h6 mt-4">Shipment history</h3>
            <div class="list-group list-group-flush">
                @foreach ($tracking['events'] as $event)
                    <div class="list-group-item px-0">
                        @if (! empty($event['time']))
                            <time class="small text-body-secondary d-block">{{ $event['time'] }}</time>
                        @endif
                        @if (! empty($event['location']))
                            <span class="fw-semibold">{{ $event['location'] }}</span>
                        @endif
                        @if (! empty($event['description']))
                            <p class="mb-0">{{ $event['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-body-secondary mt-3 mb-0">No courier scan events are available yet. Try again later.</p>
        @endif
    </div>
</div>
