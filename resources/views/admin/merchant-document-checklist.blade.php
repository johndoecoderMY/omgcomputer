@extends('layouts.app')

@section('title', 'Merchant document checklist')

@section('content')
<div class="container-fluid py-3" data-checklist-storage-key="merchant-documents-v1-{{ auth()->id() }}">
    <x-page-header eyebrow="Business setup" title="Merchant document checklist"
        description="A private preparation checklist for payment-provider onboarding. Tick items as you collect them, then print or save this page." />

    <div class="alert alert-info">
        <strong>Home-based business:</strong> use the front-door, workspace, and home/building exterior photos below instead of storefront signboard and shop-interior photos. Confirm with your payment provider if they request any other evidence.
        This checklist does not upload or store document copies.
    </div>

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between small mb-1">
                    <span>Checklist progress</span>
                    <span data-checklist-progress-label>0 of 12 complete</span>
                </div>
                <div class="progress" role="progressbar" aria-label="Checklist progress" aria-valuemin="0" aria-valuemax="12" aria-valuenow="0">
                    <div class="progress-bar" data-checklist-progress-bar style="width: 0%"></div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary" type="button" data-checklist-reset>Reset checklist</button>
                <button class="btn btn-primary" type="button" onclick="window.print()">
                    <span class="bi bi-printer me-2" aria-hidden="true"></span>Print checklist
                </button>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header fw-semibold">Required documents</div>
                <div class="list-group list-group-flush">
                    <label class="list-group-item d-flex gap-3">
                        <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" data-checklist-item>
                        <span><strong>Front door photo</strong><span class="d-block small text-body-secondary">Clearly show your home’s unit number.</span></span>
                    </label>
                    <label class="list-group-item d-flex gap-3">
                        <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" data-checklist-item>
                        <span><strong>Dedicated workspace photo</strong><span class="d-block small text-body-secondary">Show the area where you operate the business from home.</span></span>
                    </label>
                    <label class="list-group-item d-flex gap-3">
                        <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" data-checklist-item>
                        <span><strong>Home/building exterior photos</strong><span class="d-block small text-body-secondary">Provide exterior photos in place of storefront exterior/interior photos.</span></span>
                    </label>
                    <label class="list-group-item d-flex gap-3">
                        <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" data-checklist-item>
                        <span><strong>Product or inventory photos</strong><span class="d-block small text-body-secondary">Show current stock or the items you sell.</span></span>
                    </label>
                    <label class="list-group-item d-flex gap-3">
                        <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" data-checklist-item>
                        <span><strong>Special license <span class="badge text-bg-secondary">If applicable</span></strong><span class="d-block small text-body-secondary">Include any relevant regulatory or business licenses.</span></span>
                    </label>
                    <label class="list-group-item d-flex gap-3">
                        <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" data-checklist-item>
                        <span><strong>Proof of address</strong><span class="d-block small text-body-secondary">Latest utility, telco, internet bill, or tenancy agreement. The name must match your company or owner’s name.</span></span>
                    </label>
                    <label class="list-group-item d-flex gap-3">
                        <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" data-checklist-item>
                        <span><strong>E-wallet sales evidence</strong><span class="d-block small text-body-secondary">A recent sales invoice or proof of payment from a customer.</span></span>
                    </label>
                    <label class="list-group-item d-flex gap-3">
                        <input class="form-check-input flex-shrink-0 mt-1" type="checkbox" data-checklist-item>
                        <span><strong>Foreign card use case <span class="badge text-bg-secondary">Only if requested</span></strong><span class="d-block small text-body-secondary">Prepare a brief explanation of the customers, countries, and business/payment flow involved.</span></span>
                    </label>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header fw-semibold">Live website requirements</div>
                <div class="card-body">
                    <p class="small text-body-secondary">Check that these pages and business details are publicly accessible on the live site.</p>
                    <div class="vstack gap-3">
                        <label class="d-flex gap-3">
                            <input class="form-check-input flex-shrink-0" type="checkbox" data-checklist-item>
                            <span><a href="{{ route('refund-policy') }}" target="_blank" rel="noopener">Refund policy</a><span class="d-block small text-body-secondary">Clearly posted and easy to find.</span></span>
                        </label>
                        <label class="d-flex gap-3">
                            <input class="form-check-input flex-shrink-0" type="checkbox" data-checklist-item>
                            <span><a href="{{ route('privacy') }}" target="_blank" rel="noopener">Privacy policy</a><span class="d-block small text-body-secondary">Clearly posted and easy to find.</span></span>
                        </label>
                        <label class="d-flex gap-3">
                            <input class="form-check-input flex-shrink-0" type="checkbox" data-checklist-item>
                            <span><a href="{{ route('shipping-policy') }}" target="_blank" rel="noopener">Shipping / return policy</a><span class="d-block small text-body-secondary">Clearly posted and easy to find.</span></span>
                        </label>
                        <label class="d-flex gap-3">
                            <input class="form-check-input flex-shrink-0" type="checkbox" data-checklist-item>
                            <span><a href="{{ url('/#contact') }}" target="_blank" rel="noopener">Business address and contact details</a><span class="d-block small text-body-secondary">Visible on the live website and up to date.</span></span>
                        </label>
                    </div>
                    <hr>
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('admin.company-profile.edit') }}">
                        <span class="bi bi-building me-2" aria-hidden="true"></span>Review company profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <p class="small text-body-secondary mt-3 mb-0">Progress is saved in this browser only. Do not upload sensitive documents here; submit them through your payment provider’s secure channel.</p>
</div>
<style>
    @media print {
        .navbar-vertical, .navbar-top, .btn, footer { display: none !important; }
        .container-fluid { width: 100% !important; }
        .card { break-inside: avoid; }
    }
</style>
<script>
(() => {
    const root = document.querySelector('[data-checklist-storage-key]');
    if (!root) return;

    const items = [...root.querySelectorAll('[data-checklist-item]')];
    const storageKey = root.dataset.checklistStorageKey;
    const progressBar = root.querySelector('[data-checklist-progress-bar]');
    const progress = progressBar.closest('[role="progressbar"]');
    const progressLabel = root.querySelector('[data-checklist-progress-label]');
    const updateProgress = () => {
        const complete = items.filter((item) => item.checked).length;
        progressLabel.textContent = `${complete} of ${items.length} complete`;
        progress.setAttribute('aria-valuenow', String(complete));
        progressBar.style.width = `${items.length ? complete / items.length * 100 : 0}%`;
    };

    const savedState = JSON.parse(localStorage.getItem(storageKey) || '[]');
    items.forEach((item, index) => {
        item.checked = savedState[index] === true;
        item.addEventListener('change', () => {
            localStorage.setItem(storageKey, JSON.stringify(items.map((entry) => entry.checked)));
            updateProgress();
        });
    });
    root.querySelector('[data-checklist-reset]').addEventListener('click', () => {
        items.forEach((item) => item.checked = false);
        localStorage.removeItem(storageKey);
        updateProgress();
    });
    updateProgress();
})();
</script>
@endsection
