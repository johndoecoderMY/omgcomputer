@extends('layouts.app')

@section('title', 'Website Package & Structure')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Website management" title="Website package & structure"
        description="Customise the business platform package and preview the public, customer and admin website flow."
        :back-url="route('development.flowcharts.index')" back-label="Flow charts" />

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-lg-4"><div class="card h-100"><div class="card-body"><span class="bi bi-globe2 fs-2 text-primary"></span><h2 class="h5 mt-3">Public website</h2><p class="text-body-secondary mb-0">Landing page, services, packages, shop, tracking and contact pages.</p></div></div></div>
        <div class="col-lg-4"><div class="card h-100"><div class="card-body"><span class="bi bi-person-check fs-2 text-success"></span><h2 class="h5 mt-3">Customer panel</h2><p class="text-body-secondary mb-0">Authentication, bookings, orders, billing, repairs and account tools.</p></div></div></div>
        <div class="col-lg-4"><div class="card h-100"><div class="card-body"><span class="bi bi-shield-check fs-2 text-danger"></span><h2 class="h5 mt-3">Admin &amp; members</h2><p class="text-body-secondary mb-0">Operations, inventory, customers, reports, integrations and permissions.</p></div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <form class="card h-100" method="POST" action="{{ route('development.website-package.update') }}">
                @csrf
                @method('PUT')
                <div class="card-header"><h2 class="h5 mb-0">Package configuration</h2></div>
                <div class="card-body">
                    <label class="form-label" for="website-package-notes">Package summary</label>
                    <textarea class="form-control mb-3" id="website-package-notes" name="website_package_notes" rows="5" maxlength="5000" placeholder="Describe the recommended website package.">{{ old('website_package_notes', $websitePackage->package_notes ?: 'One complete OMG Computer business platform: public website, customer portal, online shop, billing, repair operations and admin management.') }}</textarea>
                    <label class="form-label" for="website-pages">Public website structure</label>
                    <textarea class="form-control" id="website-pages" name="website_pages" rows="10" maxlength="8000" required>{{ old('website_pages', implode("\n", $pages)) }}</textarea>
                    <div class="form-text">Add one page per line. The flow chart updates while you edit.</div>
                </div>
                <div class="card-footer text-end"><button class="btn btn-primary" type="submit"><span class="bi bi-save me-2"></span>Save package</button></div>
            </form>
        </div>
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div><h2 class="h5 mb-1">Website flow charts</h2><div class="small text-body-secondary">Use the built-in Development Flow Charts editor.</div></div>
                    <div class="d-flex gap-2">
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('development.flowcharts.index', ['category' => 'website']) }}"><span class="bi bi-diagram-3 me-1"></span>All flow charts</a>
                        <a class="btn btn-sm btn-primary" href="{{ route('development.flowcharts.create', ['category' => 'website_structure']) }}"><span class="bi bi-plus me-1"></span>Create flow chart</a>
                    </div>
                </div>
                <div class="card-body">
                    @forelse ($websiteFlowCharts as $flowChart)
                        <div class="border rounded-3 p-3 mb-2 d-flex justify-content-between align-items-center gap-3">
                            <div>
                                <div class="fw-semibold">{{ $flowChart->title ?: $flowChart->categoryLabel() }}</div>
                                <div class="small text-body-secondary">{{ $flowChart->categoryLabel() }} · {{ count($flowChart->steps ?? []) }} nodes</div>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('development.flowcharts.show', $flowChart) }}">Open</a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('development.flowcharts.edit', $flowChart) }}">Edit</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center border rounded-3 py-5 bg-body-tertiary">
                            <span class="bi bi-diagram-3 fs-1 text-primary"></span>
                            <h3 class="h5 mt-3">No website flow chart yet</h3>
                            <p class="text-body-secondary mb-3">Create the website journey, structure, or sitemap using the built-in flow chart editor.</p>
                            <a class="btn btn-primary" href="{{ route('development.flowcharts.create', ['category' => 'website_structure']) }}"><span class="bi bi-plus me-1"></span>Create website flow chart</a>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
