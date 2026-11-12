@extends('layouts.app')

@section('title', 'Self-test checklist')

@section('content')
<div class="container-fluid py-3" id="self-test-checklist" data-user-id="{{ auth()->id() }}">
    <x-page-header eyebrow="Admin / QA" title="Website self-test checklist"
        description="Walk through the site as staff and as a customer. Start with Shop, Projects, and PC builds. Check each item after verifying the expected result." />

    <div class="alert alert-warning">
        <strong>Use test data.</strong> Use a dedicated test customer, test-mode CHiP credentials, and test products/projects. Do not enter real card details or send real payments while testing. CHiP QR appears only if enabled for the merchant account.
    </div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between mb-1">
                    <strong>Checklist progress</strong>
                    <span id="self-test-progress-label" class="text-body-secondary">0 of 0 complete</span>
                </div>
                <div class="progress" role="progressbar" aria-label="Self-test progress" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" id="self-test-progress" style="width: 0%"></div>
                </div>
                <div class="form-text">Your checkmarks are saved in this browser on this device only.</div>
                <div class="alert alert-warning d-none mt-2 mb-0" id="self-test-storage-warning" role="alert">
                    This browser could not save your checkmarks. They will be cleared when you leave this page.
                </div>
            </div>
            <button type="button" class="btn btn-outline-secondary" id="self-test-reset">Clear my checkmarks</button>
        </div>
    </div>

    <section class="card mb-4" aria-labelledby="shop-tests-title">
        <div class="card-header">
            <span class="badge text-bg-danger me-2">Priority 1</span>
            <h2 class="h5 d-inline" id="shop-tests-title">Shop — browse, order, and fulfil</h2>
        </div>
        <div class="card-body">
            <p>Prepare one in-stock test product and one customer test account. Use a low-value test product, not real sellable stock.</p>
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="shop-guest">
                    <span><strong>Online / guest browsing:</strong> Open the public shop in a signed-out window. Verify in-stock products and details are visible, unavailable/out-of-stock products cannot be purchased, and cart/management actions require sign-in.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="shop-customer">
                    <span><strong>Online / customer account:</strong> Sign in as a customer, add a product, change quantity, remove it, then add it again. Verify cart count and totals update and another customer cannot view this account’s order.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="shop-checkout">
                    <span><strong>Online / checkout:</strong> Submit a test order with valid contact and delivery details. Verify order confirmation, correct amount, order history, and that stock is reserved/deducted according to the current checkout flow.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="shop-chip">
                    <span><strong>Online / CHiP:</strong> In test mode, complete one successful checkout and one failed/cancelled checkout. Verify only a provider-confirmed payment marks an order paid and repeated callbacks do not duplicate payment or status changes. Never test with live funds.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="shop-fulfilment">
                    <span><strong>Staff / fulfilment:</strong> Update the test order through its allowed statuses, add a tracking number, and check the customer order page and tracking view. Verify customers cannot change staff-only order status.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="shop-face-to-face">
                    <span><strong>Face-to-face / counter sale:</strong> Create a quotation for the agreed products, record the customer’s in-person acceptance, convert to an invoice, record full payment using the actual method, and verify stock movement and payment history. Open the generated receipt PDF and print or save it for the customer.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="booking-tests-title">
        <div class="card-header">
            <h2 class="h5 d-inline" id="booking-tests-title">Bookings — customer request to staff follow-up</h2>
        </div>
        <div class="card-body">
            <p>Use a test customer and a test time slot. Check both the public booking path and staff management flow.</p>
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="booking-public">
                    <span><strong>Online / public booking:</strong> As a guest, view available events and times, submit a booking with valid contact details, and verify the confirmation reference and booking details. Check unavailable or full slots cannot be selected.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="booking-account">
                    <span><strong>Customer account:</strong> Sign in as a customer and create a booking. Verify it appears in the customer’s booking list and a different customer cannot view it.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="booking-staff">
                    <span><strong>Staff / schedule:</strong> Find the test booking in the staff list/calendar, update its status or details, and verify the change is reflected without creating a duplicate booking.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="booking-validation">
                    <span><strong>Validation / notifications:</strong> Try missing or invalid required details and verify a clear error with no booking created. Verify the expected customer/staff notification and correct time zone.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="ticket-tests-title">
        <div class="card-header">
            <h2 class="h5 d-inline" id="ticket-tests-title">Repair tickets — intake, updates, and customer view</h2>
        </div>
        <div class="card-body">
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="ticket-intake">
                    <span><strong>Staff / intake:</strong> Create a repair ticket for a test customer with device, issue description, priority, and received date. Verify the ticket number and customer association are correct.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="ticket-updates">
                    <span><strong>Staff / progress:</strong> Add a ticket status update and internal note. Verify attachments can be opened by authorized staff and internal notes are not exposed to customers.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="ticket-customer">
                    <span><strong>Customer account:</strong> As the linked customer, review the ticket and customer-visible updates. Verify an unrelated account cannot view the ticket or its attachments.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="ticket-billing">
                    <span><strong>Ticket billing:</strong> Create a quotation for the repair, record acceptance, convert to invoice, and verify the invoice remains linked to the correct ticket and customer.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="billing-tests-title">
        <div class="card-header">
            <h2 class="h5 d-inline" id="billing-tests-title">Billing — quotations, invoices, payments, and receipts</h2>
        </div>
        <div class="card-body">
            <p>Test a standalone service or sale quotation as well as the project- and repair-linked billing flows below.</p>
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="billing-quote">
                    <span><strong>Quotation:</strong> Create a quotation with several line items, quantities, tax, customer details, and notes. Verify totals, PDF, and that editing a draft does not issue an invoice or deduct stock.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="billing-acceptance">
                    <span><strong>Acceptance / invoice:</strong> Test customer acceptance online and staff-recorded acceptance outside the website. Verify an invoice can only be issued from an accepted quotation and its number, items, and totals are correct.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="billing-payment">
                    <span><strong>Payment:</strong> Test full manual payment and CHiP test-mode payment. Verify failed/pending CHiP attempts do not mark the invoice paid, confirmed payment is recorded once, and partial payment is rejected.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="billing-receipt">
                    <span><strong>Receipt:</strong> Print/save the receipt PDF and check its number, customer, invoice, payment method, reference, date, and amount. Verify it is available only after payment is recorded and only to the invoice customer and staff.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="billing-reports">
                    <span><strong>Billing records:</strong> Filter quotations and invoices by type/status, verify paid and outstanding totals, and confirm voided or unrelated customer documents cannot be accessed through direct URLs.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="e-invoice-tests-title">
        <div class="card-header">
            <span class="badge text-bg-danger me-2">Not implemented</span>
            <h2 class="h5 d-inline" id="e-invoice-tests-title">E-Invoice — MyInvois submission</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                The website currently creates ordinary invoice PDFs; it does not submit e-Invoices to MyInvois/LHDN, receive validation results, or store an official submission ID. A PDF invoice or payment receipt is not evidence that an e-Invoice was submitted.
            </div>
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="einvoice-blocked" disabled>
                    <span><strong>Submission test — blocked:</strong> Do not mark this complete until a MyInvois integration is implemented. After implementation, test sandbox submission, rejection/error handling, retry without duplicate submissions, and confirmation that the official status and UUID are saved against the correct invoice.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="einvoice-readiness">
                    <span><strong>Business data readiness:</strong> Review company registration and tax details in Company Profile and ensure test customer records have the required identity and address information. This is data preparation only, not e-Invoice submission or compliance verification.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="project-tests-title">
        <div class="card-header">
            <span class="badge text-bg-warning me-2">Priority 2</span>
            <h2 class="h5 d-inline" id="project-tests-title">Projects — scope, approval, and payment</h2>
        </div>
        <div class="card-body">
            <p>Use a test project and a test customer account linked to it. Do not use a real customer for these checks.</p>
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="project-setup">
                    <span><strong>Staff / setup:</strong> Create a project, link a customer, and verify the project shows the correct customer and project billing section to staff.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="project-online-quote">
                    <span><strong>Online / quotation:</strong> Create a quotation with specific deliverables, revision limit, target date, and full-payment-before-work terms. Send by email or add it to the customer panel; verify the customer can view the quotation and PDF.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="project-online-accept">
                    <span><strong>Online / acceptance:</strong> As the customer, accept a sent quotation. Verify a draft/sent quotation cannot be converted, but an accepted quotation can. Convert it and verify an invoice is issued and visible to the linked customer.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="project-face-to-face">
                    <span><strong>Face-to-face / acceptance:</strong> Create a quotation and do not send it. Record the customer’s in-person acceptance from the quotation page, convert to invoice, and verify the quote-first process succeeds without website delivery.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="project-payment">
                    <span><strong>Invoice / full payment:</strong> Verify the customer can pay the full outstanding invoice through test-mode CHiP, or staff can record a full cash/bank/card payment. Confirm partial payment is rejected and the paid balance/status updates correctly.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="project-access">
                    <span><strong>Privacy / access:</strong> Sign in as an unrelated customer. Verify they cannot view this project, quotation, invoice, or PDF. Check that project terms and prices remain correct in the customer view and PDF.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="receipt-tests-title">
        <div class="card-header">
            <span class="badge text-bg-info me-2">Billing &amp; payments</span>
            <h2 class="h5 d-inline" id="receipt-tests-title">Payment receipts</h2>
        </div>
        <div class="card-body">
            <p>Receipts are created from recorded invoice payments. They are available from the invoice payment history and the Payment receipts page; receipt PDFs can be printed or saved.</p>
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="receipt-manual">
                    <span><strong>Manual payment:</strong> Record a full payment on a test invoice. Verify a uniquely numbered receipt appears in payment history and on the Payment receipts page, and the PDF shows the correct customer, invoice, date, method, reference, and amount.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="receipt-chip">
                    <span><strong>CHiP payment:</strong> Complete a test-mode payment and verify its receipt is available after CHiP confirms payment. A pending, failed, or cancelled attempt must not produce a receipt.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="receipt-access">
                    <span><strong>Customer access:</strong> Verify the correct customer can view and print their receipt, while a different customer cannot open its PDF or see it in their receipt list.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="pc-tests-title">
        <div class="card-header">
            <span class="badge text-bg-warning me-2">Priority 3</span>
            <h2 class="h5 d-inline" id="pc-tests-title">Custom PC builds — catalogue to invoice</h2>
        </div>
        <div class="card-body">
            <p>Use a catalogue-only component and, separately, a real stocked inventory item to verify these remain distinct.</p>
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="pc-catalogue">
                    <span><strong>Staff / parts catalogue:</strong> Open Manage builder parts, add a catalogue part with specification and RM price, edit it, and delete an unused test part. Verify none of these actions creates inventory stock.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="pc-supplier">
                    <span><strong>Staff / suppliers:</strong> Link a supplier with Shopee shop and product URLs and pricing. Search by category, name/SKU, price, and supplier. Verify supplier details appear on the correct catalogue part.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="pc-compatible">
                    <span><strong>Builder / compatibility:</strong> Create a build with compatible CPU and motherboard sockets and verify it is accepted. Try mismatched sockets and verify the quotation is blocked with a useful validation error.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="pc-multiple">
                    <span><strong>Builder / multiple PCs:</strong> Add two builds in one quotation, select different parts, and verify each PC’s compatibility is checked independently and quantities/prices/tax totals are correct.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="pc-online">
                    <span><strong>Online / customer approval:</strong> Send the PC quotation to a test customer, accept it as that customer, convert to invoice, and pay the full amount in CHiP test mode. Verify the customer can see the documents and payment status.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="pc-face-to-face">
                    <span><strong>Face-to-face / counter build:</strong> Prepare the build with the customer, record acceptance without sending the quote online, issue the invoice, record full payment, and verify inventory changes only for line items linked to actual stock—not catalogue-only components.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="pc-history">
                    <span><strong>History / safety:</strong> Verify a catalogue part already used on an invoice cannot be deleted, existing quotations retain their line descriptions and prices, and a failed invoice conversion leaves stock and the quotation unchanged.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="task-tests-title">
        <div class="card-header">
            <h2 class="h5 d-inline" id="task-tests-title">Tasks — assignment, status, and access</h2>
        </div>
        <div class="card-body">
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="task-create">
                    <span><strong>Create / assign:</strong> Create a task, link it to a test project if applicable, assign it to a staff member, and verify it appears for the right assignee and in the project.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="task-update">
                    <span><strong>Progress / completion:</strong> Update task status, due date, and completion details. Verify filters and dashboard counts update and the task does not disappear unexpectedly.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="task-access">
                    <span><strong>Permissions:</strong> Test as an assigned staff member, an unassigned staff member, and a customer. Verify each can only view or change tasks allowed by their role and project access.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="email-tests-title">
        <div class="card-header">
            <h2 class="h5 d-inline" id="email-tests-title">Email — inbox, sending, and settings</h2>
        </div>
        <div class="card-body">
            <p>Use a test mailbox and recipient. Avoid sending test content to real customers.</p>
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="email-inbox">
                    <span><strong>Inbox:</strong> Verify mailbox connection, message list, open message, read/unread, archive, and delete behavior. Confirm polling does not create duplicate messages.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="email-send">
                    <span><strong>Compose / send:</strong> Send a message to a controlled test address with a safe attachment. Verify sender, recipient, subject, body, and attachment, and confirm validation catches invalid addresses or missing required fields.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="email-admin">
                    <span><strong>Admin settings / access:</strong> Test SMTP/IMAP connection checks and confirm only authorized admins can change mailbox settings. Check errors are clear without exposing credentials.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="customer-tests-title">
        <div class="card-header">
            <h2 class="h5 d-inline" id="customer-tests-title">Customer records and accounts</h2>
        </div>
        <div class="card-body">
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="customer-record">
                    <span><strong>Staff / customer record:</strong> Create and edit a test customer with name, email, phone, and address. Verify search/list/detail pages show the current values and duplicate or invalid input is handled clearly.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="customer-link">
                    <span><strong>Linkage:</strong> Link the test customer to a project, repair ticket, quotation, and invoice where applicable. Verify each record points to the right customer and updates do not silently link another person’s account.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="customer-account">
                    <span><strong>Customer account:</strong> Create/invite a test customer account, complete sign-in and password/reset flows, and verify the account can see only its own projects, tickets, bookings, billing documents, receipts, and orders.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="customer-permissions">
                    <span><strong>Account safety:</strong> Check staff/customer role boundaries, account activation or deactivation, profile updates, and direct URL access as an unrelated customer. Confirm no private record or attachment is exposed.</span>
                </label>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="release-tests-title">
        <div class="card-header">
            <span class="badge text-bg-secondary me-2">Final</span>
            <h2 class="h5 d-inline" id="release-tests-title">Before using this with real customers</h2>
        </div>
        <div class="card-body">
            <div class="list-group list-group-flush">
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="final-mobile">
                    <span><strong>Mobile and browser:</strong> Repeat the most important customer steps on a phone-sized screen and a second browser. Check readable totals, clear errors, and no broken redirects.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="final-email">
                    <span><strong>Messages and documents:</strong> Check quotation email, WhatsApp draft, invoice PDF, links, customer names, item descriptions, due dates, and totals. Ensure test messages were not sent to real customers.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="final-known-gaps">
                    <span><strong>Known current limits:</strong> The app requires full invoice payment (no deposits/partial payments). Payment receipts are available as PDFs, but are not automatically emailed to customers; open and print/save the receipt to provide it.</span>
                </label>
                <label class="list-group-item d-flex gap-3">
                    <input class="form-check-input self-test-check" type="checkbox" data-test-id="final-cleanup">
                    <span><strong>Clean up:</strong> Remove/cancel test orders and test data where appropriate, restore test stock, confirm CHiP is in the intended mode, and make sure no test records remain in customer-facing views.</span>
                </label>
            </div>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const checklist = document.getElementById('self-test-checklist');
        const checks = [...checklist.querySelectorAll('.self-test-check')];
        const storageKey = `omgcomputer-self-test-v1-${checklist.dataset.userId}`;
        const progress = document.getElementById('self-test-progress');
        const progressLabel = document.getElementById('self-test-progress-label');
        const storageWarning = document.getElementById('self-test-storage-warning');
        const showStorageWarning = () => storageWarning.classList.remove('d-none');

        const updateProgress = () => {
            const completed = checks.filter((check) => check.checked).length;
            const percent = checks.length ? Math.round((completed / checks.length) * 100) : 0;
            progress.style.width = `${percent}%`;
            progress.setAttribute('aria-valuenow', String(percent));
            progressLabel.textContent = `${completed} of ${checks.length} complete`;
        };

        try {
            const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
            checks.forEach((check) => { check.checked = saved[check.dataset.testId] === true; });
        } catch (error) {
            showStorageWarning();
        }

        checks.forEach((check) => {
            check.addEventListener('change', () => {
                try {
                    const state = Object.fromEntries(checks.map((item) => [item.dataset.testId, item.checked]));
                    localStorage.setItem(storageKey, JSON.stringify(state));
                } catch (error) {
                    showStorageWarning();
                }
                updateProgress();
            });
        });

        document.getElementById('self-test-reset').addEventListener('click', () => {
            if (!window.confirm('Clear all self-test checkmarks saved in this browser?')) return;
            checks.forEach((check) => { check.checked = false; });
            try {
                localStorage.removeItem(storageKey);
            } catch (error) {
                showStorageWarning();
            }
            updateProgress();
        });
        updateProgress();
    })();
</script>
@endpush
