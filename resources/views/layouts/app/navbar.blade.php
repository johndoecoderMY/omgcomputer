<nav class="navbar navbar-light navbar-glass navbar-top navbar-expand">
    <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" type="button"
        data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse"
        aria-expanded="false" aria-label="Toggle Navigation"><span class="navbar-toggle-icon"><span
                class="toggle-line"></span></span></button>
    <a class="navbar-brand me-1 me-sm-3" href="{{ route('dashboard') }}">
        <div class="d-flex align-items-center">
            <img class="me-2 brand-logo" src="{{ asset('assets/img/omg/logoex2.png') }}" alt="OMG Computer logo"
                width="80" />
        </div>
    </a>
    <ul class="navbar-nav flex-grow-1 mx-2 mx-lg-3" style="min-width: 0; max-width: 32rem;">
        <li class="nav-item flex-grow-1" style="min-width: 0;">
        <div class="search-box" data-header-page-search style="max-width: 100%;">
            <form class="position-relative" role="search" data-bs-toggle="search" data-bs-display="static"
                aria-expanded="false">
                <input class="form-control search-input fuzzy-search" type="search" placeholder="Search..."
                    aria-label="Search pages" aria-controls="header-page-search-results" autocomplete="off" />
                <span class="fas fa-search search-box-icon" aria-hidden="true"></span>
            </form>
            <div class="btn-close-falcon-container position-absolute end-0 top-50 translate-middle shadow-none"
                data-bs-dismiss="search">
                <button class="btn btn-link btn-close-falcon p-0" type="button" aria-label="Close search"></button>
            </div>
            <div class="dropdown-menu border font-base start-0 mt-2 py-0 overflow-hidden w-100"
                id="header-page-search-results" role="listbox">
                <div class="scrollbar list py-3" style="max-height: 24rem;">
                    <section data-header-search-recent-section>
                        <h6 class="dropdown-header fw-medium text-uppercase px-x1 fs-11 pt-0 pb-2">Recently Browsed</h6>
                        <div data-header-search-recent></div>
                        <p class="dropdown-item fs-10 px-x1 py-1 mb-0 text-body-secondary"
                            data-header-search-recent-empty>No recently browsed pages yet.</p>
                        <hr class="text-200 dark__text-900 my-2">
                    </section>
                    <h6 class="dropdown-header fw-medium text-uppercase px-x1 fs-11 pt-0 pb-2 d-none"
                        data-header-search-pages-heading>Pages</h6>
                    @if (auth()->user()->hasRole('customer'))
                        <a class="dropdown-item px-x1 py-2" href="{{ route('projects.index') }}" role="option"
                            data-header-search-item data-search-terms="my projects customer">
                            <span class="fas fa-project-diagram me-2 text-primary" aria-hidden="true"></span>My Projects
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('profile.edit') }}" role="option"
                            data-header-search-item data-search-terms="profile account settings">
                            <span class="fas fa-user me-2 text-primary" aria-hidden="true"></span>Profile
                        </a>
                    @else
                        <a class="dropdown-item px-x1 py-2" href="{{ route('dashboard') }}" role="option"
                            data-header-search-item data-search-terms="home overview">
                            <span class="fas fa-chart-pie me-2 text-primary" aria-hidden="true"></span>Dashboard
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('tickets.index') }}" role="option"
                            data-header-search-item data-search-terms="support repair help desk">
                            <span class="fas fa-ticket-alt me-2 text-primary" aria-hidden="true"></span>Tickets
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('tickets.create') }}" role="option"
                            data-header-search-item data-search-terms="support repair new create">
                            <span class="fas fa-plus me-2 text-primary" aria-hidden="true"></span>New ticket
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('bookings.index') }}" role="option"
                            data-header-search-item data-search-terms="appointments schedule calendar">
                            <span class="fas fa-calendar-check me-2 text-primary" aria-hidden="true"></span>Customer bookings
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('bookings.calendar') }}" role="option"
                            data-header-search-item data-search-terms="appointments schedule booking calendar">
                            <span class="fas fa-calendar-alt me-2 text-primary" aria-hidden="true"></span>Bookings calendar
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('booking-time-slots.index') }}" role="option"
                            data-header-search-item data-search-terms="appointments schedule availability">
                            <span class="fas fa-clock me-2 text-primary" aria-hidden="true"></span>Available times
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('bookings.create') }}" role="option"
                            data-header-search-item data-search-terms="appointments public calendar schedule">
                            <span class="fas fa-calendar-plus me-2 text-primary" aria-hidden="true"></span>Public booking calendar
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('projects.index') }}" role="option"
                            data-header-search-item data-search-terms="work">
                            <span class="fas fa-project-diagram me-2 text-primary" aria-hidden="true"></span>Projects
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('calendar') }}" role="option"
                            data-header-search-item data-search-terms="all events system calendar schedule">
                            <span class="fas fa-calendar-alt me-2 text-primary" aria-hidden="true"></span>All Events Calendar
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('customers.index') }}" role="option"
                            data-header-search-item data-search-terms="clients">
                            <span class="fas fa-users me-2 text-primary" aria-hidden="true"></span>Customers
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('development.overview') }}" role="option"
                            data-header-search-item data-search-terms="company business">
                            <span class="fas fa-home me-2 text-primary" aria-hidden="true"></span>OMG Computer overview
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('tasks.index') }}" role="option"
                            data-header-search-item data-search-terms="to do work">
                            <span class="fas fa-tasks me-2 text-primary" aria-hidden="true"></span>Tasks
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('development.plan') }}" role="option"
                            data-header-search-item data-search-terms="business">
                            <span class="fas fa-file-alt me-2 text-primary" aria-hidden="true"></span>Business plan
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('development.sops') }}" role="option"
                            data-header-search-item data-search-terms="procedures documentation">
                            <span class="fas fa-book me-2 text-primary" aria-hidden="true"></span>SOP library
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('customer-messages.index') }}" role="option"
                            data-header-search-item data-search-terms="templates communication">
                            <span class="fas fa-envelope me-2 text-primary" aria-hidden="true"></span>Customer messages
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('expenses.index', 'rental') }}" role="option"
                            data-header-search-item data-search-terms="rental rent operating costs">
                            <span class="fas fa-building me-2 text-primary" aria-hidden="true"></span>Rental
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('expenses.index', 'bills') }}" role="option"
                            data-header-search-item data-search-terms="bills utilities expenses">
                            <span class="fas fa-file-invoice-dollar me-2 text-primary" aria-hidden="true"></span>Bills
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('expenses.index', 'services') }}" role="option"
                            data-header-search-item data-search-terms="services renewal domain server SSM operating costs">
                            <span class="fas fa-sync-alt me-2 text-primary" aria-hidden="true"></span>Services and renewals
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('teams.index') }}" role="option"
                            data-header-search-item data-search-terms="staff members">
                            <span class="fas fa-users me-2 text-primary" aria-hidden="true"></span>Teams
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('departments.index') }}" role="option"
                            data-header-search-item data-search-terms="organization">
                            <span class="fas fa-sitemap me-2 text-primary" aria-hidden="true"></span>Departments
                        </a>
                        @role('admin')
                            <a class="dropdown-item px-x1 py-2" href="{{ route('admin.users.customers') }}" role="option"
                                data-header-search-item data-search-terms="customer accounts">
                                <span class="fas fa-user-plus me-2 text-primary" aria-hidden="true"></span>Customer accounts
                            </a>
                            <a class="dropdown-item px-x1 py-2" href="{{ route('admin.users.index') }}" role="option"
                                data-header-search-item data-search-terms="members staff users">
                                <span class="fas fa-users me-2 text-primary" aria-hidden="true"></span>User accounts
                            </a>
                            <a class="dropdown-item px-x1 py-2" href="{{ route('admin.access.index') }}" role="option"
                                data-header-search-item data-search-terms="roles permissions security">
                                <span class="fas fa-user-shield me-2 text-primary" aria-hidden="true"></span>Roles and permissions
                            </a>
                            <a class="dropdown-item px-x1 py-2" href="{{ route('admin.backups.index') }}" role="option"
                                data-header-search-item data-search-terms="database restore">
                                <span class="fas fa-database me-2 text-primary" aria-hidden="true"></span>Backups
                            </a>
                        @endrole
                        <a class="dropdown-item px-x1 py-2" href="{{ route('products.index') }}" role="option"
                            data-header-search-item data-search-terms="services catalog">
                            <span class="fas fa-tags me-2 text-primary" aria-hidden="true"></span>Products and services
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('development.flowcharts.index') }}" role="option"
                            data-header-search-item data-search-terms="process diagrams">
                            <span class="fas fa-project-diagram me-2 text-primary" aria-hidden="true"></span>Flow charts
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('meta-content.index') }}" role="option"
                            data-header-search-item data-search-terms="social media posts">
                            <span class="fas fa-share-alt me-2 text-primary" aria-hidden="true"></span>Meta content
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('inventory.index') }}" role="option"
                            data-header-search-item data-search-terms="stock parts">
                            <span class="fas fa-boxes me-2 text-primary" aria-hidden="true"></span>Inventory overview
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('inventory.parts') }}" role="option"
                            data-header-search-item data-search-terms="stock spare parts">
                            <span class="fas fa-cubes me-2 text-primary" aria-hidden="true"></span>Spare parts
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('inventory.suppliers.index') }}" role="option"
                            data-header-search-item data-search-terms="vendors">
                            <span class="fas fa-truck me-2 text-primary" aria-hidden="true"></span>Suppliers
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('inventory.movements') }}" role="option"
                            data-header-search-item data-search-terms="stock transactions">
                            <span class="fas fa-exchange-alt me-2 text-primary" aria-hidden="true"></span>Stock movements
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('billing.index') }}" role="option"
                            data-header-search-item data-search-terms="invoices quotations payments">
                            <span class="fas fa-file-invoice-dollar me-2 text-primary" aria-hidden="true"></span>Billing and payments
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('reports.index') }}" role="option"
                            data-header-search-item data-search-terms="analytics statistics">
                            <span class="fas fa-chart-line me-2 text-primary" aria-hidden="true"></span>Reports and analytics
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('reports.marketing') }}" role="option"
                            data-header-search-item data-search-terms="marketing whatsapp campaigns">
                            <span class="fas fa-bullhorn me-2 text-primary" aria-hidden="true"></span>Marketing and WhatsApp
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('reports.sales') }}" role="option"
                            data-header-search-item data-search-terms="revenue">
                            <span class="fas fa-chart-bar me-2 text-primary" aria-hidden="true"></span>Sales report
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('reports.inventory') }}" role="option"
                            data-header-search-item data-search-terms="stock">
                            <span class="fas fa-boxes me-2 text-primary" aria-hidden="true"></span>Inventory report
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('reports.teams') }}" role="option"
                            data-header-search-item data-search-terms="staff performance">
                            <span class="fas fa-users me-2 text-primary" aria-hidden="true"></span>Team performance
                        </a>
                        <a class="dropdown-item px-x1 py-2" href="{{ route('profile.edit') }}" role="option"
                            data-header-search-item data-search-terms="account settings">
                            <span class="fas fa-user me-2 text-primary" aria-hidden="true"></span>My Profile
                        </a>
                        @foreach (['403' => 'Forbidden', '404' => 'Not found', '419' => 'Session expired', '429' => 'Too many requests', '500' => 'Server error', '503' => 'Unavailable'] as $code => $label)
                            <a class="dropdown-item px-x1 py-2" href="{{ route('error-pages.preview', $code) }}" role="option"
                                data-header-search-item data-search-terms="error {{ $code }} {{ $label }}">
                                <span class="fas fa-exclamation-triangle me-2 text-primary" aria-hidden="true"></span>
                                Error page: {{ $code }} - {{ $label }}
                            </a>
                        @endforeach
                        @role('admin')
                            <a class="dropdown-item px-x1 py-2" href="{{ route('reports.integrations.whatsapp') }}" role="option"
                                data-header-search-item data-search-terms="integration api">
                                <span class="fab fa-whatsapp me-2 text-primary" aria-hidden="true"></span>WhatsApp API test
                            </a>
                            <a class="dropdown-item px-x1 py-2" href="{{ route('reports.integrations.notifications') }}" role="option"
                                data-header-search-item data-search-terms="integration notifications email mail test">
                                <span class="fas fa-bell me-2 text-primary" aria-hidden="true"></span>Notification & email tests
                            </a>
                            <a class="dropdown-item px-x1 py-2" href="{{ route('reports.integrations.easyparcel') }}" role="option"
                                data-header-search-item data-search-terms="integration shipping delivery">
                                <span class="fas fa-truck me-2 text-primary" aria-hidden="true"></span>EasyParcel rate checker
                            </a>
                            <a class="dropdown-item px-x1 py-2" href="{{ route('marketplace-shops.index') }}" role="option"
                                data-header-search-item data-search-terms="integration ecommerce stores">
                                <span class="fas fa-store me-2 text-primary" aria-hidden="true"></span>E-commerce shops
                            </a>
                        @endrole
                    @endif
                    <p class="px-x1 py-2 mb-0 small text-body-secondary d-none" data-header-search-prompt>Type to search pages.</p>
                    <p class="px-x1 py-2 mb-0 small text-body-secondary d-none" data-header-search-empty role="status">
                        No matching pages.
                    </p>
                    </div>
            </div>
        </div>
        </li>
    </ul>
    <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">
        <li class="nav-item ps-2 pe-0">
            <div class="dropdown theme-control-dropdown"><a
                    class="nav-link d-flex align-items-center dropdown-toggle fa-icon-wait fs-9 pe-1 py-0"
                    href="navbar-vertical.html#" role="button" id="themeSwitchDropdown" data-bs-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false"><span class="fas fa-sun fs-7"
                        data-fa-transform="shrink-2" data-theme-dropdown-toggle-icon="light"></span><span
                        class="fas fa-moon fs-7" data-fa-transform="shrink-3"
                        data-theme-dropdown-toggle-icon="dark"></span><span class="fas fa-adjust fs-7"
                        data-fa-transform="shrink-2" data-theme-dropdown-toggle-icon="auto"></span></a>
                <div class="dropdown-menu dropdown-menu-end dropdown-caret border py-0 mt-3"
                    aria-labelledby="themeSwitchDropdown">
                    <div class="bg-white dark__bg-1000 rounded-2 py-2"><button
                            class="dropdown-item d-flex align-items-center gap-2" type="button" value="light"
                            data-theme-control="theme"><span class="fas fa-sun"></span>Light<span
                                class="fas fa-check dropdown-check-icon ms-auto text-600"></span></button>
                        <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="dark"
                            data-theme-control="theme"><span class="fas fa-moon" data-fa-transform=""></span>Dark<span
                                class="fas fa-check dropdown-check-icon ms-auto text-600"></span></button>
                        <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="auto"
                            data-theme-control="theme"><span class="fas fa-adjust" data-fa-transform=""></span>Auto<span
                                class="fas fa-check dropdown-check-icon ms-auto text-600"></span></button>
                    </div>
                </div>
            </div>
        </li>
       
        @auth
            @include('notifications._navbar')
        @endauth
     
        <li class="nav-item dropdown">
            <a class="nav-link pe-0 ps-2" href="{{ route('profile.edit') }}" id="navbarDropdownUser" role="button"
                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                aria-label="Open account menu for {{ auth()->user()->name }}">
                <div class="avatar avatar-xl">
                    @if (auth()->user()?->profile_photo_url)
                        <img class="rounded-circle" src="{{ auth()->user()->profile_photo_url }}" alt="{{ auth()->user()->name }} profile photo">
                    @else
                        <div class="avatar-name rounded-circle bg-primary-subtle text-primary">
                            <span>{{ str(auth()->user()?->name ?? 'U')->substr(0, 1)->upper() }}</span>
                        </div>
                    @endif
                </div>
            </a>
            <div class="dropdown-menu dropdown-caret dropdown-menu-end py-0"
                aria-labelledby="navbarDropdownUser">
                <div class="bg-white dark__bg-1000 rounded-2 py-2">
                    <div class="px-3 py-2">
                        <div class="fw-semibold text-body">{{ auth()->user()->name }}</div>
                        <div class="small text-body-secondary text-break">{{ auth()->user()->email }}</div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                        <span class="fas fa-user me-2 text-body-secondary" aria-hidden="true"></span>Profile and account
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="dropdown-item" type="submit">
                            <span class="fas fa-sign-out-alt me-2" aria-hidden="true"></span>Log out
                        </button>
                    </form>
                </div>
            </div>
        </li>
    </ul>
</nav>