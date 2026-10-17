@extends('layouts.guest')

@section('title', 'Terms & Conditions')

@section('content')
    @include('pages.partials.hero', [
        'eyebrow' => 'Terms of service',
        'heading' => 'Terms & Conditions',
        'description' => 'By using our services, you agree to the terms described below.',
    ])

    <section class="pb-8">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm p-2">
                        <div class="card-body p-4 p-lg-5">
                            <ol class="text-700 ps-3 mb-0">
                                <li class="mb-4"><span class="fw-semibold text-1000">Service scope:</span> We provide repair, troubleshooting, diagnostics, and upgrade support for computer systems and related devices. Scope and pricing may vary based on the issue and requested work.</li>
                                <li class="mb-4"><span class="fw-semibold text-1000">Customer information:</span> You agree to provide accurate information about the device, issue, and any relevant conditions that may affect diagnosis or repair.</li>
                                <li class="mb-4"><span class="fw-semibold text-1000">Diagnosis and recommendations:</span> We may recommend a repair, component replacement, data backup, or other steps based on our assessment. Final approval is required before performing additional work.</li>
                                <li class="mb-4"><span class="fw-semibold text-1000">Device ownership and risk:</span> While in our care, devices are handled responsibly, but we are not liable for pre-existing damage, data loss, or issues outside the scope of the agreed repair work unless caused by our direct negligence.</li>
                                <li class="mb-4"><span class="fw-semibold text-1000">Payment:</span> Charges for services, parts, and labor are subject to prior agreement. Payment is expected upon completion unless otherwise arranged in writing.</li>
                                <li class="mb-0"><span class="fw-semibold text-1000">Changes to terms:</span> These terms may be updated at any time. Continued use of our services after changes are published constitutes acceptance of the revised terms.</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
