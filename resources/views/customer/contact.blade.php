@extends('layouts.app')

@section('title', 'Contact us')

@section('content')
<div class="container-fluid py-3">
    @php
        $whatsappUrl = 'https://wa.me/60104224350?text='.rawurlencode('Hi OMG Computer, I need help with my computer service.');
        $contactEmail = is_string($companyProfile->email) && filter_var($companyProfile->email, FILTER_VALIDATE_EMAIL)
            ? $companyProfile->email
            : 'hello@omgcomputer.my';
        $contactPhone = trim((string) $companyProfile->phone);
    @endphp

    <x-page-header eyebrow="Customer support" title="Contact us"
        description="Get help with repairs, bookings, orders, parcels, or your account.">
    </x-page-header>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body p-4 p-lg-5">
                    <span class="avatar avatar-xl mb-3"><span class="avatar-name rounded-circle bg-primary-subtle text-primary"><span class="bi bi-headset" aria-hidden="true"></span></span></span>
                    <h2 class="h3 mb-3">How can we help?</h2>
                    <p class="text-body-secondary mb-4">Choose the quickest way to contact our team. Please include your repair ticket or order number when available.</p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a class="card h-100 text-decoration-none" href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">
                                <div class="card-body d-flex gap-3 align-items-center">
                                    <span class="fs-2 text-success"><span class="bi bi-whatsapp" aria-hidden="true"></span></span>
                                    <div><span class="d-block fw-semibold text-body">WhatsApp</span><small class="text-body-secondary">Chat with our team</small></div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a class="card h-100 text-decoration-none" href="mailto:{{ $contactEmail }}">
                                <div class="card-body d-flex gap-3 align-items-center">
                                    <span class="fs-2 text-primary"><span class="bi bi-envelope" aria-hidden="true"></span></span>
                                    <div><span class="d-block fw-semibold text-body">Email</span><small class="text-body-secondary">{{ $contactEmail }}</small></div>
                                </div>
                            </a>
                        </div>
                        @if ($contactPhone !== '')
                            <div class="col-md-6">
                                <a class="card h-100 text-decoration-none" href="tel:{{ preg_replace('/[^0-9+]/', '', $contactPhone) }}">
                                    <div class="card-body d-flex gap-3 align-items-center">
                                        <span class="fs-2 text-primary"><span class="bi bi-telephone" aria-hidden="true"></span></span>
                                        <div><span class="d-block fw-semibold text-body">Phone</span><small class="text-body-secondary">{{ $contactPhone }}</small></div>
                                    </div>
                                </a>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <a class="card h-100 text-decoration-none" href="{{ route('repairs.track.form') }}">
                                <div class="card-body d-flex gap-3 align-items-center">
                                    <span class="fs-2 text-primary"><span class="bi bi-search" aria-hidden="true"></span></span>
                                    <div><span class="d-block fw-semibold text-body">Track a repair</span><small class="text-body-secondary">Check your device status</small></div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header fw-semibold">Business information</div>
                <div class="card-body">
                    <h2 class="h6">{{ $companyProfile->legal_name ?: 'OMG Computer' }}</h2>
                    @if ($companyProfile->address)
                        <p class="text-body-secondary mb-3"><span class="bi bi-geo-alt me-2" aria-hidden="true"></span><span class="text-pre-line">{{ $companyProfile->address }}</span></p>
                    @endif
                    @if ($companyProfile->website_url)
                        <a class="d-block mb-2" href="{{ $companyProfile->website_url }}" target="_blank" rel="noopener noreferrer"><span class="bi bi-globe me-2" aria-hidden="true"></span>Visit website</a>
                    @endif
                    @if ($companyProfile->facebook_url)
                        <a class="d-block" href="{{ $companyProfile->facebook_url }}" target="_blank" rel="noopener noreferrer"><span class="bi bi-facebook me-2" aria-hidden="true"></span>Facebook</a>
                    @endif
                </div>
                <div class="card-footer bg-transparent">
                    <a class="btn btn-primary w-100" href="{{ route('bookings.account-create') }}"><span class="bi bi-calendar-plus me-2" aria-hidden="true"></span>Book a service</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
