@extends('layouts.app')

@section('title', 'Sales Report')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Reports / Finance" title="Sales"
        description="Track issued invoices, payment collection, and items contributing to revenue."
        :back-url="route('reports.index')" back-label="All reports">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('reports.export', request()->query()) }}">
                <span class="bi bi-download me-2" aria-hidden="true"></span>Export CSV
            </a>
        </x-slot:actions>
    </x-page-header>
    @include('reports._tabs', ['active' => 'sales'])

    <div class="card p-2 mb-4">
        <form class="card-body d-flex flex-wrap align-items-end gap-3" method="GET">
            <div><label class="form-label mb-1" for="from">From</label><input class="form-control" id="from" name="from" type="date" value="{{ request('from', $from->toDateString()) }}"></div>
            <div><label class="form-label mb-1" for="to">To</label><input class="form-control" id="to" name="to" type="date" value="{{ request('to', $to->toDateString()) }}"></div>
            <button class="btn btn-outline-primary" type="submit">Apply period</button>
            <a class="btn btn-outline-secondary" href="{{ route('reports.sales') }}">Reset</a>
            <span class="small text-body-secondary ms-lg-auto">{{ $from->format('M j, Y') }} – {{ $to->format('M j, Y') }}</span>
        </form>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Issued invoices', 'value' => number_format($invoiceCount), 'icon' => 'bi-file-earmark-text'],
            ['label' => 'Sales revenue', 'value' => 'RM '.number_format($revenue, 2), 'icon' => 'bi-graph-up'],
            ['label' => 'Payments collected', 'value' => 'RM '.number_format($collected, 2), 'icon' => 'bi-check-circle'],
            ['label' => 'Outstanding balance', 'value' => 'RM '.number_format(max(0, $revenue - $collected), 2), 'icon' => 'bi-wallet'],
        ] as $metric)
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 p-2">
                    <div class="card-header bg-body"><h2 class="h5 mb-0 text-primary">{{ $metric['label'] }}</h2></div>
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <span class="fs-4 fw-semibold">{{ $metric['value'] }}</span>
                        <span class="bi {{ $metric['icon'] }} fs-3 text-primary opacity-50" aria-hidden="true"></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card p-2 mb-4">
        <div class="card-header bg-body">
            <h2 class="h5 mb-1 text-primary">Revenue and collections</h2>
            <p class="small text-body-secondary mb-0">Monthly totals for invoices in the selected period.</p>
        </div>
        <div class="card-body">
            @if ($salesTrend->isEmpty())
                <p class="text-body-secondary text-center py-5 mb-0">No invoice data for this period yet.</p>
            @else
                <div id="sales-trend-chart" role="img" aria-label="Monthly sales revenue and collected payments" style="height: 320px"></div>
            @endif
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xxl-7">
            <div class="card h-100 p-2">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <div><h2 class="h5 mb-1 text-primary">Invoices in period</h2><p class="small text-body-secondary mb-0">{{ $invoices->count() }} invoice(s)</p></div>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('billing.index', ['type' => 'invoice']) }}">All invoices</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-bordered">
                        <thead><tr><th>Invoice</th><th>Customer</th><th>Status</th><th class="text-end">Total</th><th class="text-end">Paid</th></tr></thead>
                        <tbody>
                            @forelse ($invoices as $invoice)
                                <tr>
                                    <td><a class="fw-semibold" href="{{ route('billing.documents.show', $invoice) }}">{{ $invoice->number }}</a><div class="small text-body-secondary">{{ $invoice->issue_date->format('M j, Y') }}</div></td>
                                    <td>{{ $invoice->customer_name }}</td>
                                    <td><x-status-badge :status="$invoice->status" :label="str_replace('_', ' ', ucfirst($invoice->status))" /></td>
                                    <td class="text-end">RM {{ number_format($invoice->total, 2) }}</td>
                                    <td class="text-end">RM {{ number_format($invoice->paid_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-body-secondary py-5">No sales in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-xxl-5">
            <div class="card h-100 p-2">
                <div class="card-header bg-body"><h2 class="h5 mb-1 text-primary">Top items by revenue</h2><p class="small text-body-secondary mb-0">Best-performing invoice line items.</p></div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 table-bordered">
                        <thead><tr><th>Item</th><th class="text-end">Units</th><th class="text-end">Revenue</th></tr></thead>
                        <tbody>
                            @forelse ($topItems as $item)
                                <tr><td class="fw-semibold">{{ $item->description }}</td><td class="text-end">{{ $item->units }}</td><td class="text-end">RM {{ number_format($item->revenue, 2) }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-body-secondary py-5">No item sales in this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const element = document.getElementById('sales-trend-chart');
        if (!element || !window.echarts) return;
        const rows = @js($salesTrend);
        const chart = window.echarts.init(element);
        chart.setOption({
            color: ['#2c7be5', '#00d27a'],
            tooltip: { trigger: 'axis' },
            legend: { top: 0 },
            grid: { top: 40, right: 20, bottom: 30, left: 70 },
            xAxis: { type: 'category', data: rows.map((row) => row.month), axisLabel: { color: '#748194' } },
            yAxis: { type: 'value', axisLabel: { color: '#748194', formatter: (value) => `RM ${value}` }, splitLine: { lineStyle: { color: '#edf2f9' } } },
            series: [
                { name: 'Revenue', type: 'bar', data: rows.map((row) => row.revenue), barMaxWidth: 36, itemStyle: { borderRadius: [5, 5, 0, 0] } },
                { name: 'Collected', type: 'line', smooth: true, data: rows.map((row) => row.collected) },
            ],
        });
        window.addEventListener('resize', () => chart.resize());
    })();
</script>
@endpush
