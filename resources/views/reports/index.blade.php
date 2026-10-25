@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Insights" title="Reports &amp; analytics"
        description="A clear view of sales, stock health, team workload, and campaign performance.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('reports.export', request()->query()) }}">
                <span class="bi bi-download me-2" aria-hidden="true"></span>Export sales CSV
            </a>
        </x-slot:actions>
    </x-page-header>

    <div class="card p-2 mb-4">
        <form class="card-body d-flex flex-wrap align-items-end gap-3" method="GET">
            <div>
                <label class="form-label mb-1" for="from">From</label>
                <input class="form-control" id="from" name="from" type="date" value="{{ request('from', $from->toDateString()) }}">
            </div>
            <div>
                <label class="form-label mb-1" for="to">To</label>
                <input class="form-control" id="to" name="to" type="date" value="{{ request('to', $to->toDateString()) }}">
            </div>
            <button class="btn btn-outline-primary" type="submit">Apply period</button>
            <a class="btn btn-outline-secondary" href="{{ route('reports.index') }}">Reset</a>
            <span class="small text-body-secondary ms-lg-auto">Sales period: {{ $from->format('M j, Y') }} – {{ $to->format('M j, Y') }}</span>
        </form>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Issued sales', 'value' => 'RM '.number_format($salesTotal, 2), 'icon' => 'bi-file-earmark-spreadsheet', 'color' => 'primary', 'route' => 'reports.sales', 'link' => 'Sales report'],
            ['label' => 'Open invoice balance', 'value' => 'RM '.number_format($openBalance, 2), 'icon' => 'bi-wallet', 'color' => 'warning', 'route' => 'reports.sales', 'link' => 'View sales detail'],
            ['label' => 'Low-stock items', 'value' => number_format($lowStockCount), 'icon' => 'bi-boxes', 'color' => 'danger', 'route' => 'reports.inventory', 'link' => 'Inventory report'],
            ['label' => 'Active campaigns', 'value' => number_format($activeCampaigns), 'icon' => 'bi-megaphone', 'color' => 'info', 'route' => 'reports.marketing', 'link' => 'Marketing report'],
        ] as $metric)
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 p-2">
                    <div class="card-header bg-body d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0 text-primary">{{ $metric['label'] }}</h2>
                        <span class="bi {{ $metric['icon'] }} fs-3 text-{{ $metric['color'] }} opacity-50" aria-hidden="true"></span>
                    </div>
                    <div class="card-body">
                        <div class="fs-3 fw-semibold mb-3">{{ $metric['value'] }}</div>
                        <a class="btn btn-sm btn-primary" href="{{ route($metric['route']) }}">{{ $metric['link'] }}</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card p-2 border-start border-4 border-primary">
                <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <span class="bi bi-truck-front fs-2 text-primary" aria-hidden="true"></span>
                        <div>
                            <h2 class="h5 mb-1 text-primary">Delivery API readiness</h2>
                            <p class="small text-body-secondary mb-0">Parcel status lookup via 17TRACK</p>
                        </div>
                    </div>
                    <x-status-badge status="not_connected" label="Not connected" />
                </div>
                <div class="card-body row g-3 align-items-center">
                    <div class="col-lg-8">
                        <p class="mb-2">Look up shipment progress across supported courier services with a tracking number. 17TRACK offers a one-time free trial allocation for eligible new API accounts; check its current terms before using it for ongoing shipment volume.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <x-status-badge :status="$trackingApiConfigured ? 'configured' : 'missing'" :label="'17TRACK API key '.($trackingApiConfigured ? 'configured' : 'not set')" />
                        </div>
                        <p class="small text-body-secondary mt-2 mb-0">
                            The API key is kept server-side. Set <code>TRACK17_API_KEY</code> in the server environment after creating a 17TRACK API account.
                            Configuration readiness does not mean a tracking lookup has been tested.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        @role('admin')
                            <a class="btn btn-primary mb-2" href="{{ route('reports.integrations.tracking') }}">
                                <span class="bi bi-truck me-2" aria-hidden="true"></span>Open parcel tracker
                            </a>
                        @endrole
                        <a class="btn btn-outline-primary" href="https://api.17track.net/en/doc" target="_blank" rel="noopener noreferrer">
                            <span class="bi bi-box-arrow-up-right me-2" aria-hidden="true"></span>17TRACK API docs
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-body small text-body-secondary">
                    Tracking registers a number with 17TRACK and retrieves available courier updates; it does not book shipments or buy labels.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @foreach ([
            ['title' => 'Sales', 'description' => 'Invoices, collections, revenue, and top-selling items.', 'icon' => 'bi-file-earmark-spreadsheet', 'color' => 'primary', 'route' => 'reports.sales', 'action' => 'View sales'],
            ['title' => 'Inventory', 'description' => 'Stock value, reorder risk, and recent movements.', 'icon' => 'bi-boxes', 'color' => 'success', 'route' => 'reports.inventory', 'action' => 'View inventory'],
            ['title' => 'Team performance', 'description' => 'Assigned work, progress, and completion rates.', 'icon' => 'bi-people', 'color' => 'warning', 'route' => 'reports.teams', 'action' => 'View team report'],
            ['title' => 'Marketing', 'description' => 'Campaign results and Meta / WhatsApp setup readiness.', 'icon' => 'bi-megaphone', 'color' => 'info', 'route' => 'reports.marketing', 'action' => 'View marketing'],
        ] as $report)
            <div class="col-md-6 col-xl-3">
                <a class="card h-100 p-2 text-decoration-none" href="{{ route($report['route']) }}">
                    <div class="card-header bg-body d-flex align-items-center gap-3">
                        <span class="bi {{ $report['icon'] }} fs-3 text-{{ $report['color'] }}" aria-hidden="true"></span>
                        <h2 class="h5 mb-0 text-primary">{{ $report['title'] }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-body-secondary">{{ $report['description'] }}</p>
                        <span class="btn btn-sm btn-primary">{{ $report['action'] }} <span class="bi bi-arrow-right ms-1" aria-hidden="true"></span></span>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection
