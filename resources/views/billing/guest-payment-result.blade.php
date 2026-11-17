@extends('layouts.guest')

@section('title', 'Payment status')
@section('meta_robots', 'noindex,nofollow')
@section('meta_description', 'CHIP invoice payment status.')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body text-center p-4">
                    @if ($attempt->status === 'paid')
                        <span class="bi bi-check-circle-fill text-success display-5" aria-hidden="true"></span>
                        <h1 class="h3 mt-3">Payment confirmed</h1>
                        <p class="mb-0">CHIP confirmed payment for invoice {{ $attempt->document->number }}. Amount received: RM {{ number_format($attempt->amount, 2) }}.</p>
                    @else
                        <span class="bi bi-hourglass-split text-warning display-5" aria-hidden="true"></span>
                        <h1 class="h3 mt-3">Payment not confirmed yet</h1>
                        <p>CHIP has not confirmed payment for invoice {{ $attempt->document->number }}. Please wait a moment and check your payment status before trying again.</p>
                        <a class="btn btn-outline-primary mb-3" href="{{ route('billing.guest.chip.return', $attempt->uuid) }}">Check payment status</a>
                        <p class="small text-body-secondary mb-0">If you have already paid, contact OMG Computer and quote this invoice number.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
