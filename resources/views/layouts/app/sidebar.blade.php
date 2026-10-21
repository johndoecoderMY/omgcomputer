<nav class="navbar navbar-light navbar-vertical navbar-expand-xl">
    <script>
        var navbarStyle = localStorage.getItem("navbarStyle");
        if (navbarStyle && navbarStyle !== 'transparent') {
            document.querySelector('.navbar-vertical').classList.add(`navbar-${navbarStyle}`);
        }
    </script>

    <div class="d-flex align-items-center">
        <div class="toggle-icon-wrapper">
            <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle" data-bs-toggle="tooltip"
                data-bs-placement="left" title="Toggle Navigation">
                <span class="navbar-toggle-icon">
                    <span class="toggle-line"></span>
                </span>
            </button>
        </div>

        <a class="navbar-brand" href="#">
            <div class="d-flex align-items-center py-3">
                <img class="me-2 brand-logo brand-logo-light" src="{{ asset('assets/img/omg/logoex2.png') }}"
                    alt="OMG Computer logo" width="80" />
                <img class="me-2 brand-logo brand-logo-dark" src="{{ asset('assets/img/omg/logoex2w.png') }}" alt=""
                    aria-hidden="true" width="80" />
            </div>
        </a>
    </div>

    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content scrollbar">
            <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">
                @if (auth()->user()->hasRole('customer'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('tickets.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-ticket-alt"></span></span>
                                <span class="nav-link-text ps-1">My Tickets</span>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('bookings.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-calendar-check"></span></span>
                                <span class="nav-link-text ps-1">My Bookings</span>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('projects.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-project-diagram"></span></span>
                                <span class="nav-link-text ps-1">My Projects</span>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('billing.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-file-invoice-dollar"></span></span>
                                <span class="nav-link-text ps-1">Project bills and quotations</span>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('profile.edit') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-cog"></span></span>
                                <span class="nav-link-text ps-1">Profile</span>
                            </div>
                        </a>
                    </li>
                @else
                    {{-- Dashboard --}}
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-chart-pie"></span></span>
                                <span class="nav-link-text ps-1">Dashboard</span>
                            </div>
                        </a>
                    </li>


                    {{-- Bookings --}}
                    <li class="nav-item">
                        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                            <div class="col-auto navbar-vertical-label">Work</div>
                            <div class="col ps-0">
                                <hr class="mb-0 navbar-vertical-divider">
                            </div>
                        </div>

                        <a class="nav-link dropdown-indicator" href="#bookingsMenu" role="button" data-bs-toggle="collapse"
                            aria-expanded="false" aria-controls="bookingsMenu">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-calendar-check"></span></span>
                                <span class="nav-link-text ps-1">Bookings</span>
                            </div>
                        </a>
                        <ul class="nav collapse" id="bookingsMenu">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('bookings.index') }}">
                                    <span class="nav-link-text ps-1">List</span>
                                </a>
                            </li>
                      
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('bookings.calendar') }}">
                                    <span class="nav-link-text ps-1">Book Calendar</span>
                                </a>
                            </li>
                            @if (auth()->user()->hasRole('admin') || auth()->user()->can('booking_availability.manage'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('booking-time-slots.index') }}">
                                        <span class="nav-link-text ps-1">Available times</span>
                                    </a>
                                </li>
                            @endif
                        </ul>

                        <a class="nav-link dropdown-indicator" href="#repairTicketsMenu" role="button"
                            data-bs-toggle="collapse" aria-expanded="false" aria-controls="repairTicketsMenu">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-ticket-alt"></span></span>
                                <span class="nav-link-text ps-1">Tickets</span>
                            </div>
                        </a>

                        <ul class="nav collapse" id="repairTicketsMenu">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('tickets.index') }}">
                                    <span class="nav-link-text ps-1">All tickets</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('tickets.create') }}">
                                    <span class="nav-link-text ps-1">New ticket</span>
                                </a>
                            </li>
                        </ul>


                    </li>

                    {{-- Projects --}}
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('projects.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-project-diagram"></span></span>
                                <span class="nav-link-text ps-1">Projects</span>
                            </div>
                        </a>
                    </li>

                    {{-- Customers --}}
                    <li class="nav-item">
                        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                            <div class="col-auto navbar-vertical-label">Customers</div>
                            <div class="col ps-0">
                                <hr class="mb-0 navbar-vertical-divider">
                            </div>
                        </div>
                        <a class="nav-link" href="{{ route('customers.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-users"></span></span>
                                <span class="nav-link-text ps-1">Records</span>
                            </div>
                        </a>
                        @role('admin')
                        <a class="nav-link" href="{{ route('admin.users.customers') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-user-plus"></span></span>
                                <span class="nav-link-text ps-1">Accounts</span>
                            </div>
                        </a>
                        @endrole
                    </li>

                    {{-- OMG Computer --}}
                    <li class="nav-item">
                        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                            <div class="col-auto navbar-vertical-label">OMG Computer</div>
                            <div class="col ps-0">
                                <hr class="mb-0 navbar-vertical-divider">
                            </div>
                        </div>

                        @role('admin')
                        <a class="nav-link" href="{{ route('development.overview') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-home"></span></span>
                                <span class="nav-link-text ps-1">Overview</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('projects.tasks.index', ['project' => 1]) }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-tasks"></span></span>
                                <span class="nav-link-text ps-1">Tasks</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('development.plan') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-file-alt"></span></span>
                                <span class="nav-link-text ps-1">Business Plan</span>
                            </div>
                        </a>
                        @endrole

                    </li>



                    {{-- Parts & Inventory --}}
                    <li class="nav-item">

                        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                            <div class="col-auto navbar-vertical-label">Manage</div>
                            <div class="col ps-0">
                                <hr class="mb-0 navbar-vertical-divider">
                            </div>
                        </div>

                        <a class="nav-link" href="{{ route('development.sops') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-book"></span></span>
                                <span class="nav-link-text ps-1">SOPs</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('customer-messages.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-envelope"></span></span>
                                <span class="nav-link-text ps-1">Copywriting</span>
                            </div>
                        </a>

                        @role('admin')
                        <a class="nav-link" href="{{ route('teams.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-users-cog"></span></span>
                                <span class="nav-link-text ps-1">Teams</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('departments.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-sitemap"></span></span>
                                <span class="nav-link-text ps-1">Departments</span>
                            </div>
                        </a>
                        @endrole
                        @if (auth()->user()->hasRole('admin') || auth()->user()->can('users.view'))
                            @role('admin')
                            <a class="nav-link" href="{{ route('admin.users.index') }}">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-users"></span></span>
                                    <span class="nav-link-text ps-1">Members</span>
                                </div>
                            </a>
                            <a class="nav-link" href="{{ route('admin.access.index') }}">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-user-shield"></span></span>
                                    <span class="nav-link-text ps-1">Roles & permissions</span>
                                </div>
                            </a>
                            <a class="nav-link" href="{{ route('admin.backups.index') }}">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-database"></span></span>
                                    <span class="nav-link-text ps-1">Backups</span>
                                </div>
                            </a>
                        @else
                            <a class="nav-link" href="{{ route('admin.users.index') }}">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-users"></span></span>
                                    <span class="nav-link-text ps-1">View user accounts</span>
                                </div>
                            </a>
                            @endrole
                        @endif

                        @if (auth()->user()->hasRole('admin') || auth()->user()->can('products.view'))
                            <a class="nav-link" href="{{ route('products.index') }}">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-tags"></span></span>
                                    <span class="nav-link-text ps-1">Products & Services</span>
                                </div>
                            </a>
                        @endif

                        @if (auth()->user()->hasRole('admin') || auth()->user()->can('flowcharts.view'))
                            <a class="nav-link" href="{{ route('development.flowcharts.index') }}">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-project-diagram"></span></span>
                                    <span class="nav-link-text ps-1">Flow Charts</span>
                                </div>
                            </a>
                        @endif
                        @if (auth()->user()->hasRole('admin') || auth()->user()->can('drama_content.manage'))
                            <a class="nav-link" href="{{ route('drama-content.index') }}">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-film"></span></span>
                                    <span class="nav-link-text ps-1">Drama Content</span>
                                </div>
                            </a>
                        @endif
                        @if (auth()->user()->hasRole('admin') || auth()->user()->can('meta_content.view'))
                            <a class="nav-link" href="{{ route('meta-content.index') }}">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-share-alt"></span></span>
                                    <span class="nav-link-text ps-1">Meta Content</span>
                                </div>
                            </a>
                        @endif

                        @if (auth()->user()->hasRole('admin') || auth()->user()->can('inventory.view'))
                            <a class="nav-link dropdown-indicator" href="#inventoryMenu" role="button" data-bs-toggle="collapse"
                                aria-expanded="false" aria-controls="inventoryMenu">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-boxes"></span></span>
                                    <span class="nav-link-text ps-1">Parts & Inventory</span>
                                </div>
                            </a>

                            <ul class="nav collapse" id="inventoryMenu">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('inventory.index') }}">
                                        <span class="nav-link-text ps-1">Overview</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('inventory.parts') }}">
                                        <span class="nav-link-text ps-1">Parts</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('inventory.suppliers.index') }}">
                                        <span class="nav-link-text ps-1">Suppliers</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('inventory.movements') }}">
                                        <span class="nav-link-text ps-1">Stock Movements</span>
                                    </a>
                                </li>
                            </ul>
                        @endif
                    </li>

                    @role('admin')
                    <li class="nav-item">
                        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                            <div class="col-auto navbar-vertical-label">Operating costs</div>
                            <div class="col ps-0">
                                <hr class="mb-0 navbar-vertical-divider">
                            </div>
                        </div>
                        <a class="nav-link" href="{{ route('expenses.index', 'rental') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-building"></span></span>
                                <span class="nav-link-text ps-1">Rental</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('expenses.index', 'bills') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-file-invoice-dollar"></span></span>
                                <span class="nav-link-text ps-1">Bills</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('expenses.index', 'services') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-sync-alt"></span></span>
                                <span class="nav-link-text ps-1">Services & renewals</span>
                            </div>
                        </a>
                    </li>
                    @endrole

                    {{-- Billing & Payments --}}
                    @if (auth()->user()->hasRole('admin') || auth()->user()->can('billing.view'))
                        <li class="nav-item">
                            <a class="nav-link dropdown-indicator" href="#billingMenu" role="button" data-bs-toggle="collapse"
                                aria-expanded="false" aria-controls="billingMenu">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-file-invoice-dollar"></span></span>
                                    <span class="nav-link-text ps-1">Billing & Payments</span>
                                </div>
                            </a>

                            <ul class="nav collapse" id="billingMenu">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('billing.index', ['type' => 'quotation']) }}">
                                        <span class="nav-link-text ps-1">Quotations</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('billing.index', ['type' => 'invoice']) }}">
                                        <span class="nav-link-text ps-1">Invoices</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('billing.index', ['status' => 'partial']) }}">
                                        <span class="nav-link-text ps-1">Part-paid invoices</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    {{-- Reports --}}
                    @role('admin')
                    <li class="nav-item">
                        <a class="nav-link dropdown-indicator" href="#reportsMenu" role="button" data-bs-toggle="collapse"
                            aria-expanded="false" aria-controls="reportsMenu">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-chart-line"></span></span>
                                <span class="nav-link-text ps-1">Reports & Analytics</span>
                            </div>
                        </a>

                        <ul class="nav collapse" id="reportsMenu">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('reports.index') }}">
                                    <span class="nav-link-text ps-1">Reports Overview</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('reports.marketing') }}">
                                    <span class="nav-link-text ps-1">Marketing & WhatsApp</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('reports.sales') }}">
                                    <span class="nav-link-text ps-1">Sales</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('reports.inventory') }}">
                                    <span class="nav-link-text ps-1">Inventory</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('reports.teams') }}">
                                    <span class="nav-link-text ps-1">Team Performance</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endrole

                    @role('admin')
                    {{-- Integrations --}}
                    <li class="nav-item">
                        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                            <div class="col-auto navbar-vertical-label">Integrations</div>
                            <div class="col ps-0">
                                <hr class="mb-0 navbar-vertical-divider">
                            </div>
                        </div>
                        <a class="nav-link" href="{{ route('reports.integrations.whatsapp') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fab fa-whatsapp"></span></span>
                                <span class="nav-link-text ps-1">Test WhatsApp API</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('reports.integrations.notifications') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-bell"></span></span>
                                <span class="nav-link-text ps-1">Test Notifications & Email</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('email.settings') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-cog"></span></span>
                                <span class="nav-link-text ps-1">Email Server Settings</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('reports.integrations.easyparcel') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-truck"></span></span>
                                <span class="nav-link-text ps-1">EasyParcel Rate Checker</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('marketplace-shops.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-store"></span></span>
                                <span class="nav-link-text ps-1">E-commerce Shops</span>
                            </div>
                        </a>
                    </li>
                    @endrole

                    {{-- System --}}
                    <li class="nav-item">
                        <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                            <div class="col-auto navbar-vertical-label">System</div>
                            <div class="col ps-0">
                                <hr class="mb-0 navbar-vertical-divider">
                            </div>
                        </div>

                        <a class="nav-link" href="{{ route('profile.edit') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-cog"></span></span>
                                <span class="nav-link-text ps-1">My Profile</span>
                            </div>
                        </a>
                        <a class="nav-link" href="{{ route('email.inbox') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-envelope"></span></span>
                                <span class="nav-link-text ps-1">My Email Inbox</span>
                            </div>
                        </a>
                        @if (auth()->user()->hasRole('admin') || auth()->user()->can('projects.calendar.manage'))
                            <a class="nav-link" href="{{ route('calendar') }}">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-calendar-alt"></span></span>
                                    <span class="nav-link-text ps-1">My Calendar</span>
                                </div>
                            </a>
                        @endif
                        <a class="nav-link" href="{{ route('tasks.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-tasks"></span></span>
                                <span class="nav-link-text ps-1">My Tasks</span>
                            </div>
                        </a>

                        @role('admin')
                        <a class="nav-link dropdown-indicator" href="#errorPagesMenu" role="button"
                            data-bs-toggle="collapse" aria-expanded="false" aria-controls="errorPagesMenu">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-exclamation-triangle"></span></span>
                                <span class="nav-link-text ps-1">Error pages</span>
                            </div>
                        </a>
                        <ul class="nav collapse" id="errorPagesMenu">
                            @foreach (['403' => 'Forbidden', '404' => 'Not found', '419' => 'Session expired', '429' => 'Too many requests', '500' => 'Server error', '503' => 'Unavailable'] as $code => $label)
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('error-pages.preview', $code) }}">
                                        <span class="nav-link-text ps-1">{{ $code }} · {{ $label }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        @endrole
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>