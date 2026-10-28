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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <div><h2 class="h5 mb-1">Website structure flow</h2><div class="small text-body-secondary">Suggested customer journey and staff access paths</div></div>
                    <a class="btn btn-sm btn-outline-primary" href="{{ url('/') }}" target="_blank" rel="noopener"><span class="bi bi-box-arrow-up-right me-1"></span>View website</a>
                </div>
                <div class="card-body bg-body-tertiary overflow-auto">
                    <div class="min-vh-25" style="min-width:660px">
                        <div class="d-flex justify-content-center"><div class="rounded-pill bg-dark text-white px-4 py-3 fw-semibold">Website visitor</div></div>
                        <div class="text-center text-primary fs-4">↓</div>
                        <div class="d-flex justify-content-center"><div class="rounded-3 border bg-white px-4 py-3 text-center fw-semibold shadow-sm">Landing page / Home</div></div>
                        <div class="text-center text-primary fs-4">↓</div>
                        <div id="website-flow-pages" class="d-flex justify-content-center flex-wrap gap-2 mb-2"></div>
                        <div class="row justify-content-center g-3 mt-2">
                            <div class="col-5 text-center">
                                <div class="text-primary fs-4">↘</div>
                                <div class="rounded-3 border border-success bg-white p-3 shadow-sm">
                                    <span class="bi bi-person-check text-success fs-3"></span>
                                    <div class="fw-semibold mt-1">Customer / User</div>
                                    <div class="small text-body-secondary">Sign in or register</div>
                                    <div class="text-success my-1">↓</div>
                                    <div class="bg-success-subtle rounded-2 p-2 small">Customer panel<br>Shop · Billing · Repairs · Orders</div>
                                </div>
                            </div>
                            <div class="col-5 text-center">
                                <div class="text-primary fs-4">↙</div>
                                <div class="rounded-3 border border-danger bg-white p-3 shadow-sm">
                                    <span class="bi bi-shield-check text-danger fs-3"></span>
                                    <div class="fw-semibold mt-1">Admin / Member</div>
                                    <div class="small text-body-secondary">Role-based sign in</div>
                                    <div class="text-danger my-1">↓</div>
                                    <div class="bg-danger-subtle rounded-2 p-2 small">Admin panel<br>Operations · Reports · Settings</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const textarea = document.getElementById('website-pages');
    const container = document.getElementById('website-flow-pages');

    const renderFlow = () => {
        container.innerHTML = '';
        const pages = [...new Set(textarea.value.split(/\r?\n/).map(page => page.trim()).filter(Boolean))].slice(0, 30);
        if (!pages.length) {
            container.innerHTML = '<span class="text-body-secondary small">Add pages to preview the structure.</span>';
            return;
        }

        pages.forEach((page, index) => {
            const node = document.createElement('span');
            node.className = 'badge rounded-pill text-bg-white border px-3 py-2 fw-normal text-dark shadow-sm';
            node.textContent = `${index + 1}. ${page}`;
            container.appendChild(node);
        });
    };

    textarea.addEventListener('input', renderFlow);
    renderFlow();
})();
</script>
@endpush
