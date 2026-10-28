@extends('layouts.guest')

@section('title', 'Refund Policy')

@section('content')
    @include('pages.partials.hero', [
        'eyebrow' => 'Returns & refunds',
        'heading' => 'Refund Policy',
        'description' => 'Clear information about eligibility, refunds, cancellations, and how to request assistance.',
    ])

    <section class="pb-8">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm p-2">
                        <div class="card-body p-4 p-lg-5">
                            <div class="alert alert-info border-0 mb-4">
                                Refund requests are assessed individually based on the service performed, product condition, and any applicable warranty. Please retain your invoice, receipt, or order reference.
                            </div>

                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Products and parts</h2>
                                <p class="text-700 mb-2">A refund or replacement may be considered for a defective, damaged, or incorrectly supplied product. Requests should be made as soon as the issue is discovered and must include the original receipt.</p>
                                <p class="text-700 mb-0">Products that have been used, modified, physically damaged, installed incorrectly, missing accessories, or returned outside the applicable warranty period may not be eligible for a refund.</p>
                            </div>

                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Repair and service work</h2>
                                <p class="text-700 mb-0">Diagnostic work, completed labour, replacement parts, software licences, and non-refundable third-party charges may be deducted where applicable. A refund will only be considered for verified work that remains incomplete or not as agreed, subject to any manufacturer or supplier warranty.</p>
                            </div>

                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Cancellations and deposits</h2>
                                <p class="text-700 mb-0">An appointment may be cancelled or rescheduled by contacting us before the scheduled time. Deposits and payments for ordered parts or work already performed are subject to confirmation before cancellation; custom orders and specially ordered parts may be non-refundable.</p>
                            </div>

                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Refund processing</h2>
                                <p class="text-700 mb-0">Approved refunds are normally returned to the original payment method. Processing time can vary depending on the payment provider and financial institution. We may request verification before processing a refund.</p>
                            </div>

                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Online card payments</h2>
                                <p class="text-700 mb-0">Online payments may be refunded through the same CHIP checkout used for the original transaction, once an approved claim has been reviewed and authorised by our team.</p>
                            </div>

                            <div class="mb-0">
                                <h2 class="h4 fw-bold text-1000">Request a refund</h2>
                                <p class="text-700 mb-0">Please contact OMG Computer using the details on our website and provide your name, order or repair reference, purchase date, payment amount, and a description of the issue. Do not return an item until our team has provided instructions.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
