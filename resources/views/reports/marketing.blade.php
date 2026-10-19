@extends('layouts.app')

@section('title', 'Marketing & WhatsApp')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Growth" title="Marketing & WhatsApp"
        description="Review campaign performance and check your WhatsApp Cloud API connection."
        :back-url="route('reports.index')" back-label="All reports">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('customer-messages.index') }}">
                <span class="fab fa-whatsapp me-2" aria-hidden="true"></span>Message templates
            </a>
            @role('admin')
                <a class="btn btn-primary" href="{{ route('reports.integrations.whatsapp') }}">
                    <span class="fas fa-plug me-2" aria-hidden="true"></span>Test WhatsApp API
                </a>
            @endrole
        </x-slot:actions>
    </x-page-header>
    @include('reports._tabs', ['active' => 'marketing'])

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="alert alert-info d-flex align-items-start gap-2" role="status">
        <span class="fas fa-info-circle mt-1" aria-hidden="true"></span>
        <div>
            <strong>Configuration presence does not confirm a live connection.</strong>
            Setup values are checked here. Open the WhatsApp API test page to verify your phone-number credentials against Meta.
            No message is sent, and secrets are never displayed.
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card h-100 p-2">
                <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-3 text-success fab fa-whatsapp" aria-hidden="true"></span>
                        <div>
                            <h2 class="h5 mb-0 text-primary">WhatsApp Business</h2>
                            <span class="small text-body-secondary">Cloud API setup readiness</span>
                        </div>
                    </div>
                    <span class="badge text-bg-secondary">Test from WhatsApp page</span>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-body-secondary">Configuration values present</span>
                        <strong>{{ collect($whatsAppSetup)->filter()->count() }} / {{ count($whatsAppSetup) }}</strong>
                    </div>
                    <div class="progress mb-3" role="progressbar" aria-label="WhatsApp API setup readiness"
                        aria-valuenow="{{ (int) (collect($whatsAppSetup)->filter()->count() / count($whatsAppSetup) * 100) }}"
                        aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: {{ collect($whatsAppSetup)->filter()->count() / count($whatsAppSetup) * 100 }}%"></div>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ([
                            ['key' => 'app_id', 'label' => 'Meta app ID', 'env' => 'META_APP_ID'],
                            ['key' => 'app_secret', 'label' => 'Meta app secret', 'env' => 'META_APP_SECRET'],
                            ['key' => 'access_token', 'label' => 'System-user access token', 'env' => 'META_ACCESS_TOKEN'],
                            ['key' => 'business_account_id', 'label' => 'WhatsApp Business Account ID', 'env' => 'META_WHATSAPP_BUSINESS_ACCOUNT_ID'],
                            ['key' => 'phone_number_id', 'label' => 'WhatsApp phone number ID', 'env' => 'META_WHATSAPP_PHONE_NUMBER_ID'],
                            ['key' => 'webhook_verify_token', 'label' => 'Webhook verify token', 'env' => 'META_WEBHOOK_VERIFY_TOKEN'],
                        ] as $item)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>{{ $item['label'] }} <code class="small">{{ $item['env'] }}</code></span>
                                @if ($whatsAppSetup[$item['key']])
                                    <x-status-badge status="configured" label="Configured" />
                                @else
                                    <x-status-badge status="missing" label="Missing" />
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-3 small text-body-secondary">
                        <span class="fab fa-whatsapp me-1" aria-hidden="true"></span>
                        {{ $whatsAppTemplateCount }} active WhatsApp message template(s) are available for staff to prepare drafts.
                    </div>
                </div>
                <div class="card-footer bg-body d-flex justify-content-between align-items-center">
                    <span class="small text-body-secondary">Connection check reads the phone profile; no messages are sent.</span>
                    <div class="d-flex gap-2">
                        @role('admin')
                            <a class="btn btn-sm btn-outline-success" href="{{ route('reports.integrations.whatsapp') }}">Test connection</a>
                        @endrole
                        <a class="btn btn-sm btn-outline-success" href="{{ route('customer-messages.index') }}">Manage templates</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6">
            <div class="card h-100 p-2">
                <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-3 text-primary fab fa-meta" aria-hidden="true"></span>
                        <div>
                            <h2 class="h5 mb-0 text-primary">Meta Marketing API</h2>
                            <span class="small text-body-secondary">Ads insights setup readiness</span>
                        </div>
                    </div>
                    <x-status-badge status="not_connected" label="Not connected" />
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-body-secondary">Configuration values present</span>
                        <strong>{{ collect($marketingSetup)->filter()->count() }} / {{ count($marketingSetup) }}</strong>
                    </div>
                    <div class="progress mb-3" role="progressbar" aria-label="Meta marketing API setup readiness"
                        aria-valuenow="{{ (int) (collect($marketingSetup)->filter()->count() / count($marketingSetup) * 100) }}"
                        aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-primary" style="width: {{ collect($marketingSetup)->filter()->count() / count($marketingSetup) * 100 }}%"></div>
                    </div>
                    <ul class="list-group list-group-flush">
                        @foreach ([
                            'app_id' => ['Meta app ID', 'META_APP_ID'],
                            'app_secret' => ['Meta app secret', 'META_APP_SECRET'],
                            'access_token' => ['System-user access token', 'META_ACCESS_TOKEN'],
                            'ad_account_id' => ['Meta Ads account ID', 'META_AD_ACCOUNT_ID'],
                        ] as $key => [$label, $envName])
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>{{ $label }} <code class="small">{{ $envName }}</code></span>
                                @if ($marketingSetup[$key])
                                    <x-status-badge status="configured" label="Configured" />
                                @else
                                    <x-status-badge status="missing" label="Missing" />
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-3 small text-body-secondary">
                        Current Graph API version: <code>{{ config('services.meta.graph_api_version') }}</code>
                    </div>
                </div>
                <div class="card-footer bg-body d-flex justify-content-between align-items-center">
                            <span class="small text-body-secondary">Campaign analytics below remain manually maintained. Create social posts in the Meta Content manager.</span>
                            <a class="btn btn-sm btn-outline-primary" href="{{ route('meta-content.index') }}">Manage Meta content</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Campaign spend', 'value' => 'RM '.number_format($spend, 2), 'icon' => 'fa-coins'],
            ['label' => 'Attributed revenue', 'value' => 'RM '.number_format($revenue, 2), 'icon' => 'fa-chart-line'],
            ['label' => 'Leads', 'value' => number_format($leads), 'icon' => 'fa-user-plus'],
            ['label' => 'Conversions', 'value' => number_format($conversions), 'icon' => 'fa-bullseye'],
        ] as $metric)
            <div class="col-6 col-xl-3">
                <div class="card h-100 p-2">
                    <div class="card-header bg-body"><h2 class="h5 mb-0 text-primary">{{ $metric['label'] }}</h2></div>
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <span class="fs-4 fw-semibold">{{ $metric['value'] }}</span>
                        <span class="fas {{ $metric['icon'] }} fs-3 text-primary opacity-50" aria-hidden="true"></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <details class="card mb-3 p-2">
        <summary class="card-header bg-body fw-semibold">Add campaign results</summary>
        <form method="POST" action="{{ route('reports.marketing.store') }}">
            @csrf
            <div class="card-body row g-3">
                <div class="col-md-4"><label class="form-label" for="campaign-name">Campaign</label><input class="form-control" id="campaign-name" name="name" required></div>
                <div class="col-md-3"><label class="form-label" for="channel">Channel</label><select class="form-select" id="channel" name="channel" required><option value="meta_ads">Meta Ads</option><option value="whatsapp">WhatsApp</option>@foreach (['social', 'search', 'email', 'print', 'referral', 'other'] as $channel)<option value="{{ $channel }}">{{ ucfirst($channel) }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label" for="campaign-status">Status</label><select class="form-select" id="campaign-status" name="status">@foreach (['planned', 'active', 'completed', 'paused'] as $status)<option value="{{ $status }}">{{ ucfirst($status) }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label" for="spend">Spend</label><input class="form-control" id="spend" name="spend" type="number" min="0" step="0.01" value="0" required></div>
                <div class="col-md-3"><label class="form-label" for="starts_on">Start</label><input class="form-control" id="starts_on" name="starts_on" type="date"></div>
                <div class="col-md-3"><label class="form-label" for="ends_on">End</label><input class="form-control" id="ends_on" name="ends_on" type="date"></div>
                <div class="col-md-3"><label class="form-label" for="impressions">Impressions</label><input class="form-control" id="impressions" name="impressions" type="number" min="0" value="0" required></div>
                <div class="col-md-3"><label class="form-label" for="clicks">Clicks</label><input class="form-control" id="clicks" name="clicks" type="number" min="0" value="0" required></div>
                <div class="col-md-3"><label class="form-label" for="leads">Leads</label><input class="form-control" id="leads" name="leads" type="number" min="0" value="0" required></div>
                <div class="col-md-3"><label class="form-label" for="conversions">Conversions</label><input class="form-control" id="conversions" name="conversions" type="number" min="0" value="0" required></div>
                <div class="col-md-3"><label class="form-label" for="attributed_revenue">Attributed revenue</label><input class="form-control" id="attributed_revenue" name="attributed_revenue" type="number" min="0" step="0.01" value="0" required></div>
            </div>
            <div class="card-footer bg-body text-end"><button class="btn btn-primary">Save campaign results</button></div>
        </form>
    </details>

    <div class="card p-2">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0 text-primary">Campaign performance</h2>
            <p class="small text-body-secondary mb-0">Manually recorded until Meta API reporting is connected.</p>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 table-bordered">
                <thead><tr><th>Campaign</th><th>Channel</th><th>Status</th><th class="text-end">Spend</th><th class="text-end">Impressions</th><th class="text-end">Clicks</th><th class="text-end">Leads</th><th class="text-end">Conversions</th><th class="text-end">Revenue</th></tr></thead>
                <tbody>
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td>{{ $campaign->name }}<div class="small text-body-secondary">{{ $campaign->starts_on?->format('M j, Y') ?? 'No start date' }}</div></td>
                            <td>{{ $campaign->channel === 'meta_ads' ? 'Meta Ads' : ($campaign->channel === 'whatsapp' ? 'WhatsApp' : ucfirst($campaign->channel)) }}</td><td><x-status-badge :status="$campaign->status" :label="ucfirst($campaign->status)" /></td>
                            <td class="text-end">RM {{ number_format($campaign->spend, 2) }}</td>
                            <td class="text-end">{{ number_format($campaign->impressions) }}</td>
                            <td class="text-end">{{ number_format($campaign->clicks) }}</td>
                            <td class="text-end">{{ number_format($campaign->leads) }}</td>
                            <td class="text-end">{{ number_format($campaign->conversions) }}</td>
                            <td class="text-end">RM {{ number_format($campaign->attributed_revenue, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-body-secondary py-5">No campaign results recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
