@extends('layouts.guest')

@section('title', 'Refund Policy')

@section('content')
    @include('pages.partials.hero', [
        'eyebrow' => 'Returns & refunds',
        'heading' => 'Refund Policy',
        'description' => 'How to raise a concern about an order, repair, booking, or payment.',
        'compact' => true,
    ])

    <section class="pb-3 pb-lg-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm p-1">
                        <div class="card-body p-3 p-lg-4">
                            <div class="alert alert-info border-0 mb-3">
                                This policy explains how to contact us; it does not limit rights or remedies available under applicable consumer-protection laws.
                            </div>

                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">Products and parts</h2>
                                <p class="text-700 mb-0">If an item is damaged, faulty, incorrect, or not as described, contact us promptly with your order number and supporting details. We will review the issue and explain the available next steps, taking into account the item, its stated warranty, and applicable law. Do not send an item back until return instructions are provided.</p>
                            </div>

                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">Repair and service work</h2>
                                <p class="text-700 mb-0">If work is incomplete or differs from the agreed scope, contact us with your repair or quotation reference so we can review it. Any charges for work completed, parts ordered, or third-party costs will be explained and assessed against the agreement and applicable law.</p>
                            </div>

                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">Cancellations and deposits</h2>
                                <p class="text-700 mb-0">A booking request is subject to confirmation by our team. If you need to cancel or reschedule, contact us as soon as possible. We will confirm any applicable charges or refund for payments already made, including costs for parts ordered specifically for the work, before proceeding where practicable.</p>
                            </div>

                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">Refund processing</h2>
                                <p class="text-700 mb-0">Where a refund is approved, we will confirm the amount and method. Online payments are processed through CHiP, and the time for a refund to appear may depend on the payment provider or bank.</p>
                            </div>

                            <div class="mb-0">
                                <h2 class="h4 fw-bold text-1000">Request help</h2>
                                <p class="text-700 mb-0">Contact us through the <a href="{{ url('/') }}#contact">website contact section</a> and include your name, order or repair reference, purchase date, and a description of the issue.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
