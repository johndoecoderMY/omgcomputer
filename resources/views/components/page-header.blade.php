@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'breadcrumbs' => [],
    'backUrl' => null,
    'backLabel' => 'Back',
])

@php
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName() ?? '';
    $sidebarBreadcrumbs = match (true) {
        str_starts_with($routeName, 'tickets.') => [
            ['label' => 'Support Desk'],
            ['label' => 'Tickets', 'url' => route('tickets.index')],
        ],
        $routeName === 'bookings.calendar' => [
            ['label' => 'Bookings', 'url' => route('bookings.index')],
            ['label' => 'Bookings Calendar'],
        ],
        str_starts_with($routeName, 'bookings.') => [
            ['label' => 'Bookings'],
            ['label' => 'Customer bookings', 'url' => route('bookings.index')],
        ],
        str_starts_with($routeName, 'booking-time-slots.') => [
            ['label' => 'Bookings'],
            ['label' => 'Available times', 'url' => route('booking-time-slots.index')],
        ],
        $routeName === 'projects.calendar' => [
            ['label' => 'Projects', 'url' => route('projects.index')],
            ['label' => 'Project calendar'],
        ],
        $routeName === 'calendar' => [
            ['label' => 'System'],
            ['label' => 'All Events Calendar'],
        ],
        str_starts_with($routeName, 'projects.') => [
            ['label' => 'Projects', 'url' => route('projects.index')],
        ],
        str_starts_with($routeName, 'customers.') => [
            ['label' => 'Customers'],
            ['label' => 'Customer records', 'url' => route('customers.index')],
        ],
        str_starts_with($routeName, 'admin.users.customers') => [
            ['label' => 'Customers'],
            ['label' => 'Customer accounts', 'url' => route('admin.users.customers')],
        ],
        str_starts_with($routeName, 'admin.users.') => [
            ['label' => 'Manage'],
            ['label' => 'Members', 'url' => route('admin.users.index')],
        ],
        str_starts_with($routeName, 'tasks.') => [
            ['label' => 'OMG Computer'],
            ['label' => 'Tasks', 'url' => route('tasks.index')],
        ],
        str_starts_with($routeName, 'development.flowcharts.') => [
            ['label' => 'Manage'],
            ['label' => 'Flow Charts', 'url' => route('development.flowcharts.index')],
        ],
        str_starts_with($routeName, 'development.') => [
            ['label' => 'OMG Computer'],
        ],
        str_starts_with($routeName, 'customer-messages.') => [
            ['label' => 'OMG Computer'],
            ['label' => 'Customer messages', 'url' => route('customer-messages.index')],
        ],
        str_starts_with($routeName, 'teams.') => [
            ['label' => 'Manage'],
            ['label' => 'Teams', 'url' => route('teams.index')],
        ],
        str_starts_with($routeName, 'departments.') => [
            ['label' => 'Manage'],
            ['label' => 'Departments', 'url' => route('departments.index')],
        ],
        str_starts_with($routeName, 'products.') || str_starts_with($routeName, 'products_services.') => [
            ['label' => 'Manage'],
            ['label' => 'Products & Services', 'url' => route('products.index')],
        ],
        str_starts_with($routeName, 'inventory.') || str_starts_with($routeName, 'suppliers.') => [
            ['label' => 'Manage'],
            ['label' => 'Parts & Inventory', 'url' => route('inventory.index')],
        ],
        str_starts_with($routeName, 'meta-content.') => [
            ['label' => 'Manage'],
            ['label' => 'Meta Content', 'url' => route('meta-content.index')],
        ],
        str_starts_with($routeName, 'admin.access.') => [
            ['label' => 'Manage'],
            ['label' => 'Roles & permissions', 'url' => route('admin.access.index')],
        ],
        str_starts_with($routeName, 'admin.backups.') => [
            ['label' => 'Manage'],
            ['label' => 'Backups', 'url' => route('admin.backups.index')],
        ],
        str_starts_with($routeName, 'billing.') => [
            ['label' => 'Billing & Payments'],
        ],
        str_starts_with($routeName, 'reports.integrations.') => [
            ['label' => 'Integrations'],
        ],
        str_starts_with($routeName, 'reports.') => [
            ['label' => 'Reports & Analytics'],
        ],
        str_starts_with($routeName, 'marketplace-shops.') => [
            ['label' => 'Integrations'],
            ['label' => 'E-commerce Shops', 'url' => route('marketplace-shops.index')],
        ],
        str_starts_with($routeName, 'profile.') || str_starts_with($routeName, 'error-pages.') => [
            ['label' => 'System'],
        ],
        default => [],
    };
    $breadcrumbs = array_values([...$sidebarBreadcrumbs, ...$breadcrumbs]);
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
            @elseif (! isset($breadcrumb['url']))
                <li class="breadcrumb-item">{{ $breadcrumb['label'] }}</li>
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
