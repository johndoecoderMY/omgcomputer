@extends('layouts.guest')

@section('title', 'Shipping Policy')

@section('content')
    @include('pages.partials.hero', [
        'eyebrow' => 'Shipping & delivery',
        'heading' => 'Shipping Policy',
        'description' => 'Information about order delivery, collection, shipping fees, and tracking.',
    ])

    <section class="pb-8">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="card border-0 shadow-sm p-2">
                        <div class="card-body p-4 p-lg-5">
                            <div class="alert alert-info border-0 mb-4">
                                Delivery availability, charges, and estimated times depend on the delivery location, product, carrier, and stock status. Confirmation details are provided with each order.
                            </div>

                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Order processing</h2>
                                <p class="text-700 mb-0">Orders are normally processed after payment has been confirmed. Stock availability and component compatibility will be checked before dispatch. If an item is unavailable, payment is delayed, or additional information is needed, our team may contact you before confirming the delivery schedule.</p>
                            </div>

                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Delivery areas</h2>
                                <p class="text-700 mb-0">We can arrange delivery within Malaysia, subject to courier coverage and the size, weight, and handling requirements of the goods. International delivery is available only when separately confirmed by our team.</p>
                            </div>

                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Delivery time and charges</h2>
                                <p class="text-700 mb-0">Estimated delivery times begin after the order is processed and shipped. Actual delivery time depends on the courier and destination, and may be affected by public holidays, weather, customs, or other circumstances outside our control. Shipping fees are shown or confirmed before payment.</p>
                            </div>

                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Order tracking</h2>
                                <p class="text-700 mb-0">Where a courier is used, a tracking reference may be sent after dispatch. Tracking information should appear on the courier's system after the shipment has been collected or processed.</p>
                            </div>

                            <div class="mb-4">
                                <h2 class="h4 fw-bold text-1000">Collection</h2>
                                <p class="text-700 mb-0">Customers may arrange collection when collection has been offered for their order. Please wait for confirmation that the order is ready before travelling. Bring proof of identity and your order reference.</p>
                            </div>

                            <div class="mb-0">
                                <h2 class="h4 fw-bold text-1000">Damaged or missing deliveries</h2>
                                <p class="text-700 mb-0">Please check the package before accepting it and report visible damage, an incorrect item, or missing goods promptly with your order reference and supporting photographs. Claims should be reported as soon as reasonably possible so that the courier or supplier can investigate.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
