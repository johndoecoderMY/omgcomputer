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
                data-bs-placement="left" title="Toggle Navigation"><span class="navbar-toggle-icon"><span
                        class="toggle-line"></span></span></button>
        </div><a class="navbar-brand" href="{{ route('dashboard') }}">
            <div class="d-flex align-items-center py-3">
                <img class="me-2 brand-logo" src="{{ asset('assets/img/omg/logoex2.png') }}" alt="OMG Computer logo"  width="80" />
            </div>
        </a>
    </div>
  
    <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content scrollbar">
            <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">

                {{-- Overview --}}
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-chart-pie"></span></span>
                            <span class="nav-link-text ps-1">Dashboard</span>
                        </div>
                    </a>
                </li>

                {{-- Support-ticket-style repair workflow --}}
                <li class="nav-item">
                    <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                        <div class="col-auto navbar-vertical-label">Repair Support Desk</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider"></div>
                    </div>

                    <a class="nav-link dropdown-indicator"
                       href="#repairTicketsMenu"
                       role="button"
                       data-bs-toggle="collapse"
                       aria-expanded="{{ request()->routeIs('tickets.*') ? 'true' : 'false' }}"
                       aria-controls="repairTicketsMenu">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-ticket-alt"></span></span>
                            <span class="nav-link-text ps-1">Repair Tickets</span>
                        </div>
                    </a>

                    <ul class="nav collapse {{ request()->routeIs('tickets.*') ? 'show' : '' }}" id="repairTicketsMenu">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tickets.index') ? 'active' : '' }}" href="{{ route('tickets.index') }}">
                                <span class="nav-link-text ps-1">All Tickets</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('tickets.create') ? 'active' : '' }}" href="{{ route('tickets.create') }}">
                                <span class="nav-link-text ps-1">Create Ticket</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('tickets.index', ['assigned' => 'unassigned']) }}">
                                <span class="nav-link-text ps-1">Unassigned Tickets</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('tickets.index', ['assigned' => 'mine']) }}">
                                <span class="nav-link-text ps-1">My Assigned Tickets</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('tickets.index', ['status' => 'waiting_customer']) }}">
                                <span class="nav-link-text ps-1">Awaiting Customer</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('tickets.index', ['status' => 'waiting_parts']) }}">
                                <span class="nav-link-text ps-1">Waiting for Parts</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('tickets.index', ['status' => 'ready_for_pickup']) }}">
                                <span class="nav-link-text ps-1">Ready for Pickup</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('tickets.index', ['status' => 'closed']) }}">
                                <span class="nav-link-text ps-1">Closed Tickets</span>
                            </a>
                        </li>
                    </ul>
                </li>

                {{-- Customers --}}
                <li class="nav-item">
                    <a class="nav-link dropdown-indicator"
                       href="#customerMenu"
                       role="button"
                       data-bs-toggle="collapse"
                       aria-expanded="false"
                       aria-controls="customerMenu">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-users"></span></span>
                            <span class="nav-link-text ps-1">Customers</span>
                        </div>
                    </a>

                    <ul class="nav collapse" id="customerMenu">
                        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-link-text ps-1">Customer List</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-link-text ps-1">Add Customer</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="#"><span class="nav-link-text ps-1">Customer History</span></a></li>
                    </ul>
                </li>

                {{-- Main project workspace --}}
                <li class="nav-item">
                    <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                        <div class="col-auto navbar-vertical-label">This project</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider"></div>
                    </div>
                    <a class="nav-link {{ request()->routeIs('planner.projects') ? 'active' : '' }}" href="{{ route('planner.projects') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-home"></span></span>
                            <span class="nav-link-text ps-1">Overview</span>
                        </div>
                    </a>
                    <a class="nav-link {{ request()->routeIs('planner.index') && request('view') !== 'kanban' ? 'active' : '' }}" href="{{ route('planner.index', ['view' => 'list']) }}">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-list-check"></span></span><span class="nav-link-text ps-1">Tasks — List</span></div>
                    </a>
                    <a class="nav-link {{ request()->routeIs('planner.index') && request('view') === 'kanban' ? 'active' : '' }}" href="{{ route('planner.index', ['view' => 'kanban']) }}">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-columns"></span></span><span class="nav-link-text ps-1">Tasks — Kanban</span></div>
                    </a>
                    <a class="nav-link {{ request()->routeIs('planner.business-plan') ? 'active' : '' }}" href="{{ route('planner.business-plan') }}">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-file-alt"></span></span><span class="nav-link-text ps-1">Business Plan</span></div>
                    </a>
                </li>

                <li class="nav-item">
                    <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                        <div class="col-auto navbar-vertical-label">Projects &amp; Teams</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider"></div>
                    </div>
                    <a class="nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}" href="{{ route('projects.index') }}">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-diagram-project"></span></span><span class="nav-link-text ps-1">Projects</span></div>
                    </a>
                    <a class="nav-link {{ request()->routeIs('teams.*') ? 'active' : '' }}" href="{{ route('teams.index') }}">
                        <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-users-cog"></span></span><span class="nav-link-text ps-1">Teams &amp; Technicians</span></div>
                    </a>
                </li>

                {{-- Parts inventory --}}
                <li class="nav-item">
                    <a class="nav-link dropdown-indicator"
                       href="#inventoryMenu"
                       role="button"
                       data-bs-toggle="collapse"
                       aria-expanded="{{ request()->routeIs('inventory.*') ? 'true' : 'false' }}"
                       aria-controls="inventoryMenu">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-boxes"></span></span>
                            <span class="nav-link-text ps-1">Parts & Inventory</span>
                        </div>
                    </a>

                    <ul class="nav collapse {{ request()->routeIs('inventory.*') ? 'show' : '' }}" id="inventoryMenu">
                        <li class="nav-item"><a class="nav-link" href="{{ route('inventory.index') }}"><span class="nav-link-text ps-1">Spare Parts</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('inventory.index') }}#movements"><span class="nav-link-text ps-1">Stock Movements</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('inventory.index', ['low_stock' => 1]) }}"><span class="nav-link-text ps-1">Low Stock</span></a></li>
                    </ul>
                </li>

                {{-- Quotes, invoices and payments --}}
                <li class="nav-item">
                    <a class="nav-link dropdown-indicator"
                       href="#billingMenu"
                       role="button"
                       data-bs-toggle="collapse"
                       aria-expanded="{{ request()->routeIs('billing.*') ? 'true' : 'false' }}"
                       aria-controls="billingMenu">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-file-invoice-dollar"></span></span>
                            <span class="nav-link-text ps-1">Billing & Payments</span>
                        </div>
                    </a>

                    <ul class="nav collapse {{ request()->routeIs('billing.*') ? 'show' : '' }}" id="billingMenu">
                        <li class="nav-item"><a class="nav-link" href="{{ route('billing.index', ['type' => 'quote']) }}"><span class="nav-link-text ps-1">Quotations</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('billing.index', ['type' => 'invoice']) }}"><span class="nav-link-text ps-1">Invoices</span></a></li>
                        <li class="nav-item"><a class="nav-link" href="{{ route('billing.index', ['type' => 'invoice', 'status' => 'partial']) }}"><span class="nav-link-text ps-1">Part-paid invoices</span></a></li>
                    </ul>
                </li>

                {{-- Reporting --}}
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-chart-line"></span></span>
                            <span class="nav-link-text ps-1">Reports & Analytics</span>
                        </div>
                    </a>
                </li>

                {{-- System --}}
                <li class="nav-item">
                    <div class="row navbar-vertical-label-wrapper mt-3 mb-2">
                        <div class="col-auto navbar-vertical-label">System</div>
                        <div class="col ps-0"><hr class="mb-0 navbar-vertical-divider"></div>
                    </div>
                    <a class="nav-link" href="#">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-cog"></span></span>
                            <span class="nav-link-text ps-1">Settings</span>
                        </div>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>