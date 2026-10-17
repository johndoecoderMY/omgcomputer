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
                <img class="me-2 brand-logo" src="{{ asset('assets/img/omg/logoex2.png') }}" alt="OMG Computer logo"
                    width="80" />
            </div>
        </a>
    </div>

    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content scrollbar">
            <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">
                @if (auth()->user()->hasRole('customer'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('projects.index') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-diagram-project"></span></span>
                                <span class="nav-link-text ps-1">My Projects</span>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('profile.edit') }}">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-cog"></span></span>
                                <span class="nav-link-text ps-1">My Profile</span>
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

                {{-- Support Desk --}}
                <li class="nav-item">
                    <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                        <div class="col-auto navbar-vertical-label">Support Desk</div>
                        <div class="col ps-0">
                            <hr class="mb-0 navbar-vertical-divider">
                        </div>
                    </div>

                    <a class="nav-link dropdown-indicator" href="#repairTicketsMenu" role="button"
                        data-bs-toggle="collapse" aria-expanded="false" aria-controls="repairTicketsMenu">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-ticket-alt"></span></span>
                            <span class="nav-link-text ps-1">Repair Tickets</span>
                        </div>
                    </a>

                    <ul class="nav collapse" id="repairTicketsMenu">
                        <li class="nav-item">
                            <a class="nav-link dropdown-indicator" href="#repairTicketRecordsMenu" role="button"
                                data-bs-toggle="collapse" aria-expanded="false" aria-controls="repairTicketRecordsMenu">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-clipboard-list"></span></span>
                                    <span class="nav-link-text ps-1">Tickets</span>
                                </div>
                            </a>
                            <ul class="nav collapse" id="repairTicketRecordsMenu">
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
                        <li class="nav-item">
                            <a class="nav-link dropdown-indicator" href="#repairTicketAssignmentMenu" role="button"
                                data-bs-toggle="collapse" aria-expanded="false" aria-controls="repairTicketAssignmentMenu">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-user-check"></span></span>
                                    <span class="nav-link-text ps-1">Assignment</span>
                                </div>
                            </a>
                            <ul class="nav collapse" id="repairTicketAssignmentMenu">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('tickets.index', ['assigned' => 'unassigned']) }}">
                                        <span class="nav-link-text ps-1">Unassigned</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('tickets.index', ['assigned' => 'mine']) }}">
                                        <span class="nav-link-text ps-1">Assigned to me</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link dropdown-indicator" href="#repairTicketStatusMenu" role="button"
                                data-bs-toggle="collapse" aria-expanded="false" aria-controls="repairTicketStatusMenu">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-list-check"></span></span>
                                    <span class="nav-link-text ps-1">Status</span>
                                </div>
                            </a>
                            <ul class="nav collapse" id="repairTicketStatusMenu">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('tickets.index', ['status' => 'waiting_customer']) }}">
                                        <span class="nav-link-text ps-1">Awaiting customer</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('tickets.index', ['status' => 'waiting_parts']) }}">
                                        <span class="nav-link-text ps-1">Waiting for parts</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('tickets.index', ['status' => 'ready_for_pickup']) }}">
                                        <span class="nav-link-text ps-1">Ready for pickup</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('tickets.index', ['status' => 'closed']) }}">
                                        <span class="nav-link-text ps-1">Closed</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link dropdown-indicator" href="#repairTicketBookingsMenu" role="button"
                                data-bs-toggle="collapse" aria-expanded="false" aria-controls="repairTicketBookingsMenu">
                                <div class="d-flex align-items-center">
                                    <span class="nav-link-icon"><span class="fas fa-calendar-check"></span></span>
                                    <span class="nav-link-text ps-1">Bookings &messages</span>
                                </div>
                            </a>
                            <ul class="nav collapse" id="repairTicketBookingsMenu">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('bookings.index') }}">
                                        <span class="nav-link-text ps-1">Customer bookings</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('bookings.create') }}" target="_blank">
                                        <span class="nav-link-text ps-1">Public booking calendar</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('customer-messages.index') }}">
                                        <span class="nav-link-text ps-1">Customer messages</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>

                {{-- Projects --}}
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('projects.index') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-diagram-project"></span></span>
                            <span class="nav-link-text ps-1">Projects</span>
                        </div>
                    </a>
                    <a class="nav-link" href="{{ route('projects.calendar') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-calendar-days"></span></span>
                            <span class="nav-link-text ps-1">Calendar</span>
                        </div>
                    </a>
                </li>

                {{-- Customer records --}}
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('customers.index') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-users"></span></span>
                            <span class="nav-link-text ps-1">Customer records</span>
                        </div>
                    </a>
                </li>

                {{-- OMG Computer --}}
                <li class="nav-item">
                    <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                        <div class="col-auto navbar-vertical-label">OMG Computer</div>
                        <div class="col ps-0">
                            <hr class="mb-0 navbar-vertical-divider">
                        </div>
                    </div>

                    <a class="nav-link" href="{{ route('development.overview') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-home"></span></span>
                            <span class="nav-link-text ps-1">Overview</span>
                        </div>
                    </a>

                    <a class="nav-link" href="{{ route('tasks.index') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-list-check"></span></span>
                            <span class="nav-link-text ps-1">Tasks</span>
                        </div>
                    </a>

                    <a class="nav-link" href="{{ route('development.plan') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-file-alt"></span></span>
                            <span class="nav-link-text ps-1">Business Plan</span>
                        </div>
                    </a>

                    <a class="nav-link" href="{{ route('development.flowcharts.index') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-diagram-project"></span></span>
                            <span class="nav-link-text ps-1">Flow Charts</span>
                        </div>
                    </a>
                    <a class="nav-link" href="{{ route('development.sops') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-book"></span></span>
                            <span class="nav-link-text ps-1">SOP Library</span>
                        </div>
                    </a>
                </li>



                {{-- Parts & Inventory --}}
                <li class="nav-item">

                    <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                        <div class="col-auto navbar-vertical-label">Manage</div>
                        <div class="col ps-0">
                            <hr class="mb-0 navbar-vertical-divider">
                        </div>
                    </div>

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
                    @role('admin')
                        <a class="nav-link dropdown-indicator" href="#userAccountsMenu" role="button"
                            data-bs-toggle="collapse" aria-expanded="false" aria-controls="userAccountsMenu">
                            <div class="d-flex align-items-center">
                                <span class="nav-link-icon"><span class="fas fa-users"></span></span>
                                <span class="nav-link-text ps-1">User accounts</span>
                            </div>
                        </a>
                        <ul class="nav collapse" id="userAccountsMenu">
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.users.index') }}">
                                    <span class="nav-link-text ps-1">Members </span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('admin.users.customers') }}">
                                    <span class="nav-link-text ps-1">Customer accounts</span>
                                </a>
                            </li>
                        </ul>
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
                    @endrole

                    <a class="nav-link" href="{{ route('products.index') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-tags"></span></span>
                            <span class="nav-link-text ps-1">Products & Services</span>
                        </div>
                    </a>

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
                                <span class="nav-link-text ps-1">Spare Parts</span>
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
                </li>

                {{-- Billing & Payments --}}
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

                {{-- Reports --}}
                <li class="nav-item">
                    <a class="nav-link dropdown-indicator" href="#reportsMenu" role="button"
                        data-bs-toggle="collapse" aria-expanded="false" aria-controls="reportsMenu">
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
                        @role('admin')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('reports.integrations.whatsapp') }}">
                                    <span class="nav-link-text ps-1">Test WhatsApp API</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('reports.integrations.easyparcel') }}">
                                    <span class="nav-link-text ps-1">EasyParcel Rate Checker</span>
                                </a>
                            </li>
                        @endrole
                    </ul>
                </li>

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
                </li>
                @endif
            </ul>
        </div>
    </div>
</nav>