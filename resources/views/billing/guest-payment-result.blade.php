@extends('layouts.guest')

@section('title', __('Payment status'))
@section('meta_robots', 'noindex,nofollow')
@section('meta_description', __('CHIP invoice payment status.'))

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body text-center p-4">
                    @if ($attempt->status === 'paid')
                        <span class="bi bi-check-circle-fill text-success display-5" aria-hidden="true"></span>
                        <h1 class="h3 mt-3">{{ __('Payment confirmed') }}</h1>
                        <p class="mb-0">{{ __('CHIP confirmed payment for invoice :number. Amount received: RM :amount.', ['number' => $attempt->document->number, 'amount' => number_format($attempt->amount, 2)]) }}</p>
                        @if ($receiptEmailed)
                            <p class="small text-body-secondary mt-3 mb-0">{{ __('A PDF receipt was emailed to the address on your invoice.') }}</p>
                        @else
                            <p class="small text-body-secondary mt-3 mb-0">{{ __('We could not email the receipt. Please contact OMG Computer to arrange a copy.') }}</p>
                        @endif
                    @else
                        <span class="bi bi-hourglass-split text-warning display-5" aria-hidden="true"></span>
                        <h1 class="h3 mt-3">{{ __('Payment not confirmed yet') }}</h1>
                        <p>{{ __('CHIP has not confirmed payment for invoice :number. Please wait a moment and check your payment status before trying again.', ['number' => $attempt->document->number]) }}</p>
                        <a class="btn btn-outline-primary mb-3" href="{{ route('billing.guest.chip.return', $attempt->uuid) }}">{{ __('Check payment status') }}</a>
                        <p class="small text-body-secondary mb-0">{{ __('If you have already paid, contact OMG Computer and quote this invoice number.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
