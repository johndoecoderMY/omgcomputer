@extends('layouts.guest')

@section('title', 'Quotation response')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body p-4 text-center">
                    @if ($status === 'accepted')
                        <div class="display-5 text-success mb-3"><span class="bi bi-check-circle"></span></div>
                        <h1 class="h3">Quotation accepted</h1>
                        <p class="text-body-secondary">Thank you. We have recorded your acceptance of quotation <strong>{{ $document->number }}</strong>.</p>
                    @else
                        <div class="display-5 text-danger mb-3"><span class="bi bi-x-circle"></span></div>
                        <h1 class="h3">Quotation rejected</h1>
                        <p class="text-body-secondary">Your response has been recorded for quotation <strong>{{ $document->number }}</strong>.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
