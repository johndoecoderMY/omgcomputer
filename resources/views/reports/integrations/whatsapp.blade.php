@extends('layouts.app')

@section('title', 'WhatsApp API Test')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="WhatsApp Cloud API"
        description="Verify your Meta access token and WhatsApp phone-number ID by reading the registered phone profile."
        :back-url="route('reports.marketing')" back-label="Marketing & WhatsApp" />

    <div class="row g-3">
        <div class="col-12 col-xl-5">
            <div class="card h-100 p-2">
                <div class="card-header bg-body d-flex align-items-center gap-3">
                    <span class="fab fa-whatsapp fs-2 text-success" aria-hidden="true"></span>
                    <div>
                        <h2 class="h5 mb-0 text-primary">Connection setup</h2>
                        <span class="small text-body-secondary">Graph API {{ $apiVersion }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span>Access token <code>META_ACCESS_TOKEN</code></span>
                            <x-status-badge :status="filled(config('services.meta.access_token')) ? 'configured' : 'missing'" :label="filled(config('services.meta.access_token')) ? 'Configured' : 'Missing'" />
                        </li>
                        <li class="list-group-item px-0 d-flex justify-content-between">
                            <span>Phone number ID <code>META_WHATSAPP_PHONE_NUMBER_ID</code></span>
                            <x-status-badge :status="$phoneNumberIdConfigured ? 'configured' : 'missing'" :label="$phoneNumberIdConfigured ? 'Configured' : 'Missing'" />
                        </li>
                    </ul>
                    <form method="POST" action="{{ route('reports.integrations.whatsapp.check') }}">
                        @csrf
                        <button class="btn btn-success" type="submit" @disabled(! $whatsappReady)>
                            <span class="fas fa-plug me-2" aria-hidden="true"></span>Test connection
                        </button>
                    </form>
                    @unless ($whatsappReady)
                        <p class="small text-body-secondary mt-3 mb-0">
                            Set the missing values in the server environment, then clear Laravel's cached configuration before testing.
                        </p>
                    @endunless
                </div>
                <div class="card-footer bg-body small text-body-secondary">
                    This makes a read-only phone-profile request to Meta. It does not send a WhatsApp message.
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card h-100 p-2">
                <div class="card-header bg-body">
                    <h2 class="h5 mb-0 text-primary">Test result</h2>
                </div>
                <div class="card-body">
                    @if ($result = session('integration_result'))
                        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
                            <strong>{{ $result['success'] ? 'Connection successful' : 'Connection not verified' }}</strong>
                            <div>{{ $result['message'] }}</div>
                        </div>
                        @if ($result['success'] && ! empty($result['phone']))
                            <dl class="row mb-0">
                                @foreach ([
                                    'display_phone_number' => 'Phone number',
                                    'verified_name' => 'Verified business name',
                                    'quality_rating' => 'Quality rating',
                                    'code_verification_status' => 'Verification status',
                                    'platform_type' => 'Platform',
                                    'id' => 'Phone number ID',
                                ] as $key => $label)
                                    @if (! empty($result['phone'][$key]))
                                        <dt class="col-sm-5">{{ $label }}</dt>
                                        <dd class="col-sm-7">{{ $result['phone'][$key] }}</dd>
                                    @endif
                                @endforeach
                            </dl>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <span class="fas fa-mobile-screen-button fs-1 text-body-secondary mb-3" aria-hidden="true"></span>
                            <p class="text-body-secondary mb-0">Run the test to see the WhatsApp phone profile returned by Meta.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
