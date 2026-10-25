@extends('layouts.guest')

@section('title', 'Data Deletion Instructions')

@section('content')
    @include('pages.partials.hero', [
        'eyebrow' => 'Privacy',
        'heading' => 'Data Deletion Instructions',
        'description' => 'How to disconnect Facebook data and request deletion of information associated with this app.',
    ])

    <section class="pb-8">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm p-2">
                        <div class="card-body p-4 p-lg-5">
                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Remove the app from Facebook</h2>
                                <p class="text-700 mb-0">
                                    In Facebook, open <strong>Settings &amp; privacy → Settings → Business integrations</strong>
                                    (or <strong>Apps and websites</strong>), choose the OMG Computer app, and select
                                    <strong>Remove</strong>. This revokes the app’s Facebook authorization.
                                </p>
                            </div>
                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Meta callback URL</h2>
                                <p class="text-700 mb-0">
                                    Enter this URL in the Meta app’s data-deletion callback settings:
                                    <code>{{ route('data-deletion.callback') }}</code>.
                                    Meta sends a signed request to this endpoint; the app verifies it before deleting
                                    the matching Page and ad-account connections.
                                </p>
                            </div>
                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Request deletion from OMG Computer</h2>
                                <p class="text-700 mb-0">
                                    Use the app’s data-deletion request on Facebook, or contact us using the email or
                                    WhatsApp details on our <a href="{{ url('/') }}#contact">contact page</a>.
                                    Include the Facebook account you used to connect, but do not send your password or
                                    access token. We will remove the Meta Page and advertising account connections
                                    associated with that Facebook account.
                                </p>
                            </div>
                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">What is deleted</h2>
                                <p class="text-700 mb-0">
                                    When Facebook submits a verified deletion callback, this app deletes the associated
                                    encrypted Page and ad-account access tokens and connection records. The request is
                                    processed immediately. Content already published to Facebook is not removed from
                                    Facebook by this callback; remove published posts directly on Facebook.
                                </p>
                            </div>
                            <div class="mb-0">
                                <h2 class="h4 fw-bold text-1000">Check request status</h2>
                                <p class="text-700 mb-0">
                                    Facebook’s deletion callback returns a private status URL and confirmation code.
                                    Open the supplied URL to see whether the deletion request has been completed.
                                    Keep that URL private. For more information about our handling of personal
                                    information, read our <a href="{{ route('privacy') }}">privacy policy</a>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
