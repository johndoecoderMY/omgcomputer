@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Insights" title="Reports & analytics" />
    <form class="row g-2 mb-3" method="GET"><div class="col-auto"><label class="visually-hidden" for="from">From</label><input class="form-control" id="from" name="from" type="date" value="{{ request('from', $from->toDateString()) }}"></div><div class="col-auto"><label class="visually-hidden" for="to">To</label><input class="form-control" id="to" name="to" type="date" value="{{ request('to', $to->toDateString()) }}"></div><div class="col-auto"><button class="btn btn-outline-primary">Apply dates</button></div><div class="col-auto"><a class="btn btn-outline-secondary" href="{{ route('reports.export', request()->query()) }}"><span class="fas fa-download me-2"></span>Export sales CSV</a></div></form>
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="small text-body-secondary">Issued sales</div><div class="fs-4 fw-semibold">RM {{ number_format($salesTotal, 2) }}</div><a href="{{ route('reports.sales') }}" class="small">Sales report <span class="fas fa-arrow-right ms-1"></span></a></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="small text-body-secondary">Open invoice balance</div><div class="fs-4 fw-semibold">RM {{ number_format($openBalance, 2) }}</div><a href="{{ route('reports.sales') }}" class="small">View sales detail <span class="fas fa-arrow-right ms-1"></span></a></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="small text-body-secondary">Low-stock items</div><div class="fs-4 fw-semibold">{{ $lowStockCount }}</div><a href="{{ route('reports.inventory') }}" class="small">Inventory report <span class="fas fa-arrow-right ms-1"></span></a></div></div></div>
        <div class="col-sm-6 col-xl-3"><div class="card h-100"><div class="card-body"><div class="small text-body-secondary">Active campaigns</div><div class="fs-4 fw-semibold">{{ $activeCampaigns }}</div><a href="{{ route('reports.marketing') }}" class="small">Marketing report <span class="fas fa-arrow-right ms-1"></span></a></div></div></div>
    </div>
    <div class="card"><div class="card-header fw-semibold">Report library</div><div class="list-group list-group-flush">
        <a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('reports.sales') }}"><span><span class="fas fa-file-invoice-dollar me-2 text-primary"></span>Sales</span><span class="fas fa-chevron-right"></span></a>
        <a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('reports.inventory') }}"><span><span class="fas fa-boxes me-2 text-success"></span>Inventory</span><span class="fas fa-chevron-right"></span></a>
        <a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('reports.teams') }}"><span><span class="fas fa-users me-2 text-warning"></span>Team performance</span><span class="fas fa-chevron-right"></span></a>
        <a class="list-group-item list-group-item-action d-flex justify-content-between" href="{{ route('reports.marketing') }}"><span><span class="fas fa-bullhorn me-2 text-info"></span>Marketing</span><span class="fas fa-chevron-right"></span></a>
    </div></div>
</div>
@endsection