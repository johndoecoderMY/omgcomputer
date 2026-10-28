@extends('layouts.app')

@section('title', 'CHIP Collect Test')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="CHIP Collect"
        description="Verify server-side CHIP Collect configuration without creating a customer payment."
        :back-url="route('reports.index')" back-label="Reports overview" />

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card h-100 p-2">
                <div class="card-header bg-body d-flex align-items-center gap-3">
                    <span class="bi bi-credit-card fs-2 text-primary" aria-hidden="true"></span>
                    <h2 class="h5 mb-0 text-primary">Connection configuration</h2>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">API URL</dt>
                        <dd class="col-7 text-break"><code>{{ $apiUrl }}</code></dd>
                        <dt class="col-5">Currency</dt>
                        <dd class="col-7">{{ $currency }}</dd>
                        <dt class="col-5">Secret key</dt>
                        <dd class="col-7"><x-status-badge :status="$secretKeyConfigured ? 'configured' : 'missing'" :label="$secretKeyConfigured ? 'Configured' : 'Missing'" /></dd>
                        <dt class="col-5">Brand ID</dt>
                        <dd class="col-7"><x-status-badge :status="$brandIdConfigured ? 'configured' : 'missing'" :label="$brandIdConfigured ? 'Configured' : 'Missing'" /></dd>
                    </dl>
                </div>
                <div class="card-footer bg-body small text-body-secondary">
                    Credentials are read from the server environment and are never displayed here.
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card h-100 p-2">
                <div class="card-header bg-body">
                    <h2 class="h5 mb-0 text-primary">Test API connection</h2>
                </div>
                <div class="card-body">
                    @if ($result = session('chip_result'))
                        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
                            <strong>{{ $result['success'] ? 'Connection successful' : 'Connection not verified' }}:</strong>
                            {{ $result['message'] }}
                        </div>
                    @else
                        <p>CHIP will be asked for its callback public key. This read-only check does not create a purchase or record a payment.</p>
                    @endif
                </div>
                <div class="card-footer bg-body">
                    <form method="POST" action="{{ route('reports.integrations.chip.check') }}">
                        @csrf
                        <button class="btn btn-primary" type="submit" @disabled(! $chipConfigured)>
                            <span class="bi bi-arrow-repeat me-2" aria-hidden="true"></span>Test connection
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
