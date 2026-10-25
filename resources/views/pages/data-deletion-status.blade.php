@extends('layouts.guest')

@section('title', 'Data Deletion Status')

@section('content')
    @include('pages.partials.hero', [
        'eyebrow' => 'Privacy',
        'heading' => 'Data Deletion Status',
        'description' => 'Status for your Meta data-deletion request.',
    ])

    <section class="pb-8">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-2">
                        <div class="card-body p-4 p-lg-5 text-center">
                            <span class="bi bi-check-circle-fill text-success display-5" aria-hidden="true"></span>
                            <h2 class="h4 fw-bold text-1000 mt-3">Deletion request completed</h2>
                            <p class="text-700 mb-2">
                                The Meta ad-account and Page connections associated with your Facebook account have been deleted.
                            </p>
                            <p class="small text-body-secondary mb-0">
                                Completed {{ $deletionRequest->completed_at->format('M j, Y g:i A') }}.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
