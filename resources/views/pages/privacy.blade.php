@extends('layouts.guest')

@section('title', 'Privacy Policy')

@section('content')
    @include('pages.partials.hero', [
        'eyebrow' => 'Privacy',
        'heading' => 'Privacy Policy',
        'description' => 'We respect your privacy and are committed to handling your information responsibly.',
    ])

    <section class="pb-8">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm p-2">
                        <div class="card-body p-4 p-lg-5">
                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Information we collect</h2>
                                <p class="text-700 mb-0">We may collect information you provide when contacting us, booking a service, or requesting support. This may include your name, contact details, device information, and the nature of the issue you need help with.</p>
                            </div>
                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">How we use it</h2>
                                <p class="text-700 mb-0">We use your information to communicate with you, assess your service needs, provide support, and improve the quality of our service. We do not sell personal information to third parties.</p>
                            </div>
                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Sharing information</h2>
                                <p class="text-700 mb-0">We may share information with trusted service providers when necessary to provide support or complete a repair request. We only share what is needed and keep those relationships limited to the purpose of the service.</p>
                            </div>
                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Your choices</h2>
                                <p class="text-700 mb-0">You may contact us at any time to request access to, correction of, or deletion of your personal information, subject to legal or operational requirements.</p>
                            </div>
                            <div class="mb-0">
                                <h2 class="h4 fw-bold text-1000">Updates</h2>
                                <p class="text-700 mb-0">We may update this policy from time to time. Any changes will be reflected on this page with the updated date.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
