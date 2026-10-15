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

                {{-- Dashboard --}}
                <li class="nav-item">
                    <a class="nav-link" href="#">
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
                            <a class="nav-link" href="#">
                                <span class="nav-link-text ps-1">All Tickets</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <span class="nav-link-text ps-1">Create Ticket</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <span class="nav-link-text ps-1">Unassigned Tickets</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <span class="nav-link-text ps-1">My Assigned Tickets</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <span class="nav-link-text ps-1">Awaiting Customer</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <span class="nav-link-text ps-1">Waiting for Parts</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <span class="nav-link-text ps-1">Ready for Pickup</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">
                                <span class="nav-link-text ps-1">Closed Tickets</span>
                            </a>
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
                </li>

                {{-- Customers --}}
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-users"></span></span>
                            <span class="nav-link-text ps-1">Customers</span>
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
                            <span class="nav-link-text ps-1">Website Flow Charts</span>
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

                      <a class="nav-link" href="#">
                        <div class="d-flex align-items-center">
                            <span class="nav-link-icon"><span class="fas fa-users-cog"></span></span>
                            <span class="nav-link-text ps-1">Teams</span>
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
                    <a class="nav-link" href="{{ route('reports.index') }}">
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
                        <div class="col ps-0">
                            <hr class="mb-0 navbar-vertical-divider">
                        </div>
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