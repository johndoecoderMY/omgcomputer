@extends('layouts.guest')

@section('title', 'Privacy Policy')

@section('content')
    @include('pages.partials.hero', [
        'eyebrow' => 'Privacy',
        'heading' => 'Privacy Policy',
        'description' => 'How OMG Computer collects, uses, and protects information when you browse, contact us, book a service, or place an order.',
        'image_path' => 'assets/img/icons/shield.png',
        'image_alt' => 'Shield illustration representing privacy and data protection.',
        'image_width' => 512,
        'image_height' => 512,
        'image_fit' => 'contain',
        'compact' => true,
    ])

    <section class="pb-3 pb-lg-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm p-1">
                        <div class="card-body p-3 p-lg-4">
                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">{{ __('Information we collect') }}</h2>
                                <p class="text-700 mb-0">{{ __('Depending on how you use the website, we may collect your name, email address, phone number, account and order details, delivery address, booking requests, messages, and information about your device or requested repair. We also receive information you choose to provide when asking about products, services, or a website project.') }}</p>
                            </div>
                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">{{ __('How we use it') }}</h2>
                                <p class="text-700 mb-0">{{ __('We use this information to respond to enquiries, manage accounts and bookings, provide repair and other services, process orders and payments, arrange delivery, send service updates, protect the website, and meet applicable record-keeping obligations. We do not sell personal information.') }}</p>
                            </div>
                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">{{ __('Sharing information') }}</h2>
                                <p class="text-700 mb-0">{{ __('Online shop checkout is handled through CHiP. We receive transaction and payment-status information needed to manage an order; payment processing is also subject to the provider’s terms and privacy practices. We may share relevant information with hosting, email, payment, courier, or other service providers only as needed to operate the website or fulfil a request. Social platforms you choose to connect with are governed by their own privacy policies.') }}</p>
                            </div>
                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">{{ __('Devices and customer data') }}</h2>
                                <p class="text-700 mb-0">{{ __('Please back up important files before handing over a device for service. We access files only when needed for the requested work and with your permission. Repair work can involve risks to stored data, so we cannot guarantee against data loss.') }}</p>
                            </div>
                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">{{ __('Website operation and retention') }}</h2>
                                <p class="text-700 mb-0">{{ __('The website uses session and account data to provide features such as sign-in, bookings, and shopping carts. We keep information for as long as needed for the service, support, security, and applicable legal or accounting requirements, then delete or de-identify it where appropriate.') }}</p>
                            </div>
                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">{{ __('Your choices') }}</h2>
                                <p class="text-700 mb-0">{{ __('You may contact us to request access to or correction of your personal information, or to ask for deletion. We may need to retain some records to complete a service, resolve a dispute, protect the website, or meet legal and accounting obligations. To disconnect Meta app data, see our ') }}<a href="{{ route('data-deletion.instructions') }}">{{ __('data deletion instructions') }}</a>.</p>
                            </div>
                            <div class="mb-0">
                                <h2 class="h4 fw-bold text-1000">{{ __('Contact and updates') }}</h2>
                                <p class="text-700 mb-0">{{ __('For a privacy question or request, contact us through the ') }}<a href="{{ url('/') }}#contact">{{ __('website contact section') }}</a>{{ __('. We may update this policy when our services or practices change; the current version will be posted here.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
