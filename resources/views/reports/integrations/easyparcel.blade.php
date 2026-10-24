@extends('layouts.app')

@section('title', 'EasyParcel Rate Checker')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="EasyParcel Rate Checker"
        description="Request courier quotations for a Malaysian parcel using your EasyParcel account."
        :back-url="route('reports.index')" back-label="Reports overview">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="https://developers.easyparcel.com/" target="_blank" rel="noopener noreferrer">
                <span class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></span>API documentation
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="alert alert-info" role="status">
        <strong>Safe rate check:</strong> this requests quotations only. It does not create a shipment, buy a label, or charge your account.
        The API key is sent directly from the server to EasyParcel over HTTPS and is never shown on this page.
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card h-100 p-2">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0 text-primary">Connection settings</h2>
                    <x-status-badge :status="$environment" :label="ucfirst($environment)" />
                </div>
                <div class="card-body">
                    <p class="mb-3">API endpoint: <code>{{ $apiHost }}</code></p>
                    <p class="mb-3">
                        Authentication:
                        <x-status-badge :status="$authenticationConfigured ? 'configured' : 'missing'" :label="$authenticationConfigured ? 'Configured' : 'Missing'" />
                    </p>
                    <p class="mb-3">
                        API key:
                        <x-status-badge :status="$apiKeyConfigured ? 'configured' : 'missing'" :label="$apiKeyConfigured ? 'Configured' : 'Missing'" />
                    </p>
                    @unless ($apiKeyConfigured)
                        <div class="alert alert-warning mb-0">
                            Set both <code>EASYPARCEL_AUTHENTICATION</code> and <code>EASYPARCEL_API_KEY</code> in the server environment before checking rates.
                            The sandbox endpoint is used by default unless production is selected.
                        </div>
                    @endunless
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card p-2">
                <div class="card-header bg-body">
                    <h2 class="h5 mb-0 text-primary">Parcel route</h2>
                    <p class="small text-body-secondary mb-0">Enter five-digit Malaysian postcodes and EasyParcel state codes.</p>
                </div>
                <form method="POST" action="{{ route('reports.integrations.easyparcel.rates') }}">
                    @csrf
                    <div class="card-body row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="pick_code">Pickup postcode</label>
                            <input class="form-control @error('pick_code') is-invalid @enderror" id="pick_code" name="pick_code"
                                inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ old('pick_code') }}" required>
                            @error('pick_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="pick_state">Pickup state</label>
                            <select class="form-select @error('pick_state') is-invalid @enderror" id="pick_state" name="pick_state" required>
                                <option value="">Choose a state</option>
                                @foreach ($states as $code => $label)
                                    <option value="{{ $code }}" @selected(old('pick_state') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('pick_state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="send_code">Destination postcode</label>
                            <input class="form-control @error('send_code') is-invalid @enderror" id="send_code" name="send_code"
                                inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ old('send_code') }}" required>
                            @error('send_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="send_state">Destination state</label>
                            <select class="form-select @error('send_state') is-invalid @enderror" id="send_state" name="send_state" required>
                                <option value="">Choose a state</option>
                                @foreach ($states as $code => $label)
                                    <option value="{{ $code }}" @selected(old('send_state') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('send_state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="weight">Parcel weight (kg)</label>
                            <input class="form-control @error('weight') is-invalid @enderror" id="weight" name="weight"
                                type="number" min="0.01" max="999999.99" step="0.01" value="{{ old('weight', '1.00') }}" required>
                            @error('weight')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="card-footer bg-body">
                        <button class="btn btn-primary" type="submit" @disabled(! ($authenticationConfigured && $apiKeyConfigured))>
                            <span class="bi bi-search me-2" aria-hidden="true"></span>Check courier rates
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if ($result = session('easyparcel_result'))
            <div class="col-12">
                <div class="card p-2">
                    <div class="card-header bg-body">
                        <h2 class="h5 mb-0 text-primary">Rate lookup result</h2>
                    </div>
                    <div class="card-body">
                        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
                            {{ $result['message'] }}
                        </div>
                        @if ($result['success'])
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 table-bordered">
                                    <thead><tr><th>Courier</th><th>Service</th><th>Delivery estimate</th><th class="text-end">Rate</th></tr></thead>
                                    <tbody>
                                        @forelse ($result['rates'] as $rate)
                                            <tr>
                                                <td>{{ $rate['courier_name'] }}</td>
                                                <td>{{ $rate['service_name'] ?? $rate['service_detail'] ?? '—' }}</td>
                                                <td>{{ $rate['delivery'] ?? '—' }}</td>
                                                <td class="text-end">{{ $rate['price'] !== null ? 'RM '.number_format($rate['price'], 2) : '—' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-body-secondary py-4">EasyParcel reported success but returned no courier rates for this route.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
