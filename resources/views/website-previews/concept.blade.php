@extends('layouts.guest')

@section('title', $concept['brand'].' — Website concept')
@section('meta_description', $concept['intro'].' View this Bootstrap-based website design concept by OMG Computer.')
@section('og_type', 'website')

@section('content')
<style>
    .concept-site {
        --concept-ink: #26241f;
        --concept-muted: #746f66;
        --concept-paper: #fbf8f2;
        --concept-accent: #936444;
        color: var(--concept-ink);
        background: var(--concept-paper);
    }
    .concept-site-dashboard {
        --concept-ink: #13253a;
        --concept-muted: #5c6e80;
        --concept-paper: #f3f7fa;
        --concept-accent: #43ad94;
    }
    .concept-site-pos {
        --concept-ink: #292a27;
        --concept-muted: #6b6d66;
        --concept-paper: #f7f6f1;
        --concept-accent: #e17654;
    }
    .concept-site .concept-nav,
    .concept-site .concept-surface { background: var(--concept-paper); }
    .concept-site .text-body-secondary { color: var(--concept-muted) !important; }
    .concept-site .btn-concept {
        color: #fff;
        background: var(--concept-accent);
        border-color: var(--concept-accent);
    }
    .concept-site .btn-concept:hover,
    .concept-site .btn-concept:focus { color: #fff; filter: brightness(.9); }
    .concept-site .concept-hero { padding-block: clamp(3rem, 8vw, 7rem); }
    .concept-site .concept-image {
        width: 100%;
        height: 100%;
        min-height: 220px;
        object-fit: cover;
    }
    .concept-site .concept-feature {
        overflow: hidden;
        border: 0;
        border-radius: 1rem;
        background: #fff;
    }
    .concept-site .concept-feature img { aspect-ratio: 3 / 2; object-fit: cover; }
    .concept-site .concept-mark {
        display: inline-flex;
        width: 2.5rem;
        height: 2.5rem;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: var(--concept-accent);
        background: color-mix(in srgb, var(--concept-accent) 12%, white);
    }
    .dashboard-panel { background: #fff; }
    .dashboard-chart { width: 100%; height: auto; }
</style>

<div class="concept-site concept-site-{{ $concept['theme'] }}">
    <nav class="concept-nav navbar navbar-expand-lg border-bottom" aria-label="{{ $concept['brand'] }} demo navigation">
        <div class="container py-2">
            <a class="navbar-brand fw-bold text-reset" href="#concept-top">{{ $concept['brand'] }}</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#conceptNavigation" aria-controls="conceptNavigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="conceptNavigation">
                <div class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                    <a class="nav-link" href="#about">About</a>
                    <a class="nav-link" href="#gallery">Highlights</a>
                    <a class="btn btn-concept rounded-pill px-4 mt-2 mt-lg-0" href="#contact">{{ $concept['primary_action'] }}</a>
                </div>
            </div>
        </div>
    </nav>

    <div id="concept-top">
        @if ($concept['theme'] === 'dashboard')
            <section class="py-4 py-lg-5">
                <div class="container">
                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                        <div>
                            <p class="text-uppercase small fw-bold mb-2" style="color: var(--concept-accent)">{{ $concept['eyebrow'] }}</p>
                            <h1 class="h2 fw-bold mb-1">{{ $concept['headline'] }}</h1>
                            <p class="text-body-secondary mb-0">{{ $concept['intro'] }}</p>
                        </div>
                        <button class="btn btn-light border rounded-3" type="button" disabled><span class="bi bi-calendar3 me-2" aria-hidden="true"></span>Last 30 days</button>
                    </div>
                    <div class="row g-3 mb-4">
                        @foreach ([['Revenue', '$24,680', '+12.8%', 'bi-currency-dollar'], ['Orders', '1,284', '+8.2%', 'bi-bag'], ['Customers', '936', '+5.4%', 'bi-people'], ['Conversion', '4.82%', '+1.6%', 'bi-graph-up-arrow']] as $metric)
                            <div class="col-6 col-xl-3">
                                <article class="dashboard-panel card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <span class="small text-body-secondary">{{ $metric[0] }}</span>
                                        <div class="d-flex justify-content-between align-items-end gap-2 mt-2">
                                            <strong class="h3 mb-0">{{ $metric[1] }}</strong>
                                            <span class="small fw-semibold" style="color: var(--concept-accent)">{{ $metric[2] }}</span>
                                        </div>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <article class="dashboard-panel card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div><h2 class="h5 fw-bold mb-1">Revenue overview</h2><p class="small text-body-secondary mb-0">Performance over the last 30 days</p></div>
                                        <span class="badge rounded-pill bg-success-subtle text-success">+12.8%</span>
                                    </div>
                                    <svg class="dashboard-chart" viewBox="0 0 760 260" role="img" aria-label="Revenue trend line chart">
                                        <g stroke="#e9edf3" stroke-width="1"><path d="M45 35h690M45 85h690M45 135h690M45 185h690M45 235h690"/></g>
                                        <path d="M50 205l75-36 75 13 75-58 75 19 75-89 75 27 75-53 75 19 75-40" fill="none" stroke="#43ad94" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <g fill="#43ad94"><circle cx="50" cy="205" r="5"/><circle cx="125" cy="169" r="5"/><circle cx="200" cy="182" r="5"/><circle cx="275" cy="124" r="5"/><circle cx="350" cy="143" r="5"/><circle cx="425" cy="54" r="5"/><circle cx="500" cy="81" r="5"/><circle cx="575" cy="28" r="5"/><circle cx="650" cy="47" r="5"/><circle cx="725" cy="7" r="5"/></g>
                                    </svg>
                                    <div class="d-flex justify-content-between small text-body-secondary"><span>Oct 1</span><span>Oct 8</span><span>Oct 15</span><span>Oct 22</span><span>Oct 30</span></div>
                                </div>
                            </article>
                        </div>
                        <div class="col-lg-4">
                            <article class="dashboard-panel card border-0 shadow-sm h-100">
                                <div class="card-body p-4">
                                    <h2 class="h5 fw-bold mb-1">Sales by channel</h2>
                                    <p class="small text-body-secondary">Order distribution</p>
                                    <div class="d-flex justify-content-center py-3">
                                        <svg viewBox="0 0 200 200" width="190" role="img" aria-label="Sales channel doughnut chart">
                                            <circle cx="100" cy="100" r="68" fill="none" stroke="#e8edf3" stroke-width="24"/>
                                            <circle cx="100" cy="100" r="68" fill="none" stroke="#43ad94" stroke-width="24" stroke-dasharray="190 237" transform="rotate(-90 100 100)"/>
                                            <circle cx="100" cy="100" r="68" fill="none" stroke="#6684d8" stroke-width="24" stroke-dasharray="102 325" stroke-dashoffset="-190" transform="rotate(-90 100 100)"/>
                                            <text x="100" y="96" text-anchor="middle" fill="#13253a" font-size="25" font-weight="700">1,284</text>
                                            <text x="100" y="118" text-anchor="middle" fill="#78869a" font-size="12">orders</text>
                                        </svg>
                                    </div>
                                    <div class="d-flex justify-content-between small"><span><span class="text-success me-2">●</span>Online</span><strong>56%</strong></div>
                                    <div class="d-flex justify-content-between small mt-2"><span><span class="text-primary me-2">●</span>In store</span><strong>30%</strong></div>
                                    <div class="d-flex justify-content-between small mt-2"><span><span class="text-warning me-2">●</span>Other</span><strong>14%</strong></div>
                                </div>
                            </article>
                        </div>
                    </div>
                    <div class="text-end mt-4"><a class="btn btn-outline-secondary rounded-pill px-4" href="#gallery">{{ $concept['primary_action'] }}</a></div>
                </div>
            </section>
        @elseif ($concept['theme'] === 'pos')
            <section class="py-4 py-lg-5">
                <div class="container">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                        <div>
                            <p class="text-uppercase small fw-bold mb-2" style="color: var(--concept-accent)">{{ $concept['eyebrow'] }}</p>
                            <h1 class="h2 fw-bold mb-1">{{ $concept['brand'] }} <span class="text-body-secondary fw-normal">/ Store</span></h1>
                        </div>
                        <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2"><span class="bi bi-circle-fill me-2" aria-hidden="true"></span>Register open</span>
                    </div>
                    <p class="text-body-secondary mb-4">{{ $concept['intro'] }}</p>
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="d-flex flex-wrap gap-2 mb-3" aria-label="Product categories">
                                <button class="btn btn-dark btn-sm rounded-pill px-3" type="button" disabled>All products</button>
                                <button class="btn btn-light border btn-sm rounded-pill px-3" type="button" disabled>Accessories</button>
                                <button class="btn btn-light border btn-sm rounded-pill px-3" type="button" disabled>Home</button>
                                <button class="btn btn-light border btn-sm rounded-pill px-3" type="button" disabled>Gifts</button>
                            </div>
                            <div class="row row-cols-2 row-cols-md-3 g-3">
                                @foreach ([['Canvas Tote', '$18.00', 'bi-handbag', 'e9d8c6'], ['Desk Bottle', '$24.00', 'bi-cup-straw', 'd4e4dd'], ['Everyday Mug', '$16.00', 'bi-cup-hot', 'ead9d5'], ['Travel Pouch', '$22.00', 'bi-bag', 'e4e6f2'], ['Soft Notebook', '$12.00', 'bi-journal', 'f0e2c8'], ['Mini Planter', '$19.00', 'bi-flower1', 'd9e8dc']] as $product)
                                    <div class="col">
                                        <article class="card h-100 border-0 shadow-sm">
                                            <div class="d-flex align-items-center justify-content-center rounded-top p-3" style="background: #{{ $product[3] }}; min-height: 125px">
                                                <span class="bi {{ $product[2] }} display-5 text-dark" aria-hidden="true"></span>
                                            </div>
                                            <div class="card-body p-3">
                                                <h2 class="h6 fw-bold mb-1">{{ $product[0] }}</h2>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="small text-body-secondary">{{ $product[1] }}</span>
                                                    <button class="btn btn-sm btn-outline-secondary rounded-circle" type="button" aria-label="Add {{ $product[0] }}" disabled><span class="bi bi-plus" aria-hidden="true"></span></button>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <aside class="col-lg-4" aria-label="Example checkout basket">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h2 class="h5 fw-bold mb-0">Current sale</h2>
                                        <span class="badge text-bg-light">2 items</span>
                                    </div>
                                    <div class="d-flex justify-content-between border-bottom py-3"><span>Canvas Tote <span class="small text-body-secondary">× 1</span></span><strong>$18.00</strong></div>
                                    <div class="d-flex justify-content-between border-bottom py-3"><span>Everyday Mug <span class="small text-body-secondary">× 1</span></span><strong>$16.00</strong></div>
                                    <div class="d-flex justify-content-between py-3"><span>Subtotal</span><span>$34.00</span></div>
                                    <div class="d-flex justify-content-between fw-bold fs-5 mb-3"><span>Total</span><span>$34.00</span></div>
                                    <button class="btn btn-concept w-100 rounded-pill" type="button" disabled>{{ $concept['primary_action'] }}</button>
                                    <p class="small text-center text-body-secondary mt-3 mb-0">Sample checkout · No payment is processed</p>
                                </div>
                            </div>
                        </aside>
                    </div>
                    <div class="text-end mt-4"><a class="btn btn-outline-secondary rounded-pill px-4" href="#gallery">View catalogue and inventory screens</a></div>
                </div>
            </section>
        @else
        <section class="concept-hero">
            <div class="container">
                <div class="row align-items-center g-5">
                    <div class="col-lg-6">
                        <p class="text-uppercase small fw-bold mb-3" style="color: var(--concept-accent)">{{ $concept['eyebrow'] }}</p>
                        <h1 class="display-3 fw-bold lh-1 mb-4">{{ $concept['headline'] }}</h1>
                        <p class="fs-5 text-body-secondary mb-4">{{ $concept['intro'] }}</p>
                        <a class="btn btn-concept btn-lg rounded-pill px-4" href="#gallery">{{ $concept['primary_action'] }}</a>
                    </div>
                    <div class="col-lg-6">
                        <div class="rounded-4 overflow-hidden shadow">
                            <img class="concept-image" src="{{ Storage::disk('public')->url($images[0]) }}" alt="{{ $concept['brand'] }} website design concept">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-5 border-top border-bottom" id="about">
            <div class="container py-lg-4">
                <div class="row align-items-center g-4">
                    <div class="col-md-2 text-center">
                        <span class="concept-mark fs-4" aria-hidden="true"><span class="bi bi-stars"></span></span>
                    </div>
                    <div class="col-md-10">
                        <h2 class="h3 fw-bold mb-2">{{ $concept['section_title'] }}</h2>
                        <p class="text-body-secondary fs-5 mb-0">{{ $concept['section_copy'] }}</p>
                    </div>
                </div>
            </div>
        </section>
        @endif

        <section class="py-5" id="gallery">
            <div class="container py-lg-4">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                    <div>
                        <p class="text-uppercase small fw-bold mb-2" style="color: var(--concept-accent)">{{ $concept['eyebrow'] }}</p>
                        <h2 class="display-6 fw-bold mb-0">{{ $concept['section_title'] }}</h2>
                    </div>
                    <span class="badge rounded-pill text-bg-light border">Bootstrap-based {{ $concept['theme'] === 'dashboard' ? 'dashboard' : ($concept['theme'] === 'pos' ? 'store interface' : 'landing page') }}</span>
                </div>
                <div class="row g-4">
                    @foreach ($images as $image)
                        <div class="col-md-6 col-lg-4">
                            <article class="concept-feature card h-100 shadow-sm">
                                <img src="{{ Storage::disk('public')->url($image) }}" class="card-img-top" alt="{{ $concept['brand'] }} design detail {{ $loop->iteration }}" loading="lazy">
                                <div class="card-body">
                                    <p class="small text-body-secondary text-uppercase fw-semibold mb-2">Design detail {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</p>
                                    <h3 class="h5 fw-bold mb-0">{{ $concept['brand'] }}</h3>
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="py-5" id="contact">
            <div class="container">
                <div class="concept-surface rounded-4 p-4 p-lg-5 text-center shadow-sm">
                    <p class="text-uppercase small fw-bold mb-2" style="color: var(--concept-accent)">Demo concept</p>
                    <h2 class="h2 fw-bold mb-3">A starting point for your website</h2>
                    <p class="text-body-secondary mb-4">This is an illustrative design concept, not a real business website.</p>
                    <a class="btn btn-concept rounded-pill px-4" href="{{ route('website-previews.index') }}">Back to all concepts</a>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
