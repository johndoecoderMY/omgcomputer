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
    $actionDropdownId = 'page-actions-'.\Illuminate\Support\Str::uuid();
    $actionsHtml = isset($actions) ? (string) $actions : '';
    preg_match_all('/<(?:a\b[^>]*\bhref\s*=|button\b[^>]*|input\b[^>]*\btype\s*=\s*["\']?submit\b)[^>]*>/i', $actionsHtml, $actionMatches);
    $hasSingleAction = count($actionMatches[0]) === 1;
@endphp

@once
    <style>
        .page-header-action-dropdown .dropdown-menu {
            min-width: 13rem;
        }

        .page-header-action-dropdown .dropdown-menu > a,
        .page-header-action-dropdown .dropdown-menu > button,
        .page-header-action-dropdown .dropdown-menu > form > button {
            display: block;
            width: 100%;
            margin: 0;
            padding: .4rem 1rem;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            color: var(--falcon-dropdown-link-color, #344050);
            text-align: left;
            text-decoration: none;
            white-space: nowrap;
        }

        .page-header-action-dropdown .dropdown-menu > a:hover,
        .page-header-action-dropdown .dropdown-menu > button:hover,
        .page-header-action-dropdown .dropdown-menu > form > button:hover {
            background: var(--falcon-dropdown-link-hover-bg, #edf2f9);
            color: var(--falcon-dropdown-link-hover-color, #232e3c);
        }

        .page-header-action-dropdown .dropdown-menu > form {
            margin: 0;
        }

        .page-header-action-dropdown .dropdown-menu > form > .btn-outline-danger,
        .page-header-action-dropdown .dropdown-menu > .btn-outline-danger {
            color: var(--falcon-danger, #e63757);
        }
    </style>
@endonce

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
        @if (isset($actions))
            <div class="page-header-action-single">
                @if ($hasSingleAction)
                    {!! $actionsHtml !!}
                @else
                    <div class="dropdown font-sans-serif btn-reveal-trigger page-header-action-dropdown">
                        <button class="btn btn-primary dropdown-toggle dropdown-caret-none"
                            id="{{ $actionDropdownId }}-toggle" type="button" data-bs-toggle="dropdown" data-boundary="viewport"
                            aria-haspopup="true" aria-expanded="false" aria-label="Page actions">
                            <span class="fas fa-ellipsis-h fs-11" aria-hidden="true"></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end border py-2" aria-labelledby="{{ $actionDropdownId }}-toggle">
                            {!! $actionsHtml !!}
                        </div>
                    </div>
                @endif
            </div>
        @endif
        @if ($backUrl)
            <a href="{{ $backUrl }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>{{ $backLabel }}
            </a>
        @endif
    </div>
</div>
