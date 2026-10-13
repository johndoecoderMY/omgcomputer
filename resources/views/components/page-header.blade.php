@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'breadcrumbs' => [],
    'backUrl' => null,
    'backLabel' => 'Back',
])

@php
    $breadcrumbs = array_values($breadcrumbs);
    $lastBreadcrumb = $breadcrumbs === [] ? null : end($breadcrumbs);
    if ($lastBreadcrumb === null || (isset($lastBreadcrumb['url']) && ($lastBreadcrumb['label'] ?? null) !== $title)) {
        $breadcrumbs[] = ['label' => $title];
    }
@endphp

<nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2">
        @unless (in_array($title, ['Home', 'Dashboard'], true))
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
        @endunless
        @foreach ($breadcrumbs as $breadcrumb)
            @if ($loop->last)
                <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['label'] }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a></li>
            @endif
        @endforeach
    </ol>
</nav>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        @if ($eyebrow)<p class="text-primary fw-bold text-uppercase small mb-1">{{ $eyebrow }}</p>@endif
        <h1 class="h2 mb-1">{{ $title }}</h1>
        @if ($description)<p class="text-body-secondary mb-0">{{ $description }}</p>@endif
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        @if (isset($actions)){!! $actions !!}@endif
        @if ($backUrl)
            <a href="{{ $backUrl }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>{{ $backLabel }}
            </a>
        @endif
    </div>
</div>
