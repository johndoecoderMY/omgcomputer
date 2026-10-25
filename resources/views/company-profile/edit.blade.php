@extends('layouts.app')

@section('title', 'Company Profile')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Business details" title="Company profile"
        description="Manage the legal and contact details used on invoices and the public website."
        :back-url="route('dashboard')" back-label="Dashboard" />

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.company-profile.update') }}">
        @csrf
        @method('PUT')
        <div class="card p-2">
            <div class="card-header bg-body">
                <h2 class="h5 mb-1">Legal and tax registration</h2>
                <p class="small text-body-secondary mb-0">Tax numbers are included on billing PDFs and are not shown on the public website.</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="legal-name">Registered business name</label>
                        <input class="form-control" id="legal-name" name="legal_name" maxlength="255" value="{{ old('legal_name', $companyProfile->legal_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ssm-number">SSM registration number</label>
                        <input class="form-control" id="ssm-number" name="ssm_registration_number" maxlength="100" value="{{ old('ssm_registration_number', $companyProfile->ssm_registration_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tax-id">Tax identification number (TIN)</label>
                        <input class="form-control" id="tax-id" name="tax_identification_number" maxlength="100" value="{{ old('tax_identification_number', $companyProfile->tax_identification_number) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="sst-number">SST registration number</label>
                        <input class="form-control" id="sst-number" name="sst_registration_number" maxlength="100" value="{{ old('sst_registration_number', $companyProfile->sst_registration_number) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="business-address">Business address</label>
                        <textarea class="form-control" id="business-address" name="address" rows="3" maxlength="2000">{{ old('address', $companyProfile->address) }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="business-phone">Business phone</label>
                        <input class="form-control" id="business-phone" name="phone" maxlength="50" value="{{ old('phone', $companyProfile->phone) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="business-email">Business email</label>
                        <input class="form-control" id="business-email" name="email" type="email" maxlength="255" value="{{ old('email', $companyProfile->email) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="website-url">Website</label>
                        <input class="form-control" id="website-url" name="website_url" type="url" maxlength="255" value="{{ old('website_url', $companyProfile->website_url) }}" placeholder="https://example.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="facebook-url">Facebook page URL</label>
                        <input class="form-control" id="facebook-url" name="facebook_url" type="url" maxlength="255" value="{{ old('facebook_url', $companyProfile->facebook_url) }}" placeholder="https://www.facebook.com/your-page">
                        <div class="form-text">Only Facebook page links from facebook.com or fb.me are accepted.</div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-body text-end">
                <button class="btn btn-primary" type="submit"><span class="bi bi-save me-2" aria-hidden="true"></span>Save company profile</button>
            </div>
        </div>
    </form>
</div>
@endsection
