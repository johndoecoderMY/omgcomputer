@extends('layouts.guest')

@section('title', 'Track a Repair')

@section('content')
<section class="py-7 py-lg-9 bg-body-tertiary min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="mb-4">
                    <a class="text-decoration-none" href="{{ url('/') }}">
                        <span class="fas fa-arrow-left me-2" aria-hidden="true"></span>Back to OMG Computer
                    </a>
                </div>
                <div class="card border-0 shadow p-2">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <span class="fas fa-magnifying-glass text-primary fa-2x mb-3" aria-hidden="true"></span>
                            <span class="d-block text-primary fw-bold text-uppercase fs-10">Guest repair tracking</span>
                            <h1 class="h2 fw-bold text-1000 mt-2">Track your repair</h1>
                            <p class="text-700 mb-0">Enter the service number provided by the shop to check repair progress.</p>
                        </div>

                        @if ($errors->has('ticket_number'))
                            <div class="alert alert-danger" role="alert">
                                @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                            </div>
                        @endif

                        <form class="row g-2 justify-content-center mb-4" method="POST" action="{{ route('repairs.track') }}" aria-label="Repair tracking lookup">
                            @csrf
                            <div class="col-sm-8">
                                <label class="visually-hidden" for="service-number">Service number</label>
                                <input class="form-control" id="service-number" name="ticket_number" type="text"
                                    value="{{ old('ticket_number') }}" placeholder="Enter your service number" autocomplete="off" required>
                            </div>
                            <div class="col-sm-auto">
                                <button class="btn btn-primary w-100" type="submit">Track repair</button>
                            </div>
                        </form>

                        @if (isset($trackingTicket))
                            <div class="border rounded-3 p-3 p-md-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                    <div>
                                        <div class="small text-body-secondary">Repair service number</div>
                                        <h2 class="h4 mb-0">{{ $trackingTicket->ticket_number }}</h2>
                                    </div>
                                    <span class="badge text-bg-primary fs-9">
                                        {{ \App\Models\RepairTicket::STATUSES[$trackingTicket->status] ?? ucfirst(str_replace('_', ' ', $trackingTicket->status)) }}
                                    </span>
                                </div>

                                <h3 class="h5 mb-3">Repair timeline</h3>
                                <ol class="list-unstyled mb-0">
                                    @forelse ($trackingTicket->updates as $update)
                                        <li class="d-flex gap-3 {{ $loop->last ? '' : 'pb-4' }}">
                                            <div class="d-flex flex-column align-items-center" aria-hidden="true">
                                                <span class="rounded-circle bg-primary d-block mt-1" style="width: .75rem; height: .75rem;"></span>
                                                @unless ($loop->last)
                                                    <span class="bg-300 flex-grow-1 mt-1" style="width: 2px;"></span>
                                                @endunless
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold">
                                                    {{ \App\Models\RepairTicket::STATUSES[$update->status] ?? ucfirst(str_replace('_', ' ', $update->status)) }}
                                                </div>
                                                <time class="small text-body-secondary" datetime="{{ $update->created_at->toIso8601String() }}">
                                                    {{ $update->created_at->format('M j, Y g:i A') }}
                                                </time>
                                            </div>
                                        </li>
                                    @empty
                                        <li class="text-body-secondary">Your repair has been received. We’ll add timeline updates as work progresses.</li>
                                    @endforelse
                                </ol>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
