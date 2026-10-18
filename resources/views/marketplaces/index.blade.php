@extends('layouts.app')

@section('title', 'E-commerce Shops')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="E-commerce Shops"
        description="Connect Shopee and Lazada stores, then manually import recent orders."
        :back-url="route('reports.index')" back-label="Reports overview">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="https://open.shopee.com/" target="_blank" rel="noopener noreferrer">Shopee Open Platform</a>
            <a class="btn btn-outline-primary" href="https://open.lazada.com/" target="_blank" rel="noopener noreferrer">Lazada Open Platform</a>
        </x-slot:actions>
    </x-page-header>

    @if ($result = session('marketplace_result'))
        <div class="alert {{ $result['success'] ? 'alert-success' : 'alert-danger' }}" role="status">
            {{ $result['message'] }}
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-bold mb-2">Please correct the following before importing orders:</p>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card p-2 mb-4">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0 text-primary">Marketplace app configuration</h2>
        </div>
        <div class="card-body">
            <p class="mb-3">Register an app with each marketplace and set its credentials in the server environment. Keep partner keys and app secrets out of source control.</p>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Marketplace</th>
                            <th scope="col">Credentials</th>
                            <th scope="col">Callback URL</th>
                            <th scope="col">Setup</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row">Shopee</th>
                            <td>
                                App settings:
                                <span class="badge {{ $configured['shopee'] ? 'text-bg-success' : 'text-bg-warning' }}">{{ $configured['shopee'] ? 'Ready' : 'Missing configuration' }}</span>
                                <br><code>SHOPEE_PARTNER_ID</code>, <code>SHOPEE_PARTNER_KEY</code>
                            </td>
                            <td><code>{{ config('services.marketplaces.shopee.redirect_url') }}</code></td>
                            <td><a href="https://open.shopee.com/" target="_blank" rel="noopener noreferrer">Register / configure app</a></td>
                        </tr>
                        <tr>
                            <th scope="row">Lazada</th>
                            <td>
                                App settings:
                                <span class="badge {{ $configured['lazada'] ? 'text-bg-success' : 'text-bg-warning' }}">{{ $configured['lazada'] ? 'Ready' : 'Missing configuration' }}</span>
                                <br><code>LAZADA_APP_KEY</code>, <code>LAZADA_APP_SECRET</code>
                            </td>
                            <td><code>{{ config('services.marketplaces.lazada.redirect_url') }}</code></td>
                            <td><a href="https://open.lazada.com/" target="_blank" rel="noopener noreferrer">Register / configure app</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="small text-body-secondary mt-3 mb-0">
                Set each callback URL exactly as displayed in its marketplace app settings. Add the credentials to <code>.env</code>, then run <code>php artisan config:clear</code> if configuration is cached.
            </p>
        </div>
    </div>

    <div class="card p-2 mb-4">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h2 class="h5 mb-0 text-primary">Connected shops</h2>
            <div class="d-flex gap-2">
                @foreach ($platforms as $platform)
                    <a class="btn btn-sm btn-primary {{ $configured[$platform] ? '' : 'disabled' }}"
                        href="{{ $configured[$platform] ? route('marketplace-shops.connect', $platform) : '#' }}"
                        @unless ($configured[$platform]) aria-disabled="true" tabindex="-1" @endunless>
                        Connect {{ ucfirst($platform) }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="card-body">
            @if ($shops->isEmpty())
                <p class="text-body-secondary mb-0">No shops are connected yet. Configure a marketplace app above to enable shop authorization.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Shop</th>
                                <th scope="col">Platform</th>
                                <th scope="col">Shop ID</th>
                                <th scope="col">Connection</th>
                                <th scope="col">Imported orders</th>
                                <th scope="col">Last import</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shops as $shop)
                                <tr>
                                    <th scope="row">{{ $shop->shop_name }}</th>
                                    <td>{{ ucfirst($shop->platform) }} {{ $shop->region ? '('.$shop->region.')' : '' }}</td>
                                    <td>{{ $shop->external_shop_id }}</td>
                                    <td>
                                        <x-status-badge :status="$shop->access_token ? 'active' : 'disconnected'" :label="$shop->access_token ? 'Connected' : 'Disconnected'" />
                                    </td>
                                    <td>{{ $shop->orders_count }}</td>
                                    <td>{{ $shop->last_order_import_at?->format('Y-m-d H:i') ?? 'Never' }}</td>
                                    <td>
                                        @if ($shop->access_token)
                                            <form method="POST" action="{{ route('marketplace-shops.orders.import', $shop) }}" class="d-flex flex-wrap align-items-end gap-2 mb-2">
                                                @csrf
                                                <div>
                                                    <label class="form-label small mb-1" for="from-{{ $shop->id }}">From</label>
                                                    <input class="form-control form-control-sm" id="from-{{ $shop->id }}" type="date" name="from" value="{{ $from }}" max="{{ $to }}" required>
                                                </div>
                                                <div>
                                                    <label class="form-label small mb-1" for="to-{{ $shop->id }}">To</label>
                                                    <input class="form-control form-control-sm" id="to-{{ $shop->id }}" type="date" name="to" value="{{ $to }}" max="{{ $to }}" required>
                                                </div>
                                                <button class="btn btn-sm btn-outline-primary" type="submit">Import orders</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('marketplace-shops.disconnect', $shop) }}"
                                            onsubmit="return confirm('Disconnect this shop? Imported orders will be retained.');">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-danger" type="submit" @disabled(! $shop->access_token)>Disconnect</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card p-2">
        <div class="card-header bg-body">
            <h2 class="h5 mb-0 text-primary">Imported orders</h2>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">Order</th>
                            <th scope="col">Shop</th>
                            <th scope="col">Platform</th>
                            <th scope="col">Date</th>
                            <th scope="col">Status</th>
                            <th scope="col">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <th scope="row">{{ $order->external_order_id }}</th>
                                <td>{{ $order->shop->shop_name }}</td>
                                <td>{{ ucfirst($order->shop->platform) }}</td>
                                <td>{{ $order->ordered_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                <td><x-status-badge :status="$order->status ?: 'unknown'" :label="$order->status ?: 'Unknown'" /></td>
                                <td>{{ $order->total_amount !== null ? trim(($order->currency ? $order->currency.' ' : '').$order->total_amount) : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-body-secondary py-4">No marketplace orders have been imported.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $orders->links() }}</div>
        </div>
    </div>
</div>
@endsection
