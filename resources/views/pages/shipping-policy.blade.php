@extends('layouts.guest')

@section('title', 'Shipping Policy')

@section('content')
    @include('pages.partials.hero', [
        'eyebrow' => 'Shipping & delivery',
        'heading' => 'Shipping Policy',
        'description' => 'How online shop orders are processed, delivered, or collected.',
        'compact' => true,
    ])

    <section class="pb-3 pb-lg-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm p-1">
                        <div class="card-body p-3 p-lg-4">
                            <div class="alert alert-info border-0 mb-3">
                                The current online checkout collects an optional delivery address and shows the product subtotal. It does not calculate a delivery charge; contact us to confirm delivery arrangements and any applicable charge before completing payment if these are not already agreed.
                            </div>

                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">Order processing</h2>
                                <p class="text-700 mb-0">Orders are processed after payment is confirmed. Availability, order details, and any delivery arrangements are checked before fulfilment. If an item is unavailable or we need more information, we will contact you about the order.</p>
                            </div>

                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">Delivery arrangements</h2>
                                <p class="text-700 mb-0">Delivery method, destination coverage, charges, and estimated timing are confirmed for each order. Courier availability may depend on the destination and the size or handling requirements of the goods. Do not assume international delivery is available unless we confirm it.</p>
                            </div>

                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">Order tracking</h2>
                                <p class="text-700 mb-0">When a courier shipment is arranged, we will provide a tracking reference when available. Carrier tracking may take time to update after collection.</p>
                            </div>

                            <div class="mb-3">
                                <h2 class="h4 fw-bold text-1000">Collection</h2>
                                <p class="text-700 mb-0">Collection is available only when agreed for the order. Please wait for confirmation that your order is ready before travelling and bring your order reference.</p>
                            </div>

                            <div class="mb-0">
                                <h2 class="h4 fw-bold text-1000">Damaged, incorrect, or missing items</h2>
                                <p class="text-700 mb-0">Contact us promptly with your order reference, details, and photographs if an order arrives damaged, incorrect, or incomplete so we can investigate with the carrier or supplier. This request to report promptly does not remove any rights you have under applicable law.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
