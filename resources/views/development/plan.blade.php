@extends('layouts.app')

@section('title', 'PC & Laptop Repair Shop Business Plan')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="OMG Computer" 

        title="PC & Laptop Repair Shop"
        description="Business plan, service menu, student services and financial targets · Kampar, Perak"
        {{-- :breadcrumbs="[['label' => 'Planning', 'url' => route('planner.index')]]" --}}
        {{-- :back-url="route('planner.index')" --}}
        {{-- back-label="Back to planner" --}}
    />

    <div class="alert alert-warning d-flex align-items-start gap-2 mb-4" role="note">
        <i class="fas fa-circle-info mt-1"></i>
        <div><strong>Planning estimates:</strong> All prices and financial figures are illustrative estimates in Malaysian ringgit (RM), not guaranteed results. Confirm local rent, licensing, tax, supplier costs and customer demand before committing capital.</div>
    </div>

    {{-- Executive summary --}}
    <div class="card border-0 shadow-sm mb-4 p-2">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-xl-7">
                    <span class="badge text-bg-primary mb-3">Business overview</span>
                    <h2 class="h3 mb-3">A service-first repair shop with a student technology support niche</h2>
                    <p class="text-body-secondary mb-3">Start lean with PC and laptop maintenance, troubleshooting and upgrades. Add ethical FYP consultations and technical mentoring for students, then grow through repeat customers, campus-focused promotion and small-business maintenance contracts.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge text-bg-light border text-dark"><i class="fas fa-screwdriver-wrench me-1 text-primary"></i>Repairs</span>
                        <span class="badge text-bg-light border text-dark"><i class="fas fa-graduation-cap me-1 text-primary"></i>Student services</span>
                        <span class="badge text-bg-light border text-dark"><i class="fas fa-bullhorn me-1 text-primary"></i>Local marketing</span>
                    </div>
                </div>
                <div class="col-xl-5">
                    <div class="row g-3">
                        <div class="col-sm-6"><div class="rounded-3 border p-3 h-100"><p class="small text-body-secondary mb-1">Estimated setup capital</p><div class="h3 mb-0">RM15,500</div></div></div>
                        <div class="col-sm-6"><div class="rounded-3 border p-3 h-100"><p class="small text-body-secondary mb-1">Monthly fixed costs</p><div class="h3 mb-0">RM1,920</div></div></div>
                        <div class="col-12"><div class="rounded-3 bg-primary-subtle p-3"><p class="small text-primary-emphasis mb-1">Operating break-even target</p><div class="h3 text-primary mb-1">26 standard jobs/month</div><p class="small text-body-secondary mb-0">Based on RM75 average contribution per job; student services may change the service mix.</p></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Location --}}
    <div class="card shadow-sm mb-4" id="location">
        <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
            <div><h2 class="h5 mb-1">1. Target location</h2><p class="small text-body-secondary mb-0">Kampar, Perak, Malaysia</p></div>
            <a class="btn btn-sm btn-outline-primary" href="https://www.google.com/maps/search/?api=1&amp;query=Kampar%2C%20Perak%2C%20Malaysia" target="_blank" rel="noopener noreferrer"><i class="fas fa-map-location-dot me-2"></i>Explore area</a>
        </div>
        <div class="row g-0">
            <div class="col-lg-7"><div class="ratio ratio-16x9"><iframe src="https://www.google.com/maps?q=Kampar%2C%20Perak%2C%20Malaysia&amp;output=embed" title="Map showing Kampar, Perak, Malaysia" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div></div>
            <div class="col-lg-5"><div class="card-body p-4">
                <h3 class="h6">Shop selection checklist</h3>
                <ul class="text-body-secondary mb-0">
                    <li class="mb-2">Compare rent, deposits, utilities, renovation and lease terms.</li>
                    <li class="mb-2">Check visibility, parking, walk-in access and public transport.</li>
                    <li class="mb-2">Research nearby repair shops and their advertised services.</li>
                    <li class="mb-2">Ask students, residents and local businesses about their support needs.</li>
                    <li>Confirm business registration and local council requirements before signing.</li>
                </ul>
                <p class="small text-body-secondary mt-3 mb-0">The map indicates the general Kampar area, not a confirmed shop lot or business address.</p>
            </div></div>
        </div>
    </div>

    {{-- Services table --}}
    <div class="card shadow-sm mb-4" id="services">
        <div class="card-header bg-body py-3"><h2 class="h5 mb-1">2. Service catalogue and estimated pricing</h2><p class="small text-body-secondary mb-0">Use clear starting prices; confirm final charges after inspection or scope discussion.</p></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered">
                <thead class="table-light"><tr><th scope="col">Service</th><th scope="col">Typical scope</th><th scope="col">Target customer</th><th scope="col" class="text-end">Estimated price</th></tr></thead>
                <tbody>
                    <tr><td class="fw-semibold">Desktop PC cleaning</td><td>Dust removal and basic inspection</td><td>Home users / gamers</td><td class="text-end text-nowrap">RM40–70</td></tr>
                    <tr><td class="fw-semibold">Laptop cleaning</td><td>Internal cleaning where serviceable</td><td>Students / office users</td><td class="text-end text-nowrap">RM60–100</td></tr>
                    <tr><td class="fw-semibold">Thermal paste replacement</td><td>Thermal maintenance and temperature check</td><td>Gaming / overheating devices</td><td class="text-end text-nowrap">RM80–150</td></tr>
                    <tr><td class="fw-semibold">Windows installation</td><td>OS setup and driver configuration</td><td>Students / home users</td><td class="text-end text-nowrap">RM60–120</td></tr>
                    <tr><td class="fw-semibold">Software setup</td><td>Install and configure customer-licensed software</td><td>Students / professionals</td><td class="text-end text-nowrap">RM20–50</td></tr>
                    <tr><td class="fw-semibold">Basic troubleshooting</td><td>Initial diagnosis and recommended next steps</td><td>All customers</td><td class="text-end text-nowrap">RM30–60</td></tr>
                    <tr><td class="fw-semibold">RAM / SSD installation</td><td>Labour; parts charged separately</td><td>Upgrade customers</td><td class="text-end text-nowrap">RM30–80</td></tr>
                    <tr><td class="fw-semibold">Full maintenance package</td><td>Cleaning, basic checks and maintenance summary</td><td>Home / office users</td><td class="text-end text-nowrap">RM120–180</td></tr>
                    <tr class="table-primary"><td class="fw-semibold">FYP consultation</td><td>Project scoping, milestone planning and technical direction</td><td>Diploma / degree students</td><td class="text-end text-nowrap">RM40–80 / hour</td></tr>
                    <tr class="table-primary"><td class="fw-semibold">FYP debugging session</td><td>Guided troubleshooting for code, wiring or setup</td><td>Software / embedded projects</td><td class="text-end text-nowrap">RM60–150 / session</td></tr>
                    <tr class="table-primary"><td class="fw-semibold">IoT / ESP32 mentoring</td><td>Sensor integration, connectivity and debugging guidance</td><td>IoT / electronics students</td><td class="text-end text-nowrap">RM60–120 / hour</td></tr>
                    <tr class="table-primary"><td class="fw-semibold">Project setup workshop</td><td>Development environment, Git and toolchain setup</td><td>Students / small teams</td><td class="text-end text-nowrap">RM50–100 / session</td></tr>
                    <tr><td class="fw-semibold">Student laptop care bundle</td><td>Basic check, cleaning assessment and software setup</td><td>College / university students</td><td class="text-end text-nowrap">RM79–129</td></tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-body-secondary">Prices are draft estimates for validation in Kampar. Parts, genuine software licences, data backup and complex jobs are quoted separately. For FYP services, provide coaching and troubleshooting rather than completing assessed work on a student's behalf.</div>
    </div>

    {{-- Student revenue --}}
    <div class="card shadow-sm mb-4" id="student-services">
        <div class="card-header bg-body py-3"><h2 class="h5 mb-1">3. Student and FYP revenue opportunities</h2><p class="small text-body-secondary mb-0">Create bookable offers that fit around repair work and use your technical skills to increase average revenue.</p></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0 table-bordered">
            <thead class="table-light"><tr><th>Offer</th><th>Deliverable</th><th>Suggested format</th><th class="text-end">Draft price</th><th>Business benefit</th></tr></thead>
            <tbody>
                <tr><td class="fw-semibold">FYP idea and scope consultation</td><td>Problem statement discussion, scope boundaries and milestone roadmap</td><td>45–60 minutes</td><td class="text-end text-nowrap">RM40–80</td><td>Low equipment cost; builds referrals</td></tr>
                <tr><td class="fw-semibold">Code / hardware debugging</td><td>Guided diagnosis, explanation and next-step checklist</td><td>60–90 minutes</td><td class="text-end text-nowrap">RM60–150</td><td>Higher-value technical service</td></tr>
                <tr><td class="fw-semibold">ESP32 / IoT coaching</td><td>Sensor, Wi-Fi, API or wiring troubleshooting guidance</td><td>Hourly booking</td><td class="text-end text-nowrap">RM60–120/hour</td><td>Specialist niche and repeat sessions</td></tr>
                <tr><td class="fw-semibold">Development environment setup</td><td>IDE, drivers, Git and dependency setup on the student's device</td><td>One-time session</td><td class="text-end text-nowrap">RM50–100</td><td>Can lead to laptop maintenance work</td></tr>
                <tr><td class="fw-semibold">FYP progress review</td><td>Review a student's plan, demo readiness and troubleshooting log</td><td>30–45 minutes</td><td class="text-end text-nowrap">RM30–60</td><td>Recurring, scheduled bookings</td></tr>
                <tr><td class="fw-semibold">Small-group tech clinic</td><td>Introductory Git, ESP32 or PC maintenance workshop</td><td>4–8 attendees</td><td class="text-end text-nowrap">RM25–50/person</td><td>Earn from one session with several students</td></tr>
            </tbody>
        </table></div>
        <div class="card-body"><div class="alert alert-info mb-0"><i class="fas fa-lightbulb me-2"></i><strong>Bundle idea:</strong> Offer a student laptop check plus software setup, or a three-session FYP mentoring pack paid upfront. State session length, inclusions, cancellation rules and any parts/software costs clearly.</div></div>
    </div>

    {{-- Customer workflow --}}
    <div class="card shadow-sm mb-4" id="operations">
        <div class="card-header bg-body py-3"><h2 class="h5 mb-0">4. Service workflow and job tracking</h2></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0 table-bordered">
            <thead class="table-light"><tr><th>Step</th><th>Job status</th><th>Required action</th><th>Record to keep</th></tr></thead>
            <tbody>
                <tr><td><span class="badge text-bg-secondary">01</span></td><td class="fw-semibold">Received</td><td>Record symptoms, make/model, accessories and visible condition.</td><td>Customer details and intake checklist</td></tr>
                <tr><td><span class="badge text-bg-info">02</span></td><td class="fw-semibold">Diagnosing</td><td>Inspect the device or discuss the student's project issue and constraints.</td><td>Diagnostic notes / consultation scope</td></tr>
                <tr><td><span class="badge text-bg-warning">03</span></td><td class="fw-semibold">Awaiting approval</td><td>Explain options, price and turnaround; obtain approval before extra work.</td><td>Written quotation and consent</td></tr>
                <tr><td><span class="badge text-bg-primary">04</span></td><td class="fw-semibold">In progress</td><td>Perform approved work or scheduled coaching; update the customer if scope changes.</td><td>Work log, parts and session notes</td></tr>
                <tr><td><span class="badge text-bg-success">05</span></td><td class="fw-semibold">Ready / completed</td><td>Test agreed outcomes, explain limitations and confirm payment or handover.</td><td>Test checklist, invoice and collection record</td></tr>
            </tbody>
        </table></div>
        <div class="card-footer small text-body-secondary">Protect customer data, obtain permission before backups or data access, and keep project mentoring within academic integrity rules.</div>
    </div>

    {{-- Startup and costs --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-6" id="startup-capital"><div class="card shadow-sm h-100">
            <div class="card-header bg-body py-3"><h2 class="h5 mb-0">5. Initial startup capital</h2></div>
            <div class="table-responsive"><table class="table table-striped align-middle mb-0 table-bordered"><thead class="table-light"><tr><th>Item</th><th class="text-end">Estimate</th></tr></thead><tbody>
                <tr><td>Rental deposit and advance</td><td class="text-end">RM4,200</td></tr><tr><td>Workbench, chairs and shelves</td><td class="text-end">RM1,500</td></tr><tr><td>Repair tools and cleaning equipment</td><td class="text-end">RM1,500</td></tr><tr><td>Thermal paste and consumables</td><td class="text-end">RM400</td></tr><tr><td>Test monitor, cables and adapters</td><td class="text-end">RM800</td></tr><tr><td>Signboard and shop setup</td><td class="text-end">RM1,000</td></tr><tr><td>Registration and licensing allowance</td><td class="text-end">RM800</td></tr><tr><td>Initial advertising</td><td class="text-end">RM300</td></tr><tr><td>Emergency cash reserve</td><td class="text-end">RM5,000</td></tr>
            </tbody><tfoot class="table-light"><tr class="fw-bold"><th>Total estimated capital</th><th class="text-end">RM15,500</th></tr></tfoot></table></div>
            <div class="card-body"><p class="small text-body-secondary mb-2">Suggested planning range</p><div class="d-flex justify-content-between border-bottom py-2"><span>Lean launch</span><strong>RM15,000–20,000</strong></div><div class="d-flex justify-content-between py-2"><span>Safer reserve</span><strong>RM20,000–25,000</strong></div><p class="small text-body-secondary mt-2 mb-0">Renovation, air-conditioning or additional equipment may increase the total. Keep personal living expenses separate.</p></div>
        </div></div>
        <div class="col-xl-6" id="monthly-costs"><div class="card shadow-sm h-100">
            <div class="card-header bg-body py-3"><h2 class="h5 mb-0">6. Monthly operating expenses</h2></div>
            <div class="table-responsive"><table class="table table-striped align-middle mb-0 table-bordered"><thead class="table-light"><tr><th>Expense</th><th class="text-end">Monthly estimate</th></tr></thead><tbody>
                <tr><td>Shop rental</td><td class="text-end">RM1,200</td></tr><tr><td>Electricity and water</td><td class="text-end">RM200</td></tr><tr><td>Internet and phone</td><td class="text-end">RM120</td></tr><tr><td>Marketing</td><td class="text-end">RM200</td></tr><tr><td>Miscellaneous expenses</td><td class="text-end">RM200</td></tr>
            </tbody><tfoot class="table-light"><tr class="fw-bold"><th>Total monthly fixed costs</th><th class="text-end">RM1,920</th></tr></tfoot></table></div>
            <div class="card-body"><p class="small text-body-secondary mb-0">Excludes replacement parts, tax, loan repayments, employee wages and the owner's personal income. Track variable costs separately for each job.</p></div>
        </div></div>
    </div>

    {{-- Profit scenarios --}}
    <div class="card shadow-sm mb-4" id="profit-model">
        <div class="card-header bg-body py-3"><h2 class="h5 mb-0">7. Contribution and monthly scenarios</h2></div>
        <div class="card-body"><div class="row g-3 mb-3">
            <div class="col-md-4"><div class="border rounded-3 p-3 h-100"><p class="small text-body-secondary mb-1">Average standard job bill</p><div class="h3 mb-0">RM90</div></div></div>
            <div class="col-md-4"><div class="border rounded-3 p-3 h-100"><p class="small text-body-secondary mb-1">Average consumables</p><div class="h3 mb-0">RM15</div></div></div>
            <div class="col-md-4"><div class="bg-primary-subtle rounded-3 p-3 h-100"><p class="small text-primary-emphasis mb-1">Contribution per job</p><div class="h3 text-primary mb-0">RM75</div></div></div>
        </div><p class="text-body-secondary mb-0">RM90 average bill − RM15 consumables = RM75 contribution. This amount helps cover fixed costs first; it is not take-home income. FYP consultations may have different costs and margins, so track them separately.</p></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0 table-bordered"><thead class="table-light"><tr><th>Standard jobs / day</th><th class="text-end">Monthly jobs</th><th class="text-end">Estimated monthly sales</th><th class="text-end">Estimated operating profit*</th></tr></thead><tbody>
            <tr><td>1</td><td class="text-end">26</td><td class="text-end">RM2,340</td><td class="text-end">RM30</td></tr><tr><td>2</td><td class="text-end">52</td><td class="text-end">RM4,680</td><td class="text-end">RM1,980</td></tr><tr><td>3</td><td class="text-end">78</td><td class="text-end">RM7,020</td><td class="text-end">RM3,930</td></tr><tr><td>4</td><td class="text-end">104</td><td class="text-end">RM9,360</td><td class="text-end">RM5,880</td></tr>
        </tbody></table></div>
        <div class="card-footer small text-body-secondary">*After RM15 estimated consumables per job and RM1,920 monthly fixed expenses, but before tax, loan repayments, wages and personal income. Assumes 26 working days.</div>
    </div>

    <div class="card shadow-sm mb-4" id="break-even"><div class="card-header bg-body py-3"><h2 class="h5 mb-0">8. Break-even targets</h2></div><div class="card-body"><div class="row g-4">
        <div class="col-md-6"><div class="border-start border-4 border-primary ps-3 h-100"><p class="small text-body-secondary text-uppercase fw-bold mb-2">Cover fixed operating costs</p><p class="h4">RM1,920 ÷ RM75 = 25.6 jobs</p><p class="mb-0">Target at least <strong>26 standard jobs per month</strong> under the current assumptions.</p></div></div>
        <div class="col-md-6"><div class="border-start border-4 border-success ps-3 h-100"><p class="small text-body-secondary text-uppercase fw-bold mb-2">Include RM2,500 owner income</p><p class="h4">RM4,420 ÷ RM75 = 58.9 jobs</p><p class="mb-0">Target about <strong>59 standard-job equivalents per month</strong>, or diversify with profitable consultations and workshops.</p></div></div>
    </div><p class="small text-body-secondary mt-3 mb-0">This is a simplified model. Calculate actual contribution for each service and avoid assuming all bookings have the same value.</p></div></div>

    {{-- Marketing --}}
    <div class="card shadow-sm mb-4" id="marketing">
        <div class="card-header bg-body py-3"><h2 class="h5 mb-1">9. Advertising and customer acquisition plan</h2><p class="small text-body-secondary mb-0">Focus on trackable, low-cost channels before increasing paid advertising.</p></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0 table-bordered"><thead class="table-light"><tr><th>Channel</th><th>Action</th><th>Offer to promote</th><th>How to measure</th><th>Priority</th></tr></thead><tbody>
            <tr><td class="fw-semibold">Google Business Profile</td><td>Publish accurate hours, service categories, photos and updates after location confirmation.</td><td>Local laptop repair and cleaning</td><td>Calls, directions and enquiries</td><td><span class="badge text-bg-success">High</span></td></tr>
            <tr><td class="fw-semibold">Campus / student groups</td><td>Ask group admins for permission; share helpful tips and a clear service menu.</td><td>Student laptop care bundle and FYP coaching</td><td>Enquiries using a campaign code</td><td><span class="badge text-bg-success">High</span></td></tr>
            <tr><td class="fw-semibold">Facebook / Instagram / TikTok</td><td>Post short repair explainers, before-and-after cleaning photos with consent, and booking details.</td><td>Maintenance bundles and tech clinics</td><td>Messages and paid bookings</td><td><span class="badge text-bg-success">High</span></td></tr>
            <tr><td class="fw-semibold">WhatsApp Business</td><td>Set up a service catalogue, business hours, quick replies and booking template.</td><td>Diagnostic booking and consultation slots</td><td>Bookings and response time</td><td><span class="badge text-bg-success">High</span></td></tr>
            <tr><td class="fw-semibold">Referral programme</td><td>Give existing customers a modest discount on a future labour service after a completed referral.</td><td>Cleaning, upgrades and student services</td><td>Referral bookings and discount cost</td><td><span class="badge text-bg-info">Medium</span></td></tr>
            <tr><td class="fw-semibold">Paid local ads</td><td>Test a small weekly budget, use one offer per campaign and stop ads that do not convert.</td><td>One clearly priced, high-demand service</td><td>Cost per enquiry and cost per paid job</td><td><span class="badge text-bg-info">Test first</span></td></tr>
            <tr><td class="fw-semibold">Small business outreach</td><td>Contact nearby offices and shops with a simple maintenance proposal.</td><td>Scheduled PC maintenance</td><td>Meetings, quotes and recurring contracts</td><td><span class="badge text-bg-info">Medium</span></td></tr>
        </tbody></table></div>
        <div class="card-body"><h3 class="h6">Example monthly marketing budget</h3><div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead class="table-light"><tr><th>Activity</th><th class="text-end">Budget</th><th>Purpose</th></tr></thead><tbody><tr><td>Social content and basic design</td><td class="text-end">RM0–50</td><td>Organic posts and service menu</td></tr><tr><td>Printed flyers / QR booking cards</td><td class="text-end">RM30–50</td><td>Permitted noticeboards and local partners</td></tr><tr><td>Small paid-ad test</td><td class="text-end">RM50–100</td><td>Test one offer and track conversions</td></tr><tr><td>Referral discounts reserve</td><td class="text-end">RM20–50</td><td>Reward completed referrals</td></tr></tbody><tfoot class="table-light"><tr class="fw-bold"><th>Total suggested test budget</th><th class="text-end">RM100–250</th><th>Adjust to cash flow</th></tr></tfoot></table></div><p class="small text-body-secondary mt-2 mb-0">Your existing RM200 monthly marketing estimate sits within this range. Do not spend the full amount automatically; increase only when paid jobs justify it.</p></div>
    </div>

    {{-- Roadmap --}}
    <div class="card shadow-sm mb-4" id="roadmap">
        <div class="card-header bg-body py-3"><h2 class="h5 mb-1">10. Launch roadmap and progress tracker</h2><p class="small text-body-secondary mb-0">Use this as a lightweight project plan; update each status as work is completed.</p></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0 table-bordered"><thead class="table-light"><tr><th>Phase</th><th>Task</th><th>Target timing</th><th>Completion evidence</th><th>Status</th></tr></thead><tbody>
            <tr><td><span class="badge text-bg-secondary">1 · Validate</span></td><td>Interview students, residents and small businesses; compare local competitors.</td><td>Week 1</td><td>Customer notes and competitor comparison</td><td><span class="badge text-bg-warning">Not started</span></td></tr>
            <tr><td><span class="badge text-bg-secondary">1 · Validate</span></td><td>Compare shop lots, rental terms and licensing requirements.</td><td>Week 1–2</td><td>At least three location quotations</td><td><span class="badge text-bg-warning">Not started</span></td></tr>
            <tr><td><span class="badge text-bg-info">2 · Prepare</span></td><td>Finalize service prices, intake form, quotation and job tracking sheet.</td><td>Week 2</td><td>Ready-to-use forms and service catalogue</td><td><span class="badge text-bg-warning">Not started</span></td></tr>
            <tr><td><span class="badge text-bg-info">2 · Prepare</span></td><td>Set up tools, payment method, business contact and online profiles.</td><td>Week 2–3</td><td>Equipment checklist and live business listings</td><td><span class="badge text-bg-warning">Not started</span></td></tr>
            <tr><td><span class="badge text-bg-primary">3 · Launch</span></td><td>Run a student laptop-care promotion and open FYP consultation bookings.</td><td>Week 3–4</td><td>Enquiries, paid bookings and customer feedback</td><td><span class="badge text-bg-warning">Not started</span></td></tr>
            <tr><td><span class="badge text-bg-primary">3 · Launch</span></td><td>Review revenue, contribution, ad results and customer acquisition cost.</td><td>Monthly</td><td>Monthly financial and marketing review</td><td><span class="badge text-bg-warning">Not started</span></td></tr>
        </tbody></table></div>
    </div>

    {{-- Risk and KPIs --}}
    <div class="row g-4 mb-4">
        <div class="col-xl-7"><div class="card shadow-sm h-100" id="risks"><div class="card-header bg-body py-3"><h2 class="h5 mb-0">11. Risks and practical controls</h2></div><div class="table-responsive"><table class="table table-striped align-middle mb-0 table-bordered"><thead class="table-light"><tr><th>Risk</th><th>Control</th></tr></thead><tbody>
            <tr><td>Low initial demand</td><td>Validate demand before leasing, stage purchases and review enquiries and paid jobs monthly.</td></tr><tr><td>Unexpected repair scope</td><td>Provide written quotations and obtain approval before extra labour or parts.</td></tr><tr><td>Customer data loss or privacy incident</td><td>Obtain consent, limit access, protect credentials and document handovers.</td></tr><tr><td>Parts delays</td><td>Confirm supplier price and availability before taking deposits or promising deadlines.</td></tr><tr><td>Low-margin advertising</td><td>Track cost per paid booking and pause campaigns that do not convert.</td></tr><tr><td>Academic integrity concerns</td><td>Offer explanations, coaching and debugging guidance; do not produce assessed submissions for students.</td></tr>
        </tbody></table></div></div></div>
        <div class="col-xl-5"><div class="card shadow-sm h-100" id="metrics"><div class="card-header bg-body py-3"><h2 class="h5 mb-0">12. Monthly owner dashboard</h2></div><div class="table-responsive"><table class="table table-hover align-middle mb-0 table-bordered"><thead class="table-light"><tr><th>Metric</th><th>What to check</th></tr></thead><tbody>
            <tr><td class="fw-semibold">Demand</td><td>Enquiries, bookings and lead source</td></tr><tr><td class="fw-semibold">Conversion</td><td>Enquiries that become approved, paid jobs</td></tr><tr><td class="fw-semibold">Average bill</td><td>Sales divided by completed jobs</td></tr><tr><td class="fw-semibold">Contribution</td><td>Sales less parts and consumables</td></tr><tr><td class="fw-semibold">Advertising</td><td>Spend and cost per paid booking</td></tr><tr><td class="fw-semibold">Repeat business</td><td>Returning customers and referrals</td></tr><tr><td class="fw-semibold">Delivery</td><td>Turnaround time, rework and feedback</td></tr>
        </tbody></table></div></div></div>
    </div>

    <div class="card border-0 bg-primary text-white shadow-sm mb-4 p-2" id="recommendation"><div class="card-body p-4 p-lg-5"><p class="small text-uppercase fw-bold mb-2 opacity-75">Final recommendation</p><h2 class="h3 text-white mb-3">Build a repair business with a specialist student-tech offer.</h2><p class="mb-3">Start with essential repair services, add bookable FYP consultation and IoT mentoring, and promote packages to students and local customers. Keep parts inventory low, get approval before work, and track the profit of each service.</p><div class="row g-3"><div class="col-md-4"><div class="rounded-3 bg-white bg-opacity-10 p-3 h-100"><div class="fw-bold mb-1">Validate first</div><p class="small mb-0">Confirm local demand, rent and regulations.</p></div></div><div class="col-md-4"><div class="rounded-3 bg-white bg-opacity-10 p-3 h-100"><div class="fw-bold mb-1">Sell packages</div><p class="small mb-0">Bundle maintenance and scheduled technical coaching.</p></div></div><div class="col-md-4"><div class="rounded-3 bg-white bg-opacity-10 p-3 h-100"><div class="fw-bold mb-1">Measure results</div><p class="small mb-0">Scale only services and ads that produce positive contribution.</p></div></div></div></div></div>
</div>
@endsection
