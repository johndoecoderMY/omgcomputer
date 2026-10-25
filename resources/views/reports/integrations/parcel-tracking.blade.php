@extends('layouts.app')

@section('title', 'Parcel Tracker')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="Parcel Tracker"
        description="Track shipments with a tracking number across couriers supported by 17TRACK."
        :back-url="route('reports.index')" back-label="Reports overview">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="https://api.17track.net/en/doc" target="_blank" rel="noopener noreferrer">
                <span class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></span>17TRACK API documentation
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="alert alert-info" role="status">
        <strong>Tracking only:</strong> parcel status is retrieved from 17TRACK. This tool does not create shipments or buy labels.
        Tracking numbers are sent to 17TRACK from the server; the API key is never shown on this page.
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card h-100 p-2">
                <div class="card-header bg-body">
                    <h2 class="h5 mb-0 text-primary">API connection</h2>
                </div>
                <div class="card-body">
                    <p>Provider: <strong>17TRACK</strong></p>
                    <p class="mb-3">
                        API key:
                        <x-status-badge :status="$apiKeyConfigured ? 'configured' : 'missing'" :label="$apiKeyConfigured ? 'Configured' : 'Missing'" />
                    </p>
                    @unless ($apiKeyConfigured)
                        <div class="alert alert-warning mb-0">
                            Add <code>TRACK17_API_KEY</code> to the server environment. 17TRACK documents a one-time free allocation for eligible new API accounts; it is not a recurring monthly free quota.
                        </div>
                    @endunless
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card p-2">
                <div class="card-header bg-body">
                    <h2 class="h5 mb-0 text-primary">Track a parcel</h2>
                    <p class="small text-body-secondary mb-0">Enter a tracking number. 17TRACK will try to identify the courier automatically.</p>
                </div>
                <form method="POST" action="{{ route('reports.integrations.tracking.lookup') }}">
                    @csrf
                    <div class="card-body">
                        <label class="form-label" for="tracking_number">Tracking number</label>
                        <input class="form-control @error('tracking_number') is-invalid @enderror" id="tracking_number"
                            name="tracking_number" value="{{ old('tracking_number') }}" maxlength="100"
                            autocomplete="off" required>
                        @error('tracking_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="card-footer bg-body">
                        <button class="btn btn-primary" type="submit" @disabled(! $apiKeyConfigured)>
                            <span class="bi bi-search me-2" aria-hidden="true"></span>Track parcel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($result = session('parcel_tracking_result'))
            <div class="col-12">
                <div class="card p-2">
                    <div class="card-header bg-body">
                        <h2 class="h5 mb-0 text-primary">Tracking result</h2>
                    </div>
                    <div class="card-body">
                        @if ($result['success'])
                            @php($tracking = $result['tracking'])
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                                <div>
                                    <p class="small text-body-secondary mb-1">Tracking number</p>
                                    <p class="h5 mb-0">{{ $tracking['number'] }}</p>
                                </div>
                                <span class="badge text-bg-primary fs-9">{{ $tracking['status'] }}</span>
                            </div>
                            @if ($tracking['carrier'])
                                <p><strong>Courier:</strong> {{ $tracking['carrier'] }}</p>
                            @endif
                            @if ($tracking['latest_update'])
                                <p class="mb-3"><strong>Latest update:</strong> {{ $tracking['latest_update'] }}</p>
                            @endif
                            @if ($tracking['status_description'] || $tracking['latest_event'])
                                <p class="mb-3">{{ $tracking['status_description'] ?? $tracking['latest_event'] }}</p>
                            @endif

                            @if ($tracking['events'] !== [])
                                <h3 class="h6 mt-4">Tracking history</h3>
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
                                <p class="text-body-secondary mb-0">No courier scan events are available yet. Try tracking again later.</p>
                            @endif
                        @else
                            <div class="alert alert-danger mb-0" role="alert">{{ $result['message'] }}</div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
