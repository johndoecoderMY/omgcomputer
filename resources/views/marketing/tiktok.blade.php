@extends('layouts.app')

@section('title', 'TikTok')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Marketing" title="TikTok"
        description="Review TikTok API setup and the available paths for publishing content and managing messages."
        :back-url="route('reports.marketing')" back-label="Marketing overview" />

    <div class="alert alert-info d-flex align-items-start gap-2" role="status">
        <span class="bi bi-info-circle mt-1" aria-hidden="true"></span>
        <div>
            This page reports configuration presence only; it does not connect an account or publish content.
            Never enter TikTok credentials into a form or share them in chat.
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-6">
            <section class="card h-100 p-2">
                <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-3 bi bi-tiktok" aria-hidden="true"></span>
                        <div>
                            <h2 class="h5 mb-0 text-primary">Content posting</h2>
                            <span class="small text-body-secondary">TikTok Content Posting API</span>
                        </div>
                    </div>
                    <x-status-badge status="not_connected" label="Not connected" />
                </div>
                <div class="card-body">
                    <p>Direct publishing and sending a post as a draft are supported by TikTok’s Content Posting API. Account authorization and TikTok app approval are required before this application can publish.</p>
                    <ul class="list-group list-group-flush mb-3">
                        @foreach ([
                            'client_key' => ['Developer app client key', 'TIKTOK_CLIENT_KEY'],
                            'client_secret' => ['Developer app client secret', 'TIKTOK_CLIENT_SECRET'],
                            'redirect_uri' => ['OAuth redirect URI', 'TIKTOK_REDIRECT_URI'],
                        ] as $key => [$label, $envName])
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span>{{ $label }} <code class="small">{{ $envName }}</code></span>
                                @if ($setup[$key])
                                    <x-status-badge status="configured" label="Configured" />
                                @else
                                    <x-status-badge status="missing" label="Missing" />
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <div class="alert alert-warning mb-0">
                        Direct posting requires TikTok app approval for the <code>video.publish</code> scope and an audit. Until audited, posts may be restricted to private visibility.
                    </div>
                </div>
                <div class="card-footer bg-body">
                    <a href="https://developers.tiktok.com/products/content-posting-api/" target="_blank" rel="noopener noreferrer">
                        Content Posting API documentation <span class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></span>
                    </a>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-6">
            <section class="card h-100 p-2">
                <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="fs-3 bi bi-chat-dots" aria-hidden="true"></span>
                        <div>
                            <h2 class="h5 mb-0 text-primary">Messages</h2>
                            <span class="small text-body-secondary">TikTok inbox integration</span>
                        </div>
                    </div>
                    <x-status-badge status="not_connected" label="Not available" />
                </div>
                <div class="card-body">
                    <p class="mb-2">A generally available public API for reading and replying to TikTok direct messages could not be confirmed in the developer documentation reviewed for this integration.</p>
                    <p class="mb-0 text-body-secondary">Inbox support is not connected. Confirm that TikTok has granted your Business account or approved partner access to a messaging API before building this feature. Do not rely on unofficial endpoints.</p>
                </div>
                <div class="card-footer bg-body">
                    <a href="https://developers.tiktok.com/" target="_blank" rel="noopener noreferrer">
                        TikTok for Developers <span class="bi bi-box-arrow-up-right ms-1" aria-hidden="true"></span>
                    </a>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
