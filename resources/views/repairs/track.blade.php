@extends('layouts.guest')

@section('title', 'Track a Repair')

@section('content')
@php
    $repairSteps = [
        [
            'image' => 'assets/img/omg/hero-technician.jpg',
            'title' => 'Drop-off & check-in',
            'caption' => 'We log the issue, confirm the device details, and assign your repair service number.',
        ],
        [
            'image' => 'assets/img/omg/repair-board-work.jpg',
            'title' => 'Diagnosis & repair',
            'caption' => 'Our technician inspects the hardware, software, and parts needed to get it working again.',
        ],
        [
            'image' => 'assets/img/omg/repair-pc-components.jpg',
            'title' => 'Testing & collection',
            'caption' => 'We test the final repair, explain the outcome, and let you know when it is ready.',
        ],
    ];
@endphp

@push('styles')
    <link rel="stylesheet" href="{{ asset('vendors/glightbox/glightbox.min.css') }}">
@endpush

<style>
    .track-repair-page {
        background:
            radial-gradient(circle at top left, rgba(255, 111, 66, 0.08), transparent 34%),
            linear-gradient(180deg, #f7f9fc 0%, #eef5ff 100%);
    }

    .track-repair-hero {
        background: linear-gradient(135deg, rgba(255, 125, 72, 0.08), rgba(14, 92, 194, 0.08));
        border: 1px solid rgba(17, 24, 39, 0.06);
        overflow: hidden;
    }

    .track-repair-hero img {
        height: 100%;
        min-height: 320px;
        object-fit: cover;
    }

    .track-repair-form {
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1.1rem;
        padding: 1.1rem;
    }

    .track-repair-form .form-control {
        border-radius: 0.9rem;
        min-height: 52px;
        border-color: rgba(148, 163, 184, 0.45);
    }

    .track-repair-form .btn-primary {
        min-height: 52px;
        border-radius: 0.9rem;
        box-shadow: 0 10px 22px rgba(255, 111, 66, 0.22);
    }

    .repair-steps-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.25rem;
    }

    .repair-step-card {
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 1.4rem;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 18px 35px rgba(15, 23, 42, 0.05);
    }

    .repair-step-card img {
        width: 100%;
        height: 220px;
        object-fit: cover;
        display: block;
    }

    .repair-step-body {
        padding: 1.25rem 1.2rem 1.35rem;
    }

    .repair-step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        background: rgba(255, 111, 66, 0.12);
        color: var(--falcon-primary, #ff4b0b);
        font-weight: 700;
        font-size: 0.8rem;
        margin-bottom: 0.6rem;
    }

    .repair-step-card h3 {
        font-size: 1.15rem;
        margin-bottom: 0.55rem;
        color: var(--falcon-gray-1000, #172033);
    }

    .repair-step-card p {
        margin-bottom: 0;
        color: var(--falcon-gray-700, #58657a);
        line-height: 1.6;
    }

    .repair-status-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1.25rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06);
    }

    .repair-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 0.9rem;
        border-radius: 999px;
        font-weight: 700;
        background: rgba(22, 163, 74, 0.1);
        color: #166534;
    }

    .repair-status-badge::before {
        content: "";
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 999px;
        background: currentColor;
        box-shadow: 0 0 0 6px rgba(34, 197, 94, 0.14);
    }

    .repair-timeline li {
        position: relative;
        padding-left: 2.2rem;
    }

    .repair-timeline li::before {
        content: "";
        position: absolute;
        left: 0.35rem;
        top: 0.2rem;
        width: 0.7rem;
        height: 0.7rem;
        border-radius: 50%;
        background: var(--falcon-primary, #ff4b0b);
        box-shadow: 0 0 0 6px rgba(255, 111, 66, 0.12);
    }

    .repair-timeline li + li {
        margin-top: 1.25rem;
    }

    .repair-timeline li:not(:last-child)::after {
        content: "";
        position: absolute;
        left: 0.65rem;
        top: 1rem;
        bottom: -1.15rem;
        width: 2px;
        background: rgba(148, 163, 184, 0.35);
    }

    .public-repair-step-photo {
        display: block;
        width: 100%;
        height: clamp(14rem, 55vw, 28rem);
        object-fit: contain;
        background: #f1f5f9;
    }

    .public-repair-step-photo-link {
        display: block;
        cursor: zoom-in;
    }

    .public-repair-step-caption {
        overflow-wrap: anywhere;
    }

    @media (max-width: 991.98px) {
        .repair-steps-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<section class="track-repair-page py-7 py-lg-9 min-vh-100">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="track-repair-hero card border-0 shadow-sm mb-4">
                    <div class="row g-0 align-items-center">
                        <div class="col-lg-7">
                            <div class="p-4 p-md-5">
                                <span class="d-inline-flex align-items-center px-3 py-2 rounded-pill text-primary fw-bold text-uppercase fs-10 mb-3" style="background: rgba(255,111,66,0.08); border: 1px solid rgba(255,111,66,0.15);">
                                    <span class="bi bi-search me-2" aria-hidden="true"></span>Guest repair tracking
                                </span>
                                <h1 class="h2 fw-bold text-1000 mb-3">Track your repair</h1>
                                <p class="text-700 mb-4">Enter the service number provided by the shop to check repair progress and get a quick update on your device.</p>

                                @if ($errors->has('ticket_number'))
                                    <div class="alert alert-danger mb-3" role="alert">
                                        @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                                    </div>
                                @endif

                                <form class="track-repair-form row g-2 align-items-center mb-0" method="POST" action="{{ route('repairs.track') }}" aria-label="Repair tracking lookup">
                                    @csrf
                                    <div class="col-md-8">
                                        <label class="visually-hidden" for="service-number">Service number</label>
                                        <input class="form-control" id="service-number" name="ticket_number" type="text"
                                            value="{{ old('ticket_number') }}" placeholder="Enter your service number" autocomplete="off" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-primary w-100" type="submit">Track repair</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <img src="{{ asset('assets/img/omg/hero-technician.jpg') }}" alt="Technician working on a laptop during a repair process" width="900" height="700" loading="eager" decoding="async">
                        </div>
                    </div>
                </div>

                <div class="repair-steps-grid mb-5">
                    @foreach ($repairSteps as $repairStep)
                        <article class="repair-step-card">
                            <img src="{{ asset($repairStep['image']) }}" alt="{{ $repairStep['title'] }}" width="900" height="620" loading="lazy" decoding="async">
                            <div class="repair-step-body">
                                <span class="repair-step-number">{{ $loop->iteration }}</span>
                                <h3>{{ $repairStep['title'] }}</h3>
                                <p>{{ $repairStep['caption'] }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>

                @if (isset($trackingTicket))
                    <div class="repair-status-panel p-3 p-md-4 p-lg-5">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                            <div>
                                <div class="small text-body-secondary mb-1">Repair service number</div>
                                <h2 class="h3 mb-0">{{ $trackingTicket->ticket_number }}</h2>
                            </div>
                            <span class="repair-status-badge">
                                {{ \App\Models\RepairTicket::STATUSES[$trackingTicket->status] ?? ucfirst(str_replace('_', ' ', $trackingTicket->status)) }}
                            </span>
                        </div>

                        <h3 class="h5 mb-3">Repair timeline</h3>
                        <ol class="repair-timeline list-unstyled mb-0">
                            @forelse ($trackingTicket->updates as $update)
                                <li>
                                    <div class="fw-semibold">
                                        {{ \App\Models\RepairTicket::STATUSES[$update->status] ?? ucfirst(str_replace('_', ' ', $update->status)) }}
                                    </div>
                                    <time class="small text-body-secondary" datetime="{{ $update->created_at->toIso8601String() }}">
                                        {{ $update->created_at->format('M j, Y g:i A') }}
                                    </time>
                                </li>
                            @empty
                                <li>
                                    <div class="fw-semibold">Device received</div>
                                    <div class="small text-body-secondary">Your repair has been received. We’ll add timeline updates as work progresses.</div>
                                </li>
                            @endforelse
                        </ol>

                        <hr class="my-4 my-md-5">
                        <h3 class="h5 mb-3">Repair steps</h3>
                        <div class="d-grid gap-3">
                            @forelse ($trackingTicket->updates as $update)
                                <article class="card border-0 shadow-sm">
                                    <div class="card-body p-3 p-md-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                            <h4 class="h6 mb-0">{{ $update->title }}</h4>
                                            <time class="small text-body-secondary" datetime="{{ $update->created_at->toIso8601String() }}">
                                                {{ $update->created_at->format('M j, Y g:i A') }}
                                            </time>
                                        </div>
                                        @if ($update->customer_details)
                                            <p class="text-pre-line mb-0">{{ $update->customer_details }}</p>
                                        @endif

                                        @if ($update->attachments->isNotEmpty())
                                            <div id="public-repair-step-photos-{{ $update->id }}"
                                                class="carousel slide mt-3" data-bs-touch="true" data-bs-interval="false">
                                                <div class="carousel-inner rounded border bg-body-tertiary">
                                                    @foreach ($update->attachments->sortBy('id')->values() as $attachment)
                                                        @php
                                                            $photoCaption = $attachment->caption ?: 'Repair step photo '.$loop->iteration;
                                                            $photoUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                                                                'repairs.track.photo',
                                                                now()->addDays(7),
                                                                ['attachment' => $attachment],
                                                            );
                                                        @endphp
                                                        <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                                            <a class="attachment-lightbox public-repair-step-photo-link"
                                                                href="{{ $photoUrl }}"
                                                                data-type="image"
                                                                data-gallery="public-repair-step-{{ $update->id }}"
                                                                data-description="{{ $photoCaption }}">
                                                                <img class="public-repair-step-photo"
                                                                    src="{{ $photoUrl }}"
                                                                    alt="{{ $photoCaption }}"
                                                                    loading="lazy">
                                                            </a>
                                                            <div class="public-repair-step-caption text-center p-2 small">
                                                                {{ $photoCaption }}
                                                                @if ($update->attachments->count() > 1)
                                                                    <span class="d-block text-body-secondary">Photo {{ $loop->iteration }} of {{ $loop->count }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                @if ($update->attachments->count() > 1)
                                                    <button class="carousel-control-prev" type="button"
                                                        data-bs-target="#public-repair-step-photos-{{ $update->id }}"
                                                        data-bs-slide="prev" aria-label="Previous photo">
                                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                        <span class="visually-hidden">Previous photo</span>
                                                    </button>
                                                    <button class="carousel-control-next" type="button"
                                                        data-bs-target="#public-repair-step-photos-{{ $update->id }}"
                                                        data-bs-slide="next" aria-label="Next photo">
                                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                        <span class="visually-hidden">Next photo</span>
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <p class="text-body-secondary mb-0">No repair steps have been shared yet.</p>
                            @endforelse
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('vendors/glightbox/glightbox.min.js') }}"></script>
    <script>
        (() => {
            if (window.GLightbox) {
                window.GLightbox({ selector: '.public-repair-step-photo-link' });
            }
        })();
    </script>
@endpush
