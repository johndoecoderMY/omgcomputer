@extends('layouts.app')

@section('title', 'PC & Laptop Repair Shop Business Plan')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <p class="text-primary fw-bold text-uppercase fs-10 mb-1">Planning &amp; Progress</p>
                <h1 class="h2 mb-1">PC &amp; Laptop Repair Shop</h1>
                <p class="text-700 mb-0">A practical, service-first business plan and preliminary financial estimates.</p>
            </div>
            <a href="{{ route('planner.index') }}" class="btn btn-outline-primary">
                <span class="fas fa-columns me-2"></span>Back to planner
            </a>
        </div>

        <div class="alert alert-warning border-0 shadow-sm mb-4" role="note">
            <span class="fas fa-circle-info me-2"></span>
            These figures are planning estimates in Malaysian ringgit (RM), not guaranteed results. Confirm local rental, licensing, tax, and supplier costs before committing capital.
        </div>

        <section class="card border-0 shadow-sm mb-4" id="executive-summary">
            <div class="card-body p-4 p-lg-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <p class="text-primary fw-bold text-uppercase fs-10 mb-2">Executive summary</p>
                        <h2 class="h3 text-1000">A lean, service-led computer repair shop in Kampar, Perak.</h2>
                        <p class="text-700 mb-0">Start as an owner-operated PC and laptop service business, build trust through clear quotations and dependable work, and keep cash available by purchasing parts only after customers approve a repair. The initial plan estimates RM15,500 in setup capital, RM1,920 in monthly fixed expenses, and RM75 contribution per average job before fixed costs.</p>
                    </div>
                    <div class="col-lg-4">
                        <div class="row g-3 text-center">
                            <div class="col-6"><div class="rounded bg-body-tertiary p-3 h-100"><p class="text-600 fs-10 fw-bold text-uppercase mb-1">Setup estimate</p><p class="h4 mb-0">RM15,500</p></div></div>
                            <div class="col-6"><div class="rounded bg-body-tertiary p-3 h-100"><p class="text-600 fs-10 fw-bold text-uppercase mb-1">Monthly fixed costs</p><p class="h4 mb-0">RM1,920</p></div></div>
                            <div class="col-12"><div class="rounded bg-primary-subtle p-3"><p class="text-600 fs-10 fw-bold text-uppercase mb-1">Operating break-even target</p><p class="h4 text-primary mb-0">About 26 jobs / month</p></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card mb-4" id="location">
            <div class="card-header">
                <h2 class="h5 mb-1">Proposed location: Kampar, Perak</h2>
                <p class="text-600 fs-10 mb-0">The map marks the general Kampar area, not a selected shop lot or confirmed business address.</p>
            </div>
            <div class="row g-0">
                <div class="col-lg-7">
                    <div class="ratio ratio-16x9 h-100">
                        <iframe
                            src="https://www.google.com/maps?q=Kampar%2C%20Perak%2C%20Malaysia&amp;output=embed"
                            title="Map showing Kampar, Perak, Malaysia"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen>
                        </iframe>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card-body p-4">
                        <h3 class="h5 text-1000">Site selection checklist</h3>
                        <p class="text-700">Treat Kampar as the initial target area and compare specific shop lots before signing a lease. Validate demand and total occupancy cost at each location.</p>
                        <ul class="text-700 mb-3">
                            <li>Check visibility, walk-in access, parking, and public transport.</li>
                            <li>Compare rent, deposit, utilities, renovation, and lease terms.</li>
                            <li>Review nearby computer shops and the services they advertise.</li>
                            <li>Confirm permitted business use and local council requirements.</li>
                            <li>Ask potential customers and nearby businesses which repairs they need.</li>
                        </ul>
                        <a class="btn btn-sm btn-outline-primary" href="https://www.google.com/maps/search/?api=1&amp;query=Kampar%2C%20Perak%2C%20Malaysia" target="_blank" rel="noopener noreferrer">
                            <span class="fas fa-map-location-dot me-2"></span>Explore Kampar in Google Maps
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="card mb-4" id="concept">
            <div class="card-header"><h2 class="h5 mb-0">1. Business concept</h2></div>
            <div class="card-body">
                <p class="text-700">Open a small PC and laptop service shop focused on basic maintenance and software services. Start with labour-based work and avoid holding too much stock; order replacement parts after customers approve the job.</p>
                <div class="row g-3">
                    @foreach ([
                        ['fa-broom', 'PC and laptop cleaning'],
                        ['fa-temperature-half', 'Thermal paste replacement'],
                        ['fa-compact-disc', 'Windows installation and driver setup'],
                        ['fa-screwdriver-wrench', 'Software installation and troubleshooting'],
                        ['fa-memory', 'RAM and SSD upgrades'],
                        ['fa-gauge-high', 'Basic maintenance and performance troubleshooting'],
                    ] as [$icon, $service])
                        <div class="col-sm-6 col-xl-4">
                            <div class="d-flex align-items-center h-100 p-3 rounded bg-body-tertiary">
                                <span class="fas {{ $icon }} text-primary me-3"></span>
                                <span class="text-800">{{ $service }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="card mb-4" id="market-positioning">
            <div class="card-header"><h2 class="h5 mb-0">Market positioning and customer validation</h2></div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h3 class="h6 text-1000">Target customer groups to investigate</h3>
                        <ul class="text-700 mb-0">
                            <li>Residents and families needing everyday PC or laptop support.</li>
                            <li>Students and education-related customers seeking maintenance, upgrades, and software setup.</li>
                            <li>Small offices and local businesses that need dependable device maintenance.</li>
                            <li>Customers who prefer a local repair service with a clear explanation before work begins.</li>
                        </ul>
                    </div>
                    <div class="col-lg-6">
                        <h3 class="h6 text-1000">Validate before committing to a lease</h3>
                        <ul class="text-700 mb-0">
                            <li>Compare local repair providers, public prices, opening hours, and customer reviews.</li>
                            <li>Ask prospective customers which services they need and what turnaround they expect.</li>
                            <li>Request actual rental quotations for several suitable locations in Kampar.</li>
                            <li>Test demand with a small pre-launch campaign and track enquiries, not just likes.</li>
                            <li>Confirm licensing, tax, insurance, and business-registration requirements with the relevant authorities or a qualified adviser.</li>
                        </ul>
                    </div>
                </div>
                <div class="alert alert-info mt-4 mb-0" role="note">
                    <span class="fas fa-lightbulb me-2"></span>
                    These are customer segments to research, not verified market-size or demand claims. Update the plan after collecting local evidence.
                </div>
            </div>
        </section>

        <section class="card mb-4" id="pricing">
            <div class="card-header"><h2 class="h5 mb-0">2. Service pricing</h2></div>
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead><tr><th scope="col">Service</th><th scope="col" class="text-end">Estimated price</th></tr></thead>
                    <tbody>
                        <tr><td>Desktop PC cleaning</td><td class="text-end">RM40–70</td></tr>
                        <tr><td>Laptop cleaning</td><td class="text-end">RM60–100</td></tr>
                        <tr><td>Thermal paste replacement</td><td class="text-end">RM80–150</td></tr>
                        <tr><td>Windows installation</td><td class="text-end">RM60–120</td></tr>
                        <tr><td>Driver installation and setup</td><td class="text-end">RM30–60</td></tr>
                        <tr><td>Software installation</td><td class="text-end">RM20–50</td></tr>
                        <tr><td>RAM installation (labour)</td><td class="text-end">RM30–50</td></tr>
                        <tr><td>SSD installation (labour)</td><td class="text-end">RM30–80</td></tr>
                        <tr><td>Basic troubleshooting</td><td class="text-end">RM30–60</td></tr>
                        <tr><td>Full maintenance package</td><td class="text-end">RM120–180</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-600 fs-10">Parts, genuine software licences, data backup, and complicated jobs should be quoted and charged separately.</div>
        </section>

        <section class="card mb-4" id="service-operations">
            <div class="card-header"><h2 class="h5 mb-0">Service delivery and customer workflow</h2></div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ([
                        ['01', 'Receive and record', 'Record customer contact details, device make/model, reported symptoms, accessories received, and visible condition.'],
                        ['02', 'Inspect and quote', 'Diagnose the issue, explain options, separate labour from parts, and obtain approval before additional work.'],
                        ['03', 'Repair and communicate', 'Carry out only the approved work, protect customer data, and communicate if scope, cost, or timing changes.'],
                        ['04', 'Test and return', 'Test the agreed repair, document parts and work completed, explain any limitations, and confirm collection/payment.'],
                    ] as [$step, $title, $description])
                        <div class="col-sm-6 col-xl-3">
                            <div class="h-100 p-3 rounded bg-body-tertiary">
                                <span class="badge bg-primary mb-3">{{ $step }}</span>
                                <h3 class="h6">{{ $title }}</h3>
                                <p class="text-700 fs-10 mb-0">{{ $description }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="text-600 fs-10 mt-3 mb-0">Use a job record for every device, including consent, approved quotation, parts source, diagnostic notes, test results, and collection status.</p>
            </div>
        </section>

        <section class="row g-4 mb-4">
            <div class="col-xl-6" id="startup-capital">
                <div class="card h-100">
                    <div class="card-header"><h2 class="h5 mb-0">3. Initial startup capital</h2></div>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead><tr><th scope="col">Item</th><th scope="col" class="text-end">Estimated cost</th></tr></thead>
                            <tbody>
                                <tr><td>Rental deposit and advance</td><td class="text-end">RM4,200</td></tr>
                                <tr><td>Workbench, chairs and shelves</td><td class="text-end">RM1,500</td></tr>
                                <tr><td>Repair tools and cleaning equipment</td><td class="text-end">RM1,500</td></tr>
                                <tr><td>Thermal paste and consumables</td><td class="text-end">RM400</td></tr>
                                <tr><td>Test monitor, cables and adapters</td><td class="text-end">RM800</td></tr>
                                <tr><td>Signboard and shop setup</td><td class="text-end">RM1,000</td></tr>
                                <tr><td>Registration and licensing allowance</td><td class="text-end">RM800</td></tr>
                                <tr><td>Initial advertising</td><td class="text-end">RM300</td></tr>
                                <tr><td>Emergency cash reserve</td><td class="text-end">RM5,000</td></tr>
                            </tbody>
                            <tfoot><tr class="fw-bold"><th scope="row">Total estimated capital</th><td class="text-end">RM15,500</td></tr></tfoot>
                        </table>
                    </div>
                    <div class="card-body">
                        <h3 class="h6 text-1000">Recommended capital</h3>
                        <ul class="text-700 mb-0">
                            <li>Minimum target: RM15,000–20,000</li>
                            <li>Safer target: RM20,000–25,000</li>
                            <li>Renovation or air-conditioning may increase the budget.</li>
                            <li>Keep personal living expenses separate from business capital.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-xl-6" id="monthly-costs">
                <div class="card h-100">
                    <div class="card-header"><h2 class="h5 mb-0">4. Monthly operating expenses</h2></div>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead><tr><th scope="col">Expense</th><th scope="col" class="text-end">Monthly cost</th></tr></thead>
                            <tbody>
                                <tr><td>Shop rental</td><td class="text-end">RM1,200</td></tr>
                                <tr><td>Electricity and water</td><td class="text-end">RM200</td></tr>
                                <tr><td>Internet and phone</td><td class="text-end">RM120</td></tr>
                                <tr><td>Marketing</td><td class="text-end">RM200</td></tr>
                                <tr><td>Miscellaneous expenses</td><td class="text-end">RM200</td></tr>
                            </tbody>
                            <tfoot><tr class="fw-bold"><th scope="row">Total monthly expenses</th><td class="text-end">RM1,920</td></tr></tfoot>
                        </table>
                    </div>
                    <div class="card-body text-600 fs-10">Excludes replacement parts, tax, loan repayments, employee wages, and the owner's personal income.</div>
                </div>
            </div>
        </section>

        <section class="card mb-4" id="profit-model">
            <div class="card-header"><h2 class="h5 mb-0">5. Estimated contribution per job</h2></div>
            <div class="card-body">
                <div class="row g-3 text-center">
                    <div class="col-md-4"><div class="p-3 rounded bg-body-tertiary h-100"><p class="text-600 fs-10 text-uppercase fw-bold mb-2">Average customer bill</p><p class="h3 mb-0">RM90</p></div></div>
                    <div class="col-md-4"><div class="p-3 rounded bg-body-tertiary h-100"><p class="text-600 fs-10 text-uppercase fw-bold mb-2">Average consumables</p><p class="h3 mb-0">RM15</p></div></div>
                    <div class="col-md-4"><div class="p-3 rounded bg-primary-subtle h-100"><p class="text-600 fs-10 text-uppercase fw-bold mb-2">Contribution per job</p><p class="h3 text-primary mb-0">RM75</p></div></div>
                </div>
                <p class="text-700 mt-3 mb-0">RM90 average bill − RM15 consumables = RM75 contribution per job. This contribution covers monthly operating expenses first; it is not the same as take-home income.</p>
            </div>
        </section>

        <section class="card mb-4" id="scenarios">
            <div class="card-header"><h2 class="h5 mb-0">6. Monthly revenue and profit scenarios</h2></div>
            <div class="card-body pb-0">
                <p class="text-700">Assumption: 26 working days per month. Estimated profit is jobs × RM75 contribution − RM1,920 monthly operating expenses.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead><tr><th scope="col">Jobs per day</th><th scope="col" class="text-end">Monthly jobs</th><th scope="col" class="text-end">Monthly sales</th><th scope="col" class="text-end">Estimated profit*</th></tr></thead>
                    <tbody>
                        <tr><td>1</td><td class="text-end">26</td><td class="text-end">RM2,340</td><td class="text-end">RM30</td></tr>
                        <tr><td>2</td><td class="text-end">52</td><td class="text-end">RM4,680</td><td class="text-end">RM1,980</td></tr>
                        <tr><td>3</td><td class="text-end">78</td><td class="text-end">RM7,020</td><td class="text-end">RM3,930</td></tr>
                        <tr><td>4</td><td class="text-end">104</td><td class="text-end">RM9,360</td><td class="text-end">RM5,880</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-600 fs-10">*After estimated RM15 consumables per job and RM1,920 monthly operating expenses, but before tax, loan repayments, and personal income.</div>
        </section>

        <section class="card mb-4" id="break-even">
            <div class="card-header"><h2 class="h5 mb-0">7. Break-even calculation</h2></div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="border-start border-4 border-primary ps-3 h-100">
                            <p class="text-600 text-uppercase fs-10 fw-bold mb-2">Operating break-even</p>
                            <p class="h4 text-1000">RM1,920 ÷ RM75 = 25.6 jobs</p>
                            <p class="text-700 mb-0">Plan for approximately <strong>26 jobs per month</strong> to cover operating expenses.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border-start border-4 border-success ps-3 h-100">
                            <p class="text-600 text-uppercase fs-10 fw-bold mb-2">Including RM2,500 owner income</p>
                            <p class="h4 text-1000">(RM1,920 + RM2,500) ÷ RM75 = 58.93 jobs</p>
                            <p class="text-700 mb-0">Plan for approximately <strong>59 jobs per month</strong>, or about 2–3 jobs per working day.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card mb-4" id="strategy">
            <div class="card-header"><h2 class="h5 mb-0">8. Recommended business strategy</h2></div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="h-100 p-3 rounded bg-body-tertiary">
                            <span class="badge bg-primary mb-3">Phase 1</span>
                            <h3 class="h5">Start small</h3>
                            <ul class="text-700 mb-0">
                                <li>Find an affordable rental unit.</li>
                                <li>Buy essential tools only.</li>
                                <li>Focus on cleaning, thermal paste, and software installation.</li>
                                <li>Avoid expensive spare-part inventory.</li>
                                <li>Advertise through Google Business Profile, Facebook, local groups, and WhatsApp.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="h-100 p-3 rounded bg-body-tertiary">
                            <span class="badge bg-info mb-3">Phase 2</span>
                            <h3 class="h5">Build a customer base</h3>
                            <ul class="text-700 mb-0">
                                <li>Offer maintenance packages.</li>
                                <li>Encourage customer reviews and referrals.</li>
                                <li>Record every job, expense, and payment.</li>
                                <li>Track which services generate the most profit.</li>
                                <li>Provide clear quotations before starting repairs.</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="h-100 p-3 rounded bg-body-tertiary">
                            <span class="badge bg-success mb-3">Phase 3</span>
                            <h3 class="h5">Expand carefully</h3>
                            <ul class="text-700 mb-0">
                                <li>Add SSD and RAM sales.</li>
                                <li>Offer scheduled maintenance to small offices.</li>
                                <li>Consider refurbished PC sales after demand is established.</li>
                                <li>Upgrade equipment using business profits.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="card mb-4" id="marketing">
            <div class="card-header"><h2 class="h5 mb-0">Marketing and customer acquisition</h2></div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6 col-xl-3">
                        <h3 class="h6 text-1000">Be discoverable</h3>
                        <p class="text-700 mb-0">Set up accurate business listings, service hours, contact details, and photos once the shop address is confirmed.</p>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <h3 class="h6 text-1000">Show clear offers</h3>
                        <p class="text-700 mb-0">Publish a concise service menu and starting labour prices, with a clear note that diagnosis and parts can affect the final quote.</p>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <h3 class="h6 text-1000">Build trust</h3>
                        <p class="text-700 mb-0">Ask satisfied customers for honest reviews, respond professionally, and never publish device or customer details without consent.</p>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <h3 class="h6 text-1000">Measure results</h3>
                        <p class="text-700 mb-0">Track where enquiries come from, how many become approved jobs, average bill, and repeat/referral customers.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="card mb-4" id="risk-management">
            <div class="card-header"><h2 class="h5 mb-0">Risk management and controls</h2></div>
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead><tr><th scope="col">Risk</th><th scope="col">Practical control</th></tr></thead>
                    <tbody>
                        <tr><td>Slow initial demand</td><td>Validate demand before leasing, stage purchases, keep a cash reserve, and review enquiries and approved jobs monthly.</td></tr>
                        <tr><td>Unexpected repair scope</td><td>Use written diagnostics and quotations; get customer approval before extra labour or parts.</td></tr>
                        <tr><td>Customer data loss or privacy incident</td><td>Obtain explicit consent for backups or data access, limit access, secure credentials, and document handovers.</td></tr>
                        <tr><td>Parts delays or price changes</td><td>Order against approved jobs, confirm availability and price with suppliers, and explain lead times before taking deposits.</td></tr>
                        <tr><td>Cash-flow pressure</td><td>Track fixed and variable costs separately, reconcile payments, and preserve the emergency reserve.</td></tr>
                        <tr><td>Warranty or repeat faults</td><td>Record tests and parts used, state service terms clearly, and set aside time and funds for follow-up work.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card mb-4" id="key-metrics">
            <div class="card-header"><h2 class="h5 mb-0">Monthly owner dashboard</h2></div>
            <div class="card-body">
                <p class="text-700">Review these measures monthly and compare actual results with the assumptions in this plan.</p>
                <div class="row g-3">
                    @foreach ([
                        ['Demand', 'Enquiries, approved jobs, lead source, and enquiry-to-booking rate.'],
                        ['Service mix', 'Jobs and revenue by service, average bill, and repeat visits.'],
                        ['Contribution', 'Sales less parts and consumables per job, compared with the RM75 planning estimate.'],
                        ['Cash position', 'Fixed expenses, supplier payments, cash reserve, and unpaid customer balances.'],
                        ['Delivery', 'Turnaround time, jobs awaiting parts, rework, and customer feedback.'],
                    ] as [$metric, $description])
                        <div class="col-md-6 col-xl">
                            <div class="h-100 p-3 rounded bg-body-tertiary">
                                <h3 class="h6 text-1000">{{ $metric }}</h3>
                                <p class="text-700 fs-10 mb-0">{{ $description }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="card mb-4" id="business-rules">
            <div class="card-header"><h2 class="h5 mb-0">9. Important business rules</h2></div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ([
                        'Back up customer data only with permission.',
                        'Get consent before reinstalling Windows or formatting drives.',
                        'Use genuine software licences.',
                        'Keep customer devices and passwords secure.',
                        'Record parts costs separately from labour.',
                        'Maintain a cash reserve for slow months and unexpected expenses.',
                        'Confirm local registration, council licensing, and rental requirements before opening.',
                    ] as $rule)
                        <div class="col-md-6">
                            <div class="d-flex align-items-start">
                                <span class="fas fa-shield-halved text-primary mt-1 me-2"></span>
                                <span class="text-700">{{ $rule }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="card border-0 bg-primary text-white mb-4" id="recommendation">
            <div class="card-body p-4 p-lg-5">
                <p class="text-uppercase fw-bold fs-10 mb-2">Final recommendation</p>
                <h2 class="text-white h3">Start service-first, keep costs low, and grow from real demand.</h2>
                <p class="mb-0">Aim for about RM20,000 in available capital and at least three paying jobs per day. Avoid hiring until demand is consistent. In the first few months, focus on building a reliable customer base, managing cash flow, and learning which services are most profitable before investing in additional stock or equipment.</p>
            </div>
        </section>
    </div>
@endsection
