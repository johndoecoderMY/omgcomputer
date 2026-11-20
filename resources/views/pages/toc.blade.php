@extends('layouts.guest')

@section('title', 'Terms & Conditions')

@section('content')
    @include('pages.partials.hero', [
        'eyebrow' => 'Terms of service',
        'heading' => 'Terms & Conditions',
        'description' => 'Terms for using this website, requesting services, and placing online shop orders.',
        'image_path' => 'assets/img/icons/docs.png',
        'image_alt' => 'Document icon representing the website terms and conditions.',
        'image_width' => 128,
        'image_height' => 128,
        'image_fit' => 'contain',
        'compact' => true,
    ])

    <section class="pb-3 pb-lg-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm p-1">
                        <div class="card-body p-3 p-lg-4">
                            <ol class="text-700 ps-3 mb-0">
                                <li class="mb-3"><span class="fw-semibold text-1000">{{ __('Services and bookings:') }}</span> {{ __('The website describes computer troubleshooting, repair, upgrades, ready-built computers, and website design services. A booking is a request and is not confirmed until our team confirms availability and the appointment. We will explain the proposed work and price; additional work requires your approval.') }}</li>
                                <li class="mb-3"><span class="fw-semibold text-1000">{{ __('Device information and data:') }}</span> {{ __('Please provide accurate information about the device and issue. Back up important data before handing over a device where possible, and do not provide account passwords unless necessary and agreed for the specific work. We will handle devices with reasonable care; nothing here excludes responsibility that cannot legally be excluded.') }}</li>
                                <li class="mb-3"><span class="fw-semibold text-1000">{{ __('Shop orders:') }}</span> {{ __('Product availability, prices, and descriptions are shown on the relevant product pages and checkout. Orders are subject to stock and order confirmation. A delivery address is optional at checkout, and delivery arrangements or charges may need to be confirmed separately before payment. Product warranties are those stated for the item or provided by its manufacturer, subject to applicable law.') }}</li>
                                <li class="mb-3"><span class="fw-semibold text-1000">{{ __('Payments and refunds:') }}</span> {{ __('Online shop payments are processed through CHiP. Payment-provider terms also apply to that transaction. Our ') }}<a href="{{ route('refund-policy') }}">{{ __('Refund Policy') }}</a>{{ __(' explains how to raise a request; it does not limit statutory rights.') }}</li>
                                <li class="mb-3"><span class="fw-semibold text-1000">{{ __('Website projects and previews:') }}</span> {{ __('Website previews are examples of design work. The scope, price, deliverables, and timeline for a commissioned website project will be agreed separately before work begins.') }}</li>
                                <li class="mb-3"><span class="fw-semibold text-1000">{{ __('Privacy and third parties:') }}</span> {{ __('Our handling of personal information is described in the ') }}<a href="{{ route('privacy') }}">{{ __('Privacy Policy') }}</a>{{ __('. Payment, courier, and external services may have their own terms and privacy policies.') }}</li>
                                <li class="mb-0"><span class="fw-semibold text-1000">{{ __('Applicable rights and updates:') }}</span> {{ __('These terms are subject to applicable Malaysian law and do not remove consumer rights that cannot be excluded. We may update the website terms as our services change; the version posted on this page applies to future use, subject to applicable law.') }}</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
