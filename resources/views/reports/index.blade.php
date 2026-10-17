@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Insights" title="Reports &analytics"
        description="A clear view of sales, stock health, team workload, and campaign performance.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('reports.export', request()->query()) }}">
                <span class="fas fa-download me-2" aria-hidden="true"></span>Export sales CSV
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
            ['label' => 'Issued sales', 'value' => 'RM '.number_format($salesTotal, 2), 'icon' => 'fa-file-invoice-dollar', 'color' => 'primary', 'route' => 'reports.sales', 'link' => 'Sales report'],
            ['label' => 'Open invoice balance', 'value' => 'RM '.number_format($openBalance, 2), 'icon' => 'fa-wallet', 'color' => 'warning', 'route' => 'reports.sales', 'link' => 'View sales detail'],
            ['label' => 'Low-stock items', 'value' => number_format($lowStockCount), 'icon' => 'fa-boxes', 'color' => 'danger', 'route' => 'reports.inventory', 'link' => 'Inventory report'],
            ['label' => 'Active campaigns', 'value' => number_format($activeCampaigns), 'icon' => 'fa-bullhorn', 'color' => 'info', 'route' => 'reports.marketing', 'link' => 'Marketing report'],
        ] as $metric)
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 p-2">
                    <div class="card-header bg-body d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0 text-primary">{{ $metric['label'] }}</h2>
                        <span class="fas {{ $metric['icon'] }} fs-3 text-{{ $metric['color'] }} opacity-50" aria-hidden="true"></span>
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
                        <span class="fas fa-truck-fast fs-2 text-primary" aria-hidden="true"></span>
                        <div>
                            <h2 class="h5 mb-1 text-primary">Delivery API readiness</h2>
                            <p class="small text-body-secondary mb-0">Recommended starting point: EasyParcel Malaysia</p>
                        </div>
                    </div>
                    <span class="badge text-bg-secondary">Not connected</span>
                </div>
                <div class="card-body row g-3 align-items-center">
                    <div class="col-lg-8">
                        <p class="mb-2">EasyParcel is a practical first option to evaluate because its API supports rate checks across courier services from a single integration. Confirm the couriers, coverage, and account terms you need before building the connection.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge {{ $deliverySetup['api_key'] ? 'text-bg-success' : 'text-bg-light border' }}">API key {{ $deliverySetup['api_key'] ? 'configured' : 'not set' }}</span>
                            <span class="badge {{ $deliverySetup['api_url'] ? 'text-bg-success' : 'text-bg-light border' }}">API URL {{ $deliverySetup['api_url'] ? 'configured' : 'not set' }}</span>
                            <span class="badge text-bg-light border">Environment: {{ ucfirst($deliveryEnvironment ?: 'not set') }}</span>
                        </div>
                        <p class="small text-body-secondary mt-2 mb-0">
                            Credentials are not stored in the database or shown here. Add them to the server environment as
                            <code>EASYPARCEL_API_KEY</code> and <code>EASYPARCEL_API_URL</code> after creating an API account.
                            Configuration readiness does not mean the API has been tested.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a class="btn btn-outline-primary" href="https://developers.easyparcel.com/" target="_blank" rel="noopener noreferrer">
                            <span class="fas fa-arrow-up-right-from-square me-2" aria-hidden="true"></span>EasyParcel API docs
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-body small text-body-secondary">
                    No rates, labels, bookings, or tracking requests are made yet. A later integration should start with rate lookup and tracking, then add shipment booking only after confirming workflow and credentials.
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @foreach ([
            ['title' => 'Sales', 'description' => 'Invoices, collections, revenue, and top-selling items.', 'icon' => 'fa-file-invoice-dollar', 'color' => 'primary', 'route' => 'reports.sales', 'action' => 'View sales'],
            ['title' => 'Inventory', 'description' => 'Stock value, reorder risk, and recent movements.', 'icon' => 'fa-boxes', 'color' => 'success', 'route' => 'reports.inventory', 'action' => 'View inventory'],
            ['title' => 'Team performance', 'description' => 'Assigned work, progress, and completion rates.', 'icon' => 'fa-users', 'color' => 'warning', 'route' => 'reports.teams', 'action' => 'View team report'],
            ['title' => 'Marketing', 'description' => 'Campaign results and Meta / WhatsApp setup readiness.', 'icon' => 'fa-bullhorn', 'color' => 'info', 'route' => 'reports.marketing', 'action' => 'View marketing'],
        ] as $report)
            <div class="col-md-6 col-xl-3">
                <a class="card h-100 p-2 text-decoration-none" href="{{ route($report['route']) }}">
                    <div class="card-header bg-body d-flex align-items-center gap-3">
                        <span class="fas {{ $report['icon'] }} fs-3 text-{{ $report['color'] }}" aria-hidden="true"></span>
                        <h2 class="h5 mb-0 text-primary">{{ $report['title'] }}</h2>
                    </div>
                    <div class="card-body">
                        <p class="text-body-secondary">{{ $report['description'] }}</p>
                        <span class="btn btn-sm btn-primary">{{ $report['action'] }} <span class="fas fa-arrow-right ms-1" aria-hidden="true"></span></span>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection
