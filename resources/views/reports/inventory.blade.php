@extends('layouts.app')

@section('title', 'Inventory Report')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Reports / Operations" title="Inventory"
        description="Monitor stock value, items that need reordering, and recent inventory activity."
        :back-url="route('reports.index')" back-label="All reports">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('inventory.parts') }}">Manage inventory</a>
        </x-slot:actions>
    </x-page-header>
    @include('reports._tabs', ['active' => 'inventory'])

    @if ($lowStockCount > 0)
        <div class="alert alert-warning d-flex align-items-center gap-2" role="status">
            <span class="fas fa-triangle-exclamation" aria-hidden="true"></span>
            <span><strong>{{ $lowStockCount }} item(s)</strong> are at or below their reorder level. Review stock before scheduling more repairs.</span>
        </div>
    @else
        <div class="alert alert-success d-flex align-items-center gap-2" role="status">
            <span class="fas fa-circle-check" aria-hidden="true"></span>
            <span>All tracked parts are above their reorder levels.</span>
        </div>
    @endif

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Tracked items', 'value' => number_format($parts->count()), 'icon' => 'fa-boxes', 'color' => 'primary'],
            ['label' => 'At or below reorder level', 'value' => number_format($lowStockCount), 'icon' => 'fa-triangle-exclamation', 'color' => 'danger'],
            ['label' => 'Stock value at cost', 'value' => 'RM '.number_format($stockValue, 2), 'icon' => 'fa-coins', 'color' => 'success'],
        ] as $metric)
            <div class="col-md-4">
                <div class="card h-100 p-2">
                    <div class="card-header bg-body"><h2 class="h5 mb-0 text-primary">{{ $metric['label'] }}</h2></div>
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <span class="fs-4 fw-semibold {{ $metric['color'] === 'danger' && $lowStockCount ? 'text-danger' : '' }}">{{ $metric['value'] }}</span>
                        <span class="fas {{ $metric['icon'] }} fs-3 text-{{ $metric['color'] }} opacity-50" aria-hidden="true"></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-8">
            <div class="card h-100 p-2">
                <div class="card-header bg-body"><h2 class="h5 mb-1 text-primary">Lowest stock levels</h2><p class="small text-body-secondary mb-0">Up to 10 items compared with their reorder thresholds.</p></div>
                <div class="card-body">
                    @if ($stockChart->isEmpty())
                        <p class="text-body-secondary text-center py-5 mb-0">Add inventory items to see stock levels.</p>
                    @else
                        <div id="inventory-stock-chart" role="img" aria-label="Lowest stock levels compared with reorder points" style="height: 320px"></div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100 p-2">
                <div class="card-header bg-body"><h2 class="h5 mb-1 text-primary">Stock health</h2><p class="small text-body-secondary mb-0">Items against reorder point.</p></div>
                <div class="card-body">
                    @if ($parts->isEmpty())
                        <p class="text-body-secondary text-center py-5 mb-0">No inventory data yet.</p>
                    @else
                        <div id="inventory-health-chart" role="img" aria-label="Inventory stock health" style="height: 280px"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xxl-8">
            <div class="card h-100 p-2">
                <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                    <div><h2 class="h5 mb-1 text-primary">Stock on hand</h2><p class="small text-body-secondary mb-0">{{ $parts->count() }} tracked item(s)</p></div>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('inventory.movements') }}">Stock movements</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Item</th><th>SKU</th><th class="text-end">Quantity</th><th class="text-end">Reorder point</th><th class="text-end">Movements</th></tr></thead>
                        <tbody>
                            @forelse ($parts as $part)
                                <tr>
                                    <td class="fw-semibold">{{ $part->name }}</td><td>{{ $part->sku ?: '—' }}</td>
                                    <td class="text-end"><span class="badge text-bg-{{ $part->stock_quantity <= $part->reorder_level ? 'danger' : 'success' }}">{{ $part->stock_quantity }}</span></td>
                                    <td class="text-end">{{ $part->reorder_level }}</td><td class="text-end">{{ $part->movements_count }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-body-secondary py-5">No inventory items tracked.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-xxl-4">
            <div class="card h-100 p-2">
                <div class="card-header bg-body"><h2 class="h5 mb-1 text-primary">Recent stock activity</h2><p class="small text-body-secondary mb-0">Latest inventory adjustments.</p></div>
                <div class="list-group list-group-flush">
                    @forelse ($recentMovements as $movement)
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center gap-2">
                            <span>{{ $movement->part?->name ?? 'Deleted item' }}<small class="d-block text-body-secondary">{{ $movement->created_at->diffForHumans() }}</small></span>
                            <span class="fw-semibold {{ $movement->quantity_change < 0 ? 'text-danger' : 'text-success' }}">{{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}</span>
                        </div>
                    @empty
                        <div class="list-group-item px-0 text-body-secondary">No movements recorded.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        if (!window.echarts) return;
        const stockElement = document.getElementById('inventory-stock-chart');
        if (stockElement) {
            const rows = @js($stockChart);
            const chart = window.echarts.init(stockElement);
            chart.setOption({
                color: ['#2c7be5', '#f5803e'],
                tooltip: { trigger: 'axis' },
                legend: { top: 0 },
                grid: { top: 40, right: 18, bottom: 20, left: 130 },
                xAxis: { type: 'value', minInterval: 1, splitLine: { lineStyle: { color: '#edf2f9' } } },
                yAxis: { type: 'category', data: rows.map((row) => row.name).reverse(), axisLabel: { color: '#748194', width: 110, overflow: 'truncate' } },
                series: [
                    { name: 'On hand', type: 'bar', data: rows.map((row) => row.quantity).reverse(), barMaxWidth: 20, itemStyle: { borderRadius: [0, 5, 5, 0] } },
                    { name: 'Reorder point', type: 'bar', data: rows.map((row) => row.reorder).reverse(), barMaxWidth: 20, itemStyle: { borderRadius: [0, 5, 5, 0] } },
                ],
            });
            window.addEventListener('resize', () => chart.resize());
        }

        const healthElement = document.getElementById('inventory-health-chart');
        if (healthElement) {
            const chart = window.echarts.init(healthElement);
            chart.setOption({
                color: ['#e63757', '#00d27a'],
                tooltip: { trigger: 'item' },
                legend: { bottom: 0, left: 'center' },
                series: [{
                    type: 'pie',
                    radius: ['48%', '72%'],
                    center: ['50%', '43%'],
                    itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
                    label: { show: false },
                    data: @js($stockHealthChart),
                }],
            });
            window.addEventListener('resize', () => chart.resize());
        }
    })();
</script>
@endpush
