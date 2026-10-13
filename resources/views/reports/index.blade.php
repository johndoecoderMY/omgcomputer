@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Business intelligence" title="Reports & Analytics" description="Review invoiced sales, received payments, unpaid balances, and stock activity.">
            <x-slot:actions>
            <a href="{{ route('reports.export', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-primary"><span class="fas fa-download me-2"></span>Export invoices CSV</a>
            </x-slot:actions>
        </x-page-header>

        <button class="btn btn-falcon-default mb-4" type="button" data-bs-toggle="modal" data-bs-target="#reportFilterModal"><span class="fas fa-calendar-alt me-2"></span>Reporting period: {{ $from }} – {{ $to }}</button>

        <div class="row g-3 mb-4">
            <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Invoices issued</p><p class="h3 mb-0">{{ $invoiceCount }}</p></div></div></div>
            <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Sales invoiced</p><p class="h3 mb-0">RM{{ number_format($billed, 2) }}</p></div></div></div>
            <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Payments received in range</p><p class="h3 text-success mb-0">RM{{ number_format($collected, 2) }}</p></div></div></div>
            <div class="col-6 col-xl-3"><div class="card h-100"><div class="card-body"><p class="text-600 text-uppercase fs-10 fw-bold mb-2">Total unpaid balance</p><p class="h3 text-warning mb-0">RM{{ number_format($outstanding, 2) }}</p></div></div></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-xl-7">
                <section class="card h-100">
                    <div class="card-header"><h2 class="h5 mb-0">Monthly sales and cash received</h2></div>
                    <div class="card-body">
                        @forelse ($monthly as $month)
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1"><strong>{{ $month['label'] }}</strong><span class="text-600 fs-10">Invoiced RM{{ number_format($month['billed'], 2) }} · Received RM{{ number_format($month['collected'], 2) }}</span></div>
                                <div class="progress mb-1" style="height: 10px" role="progressbar" aria-label="Invoiced sales for {{ $month['label'] }}" aria-valuenow="{{ (int) round($month['billed'] / $maxMonthly * 100) }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-primary" style="width: {{ $month['billed'] / $maxMonthly * 100 }}%"></div></div>
                                <div class="progress" style="height: 6px" role="progressbar" aria-label="Payments received for {{ $month['label'] }}" aria-valuenow="{{ (int) round($month['collected'] / $maxMonthly * 100) }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-success" style="width: {{ $month['collected'] / $maxMonthly * 100 }}%"></div></div>
                            </div>
                        @empty
                            <p class="text-600 mb-0">No months in this reporting range.</p>
                        @endforelse
                        <div class="d-flex gap-3 fs-10 text-600 mt-4"><span><span class="badge bg-primary me-1">&nbsp;</span>Sales invoiced</span><span><span class="badge bg-success me-1">&nbsp;</span>Cash received</span></div>
                    </div>
                </section>
            </div>
            <div class="col-xl-5">
                <section class="card h-100">
                    <div class="card-header"><h2 class="h5 mb-0">Inventory health</h2></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3"><span>Units received in period</span><strong class="text-success">+{{ $stockReceived }}</strong></div>
                        <div class="d-flex justify-content-between mb-4"><span>Units used or removed</span><strong class="text-warning">−{{ $stockUsed }}</strong></div>
                        <h3 class="h6">Low-stock items</h3>
                        @forelse ($lowStock as $part)
                            <div class="d-flex justify-content-between align-items-center border-top py-2">
                                <div><strong>{{ $part->name }}</strong><div class="text-600 fs-10">{{ $part->sku }}</div></div>
                                <span class="badge badge-subtle-warning">{{ $part->stock_quantity }} / reorder {{ $part->reorder_level }}</span>
                            </div>
                        @empty
                            <p class="text-600 fs-10 mb-0">No parts are currently at or below their reorder level.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>

        <section class="card">
            <div class="card-header"><h2 class="h5 mb-0">Top invoiced services / items</h2></div>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0" data-datatables='{"pageLength":10,"language":{"emptyTable":"No invoiced services or items for this period."}}'>
                    <thead><tr><th>Description</th><th class="text-end">Units sold</th><th class="text-end">Invoiced sales</th></tr></thead>
                    <tbody>
                        @foreach ($topServices as $service)
                            <tr><td>{{ $service->description }}</td><td class="text-end">{{ $service->units }}</td><td class="text-end">RM{{ number_format((float) $service->sales, 2) }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-600 fs-10">Reports use recorded invoices and payments. They do not include quotes, cancelled documents, or unrecorded transactions.</div>
        </section>
    </div>
    <div class="modal fade" id="reportFilterModal" tabindex="-1" aria-labelledby="reportFilterModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form method="GET" action="{{ route('reports.index') }}">
            <div class="modal-header"><h2 class="modal-title fs-5" id="reportFilterModalLabel">Choose reporting period</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-sm-6"><label class="form-label" for="report-from">From</label><input class="form-control" id="report-from" name="from" type="date" value="{{ $from }}" required></div>
                <div class="col-sm-6"><label class="form-label" for="report-to">To</label><input class="form-control" id="report-to" name="to" type="date" value="{{ $to }}" required></div>
            </div>@if ($errors->any())<div class="alert alert-danger mt-3 mb-0">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif</div>
            <div class="modal-footer"><button class="btn btn-primary" type="submit">Apply period</button></div>
        </form>
    </div></div></div>
    @push('scripts')
        @if ($errors->any())
            <script>document.addEventListener('DOMContentLoaded',function(){bootstrap.Modal.getOrCreateInstance(document.getElementById('reportFilterModal')).show();});</script>
        @endif
    @endpush
@endsection
