@extends('layouts.app')

@section('title', 'Email Inbox')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Email" title="Inbox" description="Your cPanel mailbox, refreshed automatically while this page is open.">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('email.compose') }}">
                <span class="fas fa-pen me-2" aria-hidden="true"></span>Compose
            </a>
            <a class="btn btn-outline-primary" href="{{ route('email.mailbox-settings') }}">
                <span class="fas fa-cog me-2" aria-hidden="true"></span>My mailbox
            </a>
            @role('admin')
                <a class="btn btn-outline-secondary" href="{{ route('email.settings') }}">Server settings</a>
            @endrole
        </x-slot:actions>
    </x-page-header>

    @if (session('mailbox_success'))
        <div class="alert alert-success" role="status">{{ session('mailbox_success') }}</div>
    @endif

    <div class="card email-inbox" data-poll-url="{{ route('email.poll') }}"
        data-action-url="{{ url('/email') }}" data-bulk-url="{{ route('email.bulk-action') }}"
        data-csrf="{{ csrf_token() }}" data-mailbox-email="{{ auth()->user()->emailMailbox?->email }}">
        <div class="card-header">
            <div class="row align-items-center g-2">
                <div class="col d-flex align-items-center flex-wrap">
                    <div class="form-check mb-0 d-none d-sm-block">
                        <input class="form-check-input" id="select-all-emails" type="checkbox" aria-label="Select all visible emails">
                    </div>
                    <button class="btn btn-falcon-default btn-sm ms-sm-1" id="refresh-emails" type="button" aria-label="Refresh inbox" title="Refresh inbox">
                        <span class="fas fa-redo" aria-hidden="true"></span>
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-falcon-default text-600 btn-sm dropdown-toggle dropdown-caret-none ms-2"
                            type="button" id="email-filter" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                            aria-label="Filter emails" title="Filter emails">
                            <span class="fas fa-sliders-h" aria-hidden="true"></span>
                            <span class="ms-1 d-none d-md-inline" id="email-filter-label">All</span>
                        </button>
                        <div class="dropdown-menu border py-0" aria-labelledby="email-filter">
                            <div class="bg-body-quaternary py-2">
                                <button class="dropdown-item email-filter-option" type="button" data-filter="all">All</button>
                                <button class="dropdown-item email-filter-option" type="button" data-filter="unread">Unread</button>
                                <button class="dropdown-item email-filter-option" type="button" data-filter="read">Read</button>
                                <button class="dropdown-item email-filter-option" type="button" data-filter="to-me">To me</button>
                                <button class="dropdown-item email-filter-option" type="button" data-filter="flagged">Flagged</button>
                            </div>
                        </div>
                    </div>
                    <div class="border-start ms-3 ps-3 d-none" id="email-bulk-actions">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Actions for selected emails">
                            <button class="btn btn-falcon-default" type="button" data-bulk-action="archive" aria-label="Archive selected" title="Archive selected">
                                <span class="fas fa-archive" aria-hidden="true"></span>
                            </button>
                            <button class="btn btn-falcon-default" type="button" data-bulk-action="delete" aria-label="Delete selected" title="Delete selected">
                                <span class="fas fa-trash-alt" aria-hidden="true"></span>
                            </button>
                            <button class="btn btn-falcon-default" type="button" data-bulk-action="read" aria-label="Mark selected as read" title="Mark selected as read">
                                <span class="fas fa-envelope-open" aria-hidden="true"></span>
                            </button>
                            <button class="btn btn-falcon-default" type="button" data-bulk-action="unread" aria-label="Mark selected as unread" title="Mark selected as unread">
                                <span class="fas fa-envelope" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-auto d-flex align-items-center gap-2">
                    <span class="small text-body-secondary" id="email-refresh-status" role="status">Checking inbox…</span>
                    <span class="small text-body-secondary d-none d-md-inline" id="email-count"></span>
                    <a class="btn btn-falcon-primary btn-sm" href="{{ route('email.mailbox-settings') }}">
                        <span class="fas fa-envelope me-1" aria-hidden="true"></span>Mailbox
                    </a>
                </div>
            </div>
        </div>

        @if ($error)
            <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3 m-3 mb-0" id="email-inbox-error" role="alert">
                <span>{{ $error }}</span>
                <a class="btn btn-sm btn-outline-primary flex-shrink-0" href="{{ route('email.mailbox-settings') }}">Configure mailbox</a>
            </div>
        @else
            <div class="alert alert-warning d-none m-3 mb-0" id="email-inbox-error" role="alert"></div>
        @endif

        <div class="card-body fs-10 border-top p-0" id="emails" aria-live="polite">
            <section id="unread-emails">
                <h2 class="h6 fs-9 px-3 pt-3 pb-2 mb-0 border-bottom">Unread</h2>
                @foreach ($messages as $message)
                    @unless ($message['seen'])
                        @include('email._message-row', ['message' => $message])
                    @endunless
                @endforeach
            </section>
            <section id="read-emails">
                <h2 class="h6 fs-9 px-3 pt-3 pb-2 mb-0 border-bottom">Everything else</h2>
                @foreach ($messages as $message)
                    @if ($message['seen'])
                        @include('email._message-row', ['message' => $message])
                    @endif
                @endforeach
            </section>
            @if ($messages === [])
                <div class="d-flex flex-column align-items-center text-center text-body-secondary py-5" id="empty-emails">
                    <span class="fas fa-inbox fs-2 d-block mx-auto mb-2" aria-hidden="true"></span>
                    No messages found in this mailbox.
                </div>
            @else
                <div class="d-flex flex-column align-items-center text-center text-body-secondary py-5 d-none" id="empty-emails">
                    <span class="fas fa-inbox fs-2 d-block mx-auto mb-2" aria-hidden="true"></span>
                    No messages match this filter.
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .email-inbox .email-row { position: relative; }
    .email-inbox .email-row:hover { background-color: var(--falcon-body-bg, #fff); }
    .email-inbox .email-row-unread { background-color: var(--falcon-body-bg, #fff); }
    .email-inbox .email-row-read { background-color: var(--falcon-tertiary-bg, #f8f9fa); }
    .email-inbox .email-row-actions { opacity: 0; transition: opacity .15s ease-in-out; }
    .email-inbox .email-row:hover .email-row-actions,
    .email-inbox .email-row:focus-within .email-row-actions { opacity: 1; }
    .email-inbox .email-sender { width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .email-inbox .email-subject { overflow-wrap: anywhere; }
    @media (hover: none) { .email-inbox .email-row-actions { opacity: 1; } }
</style>

<script>
    (() => {
        const inbox = document.querySelector('.email-inbox[data-poll-url]');
        if (!inbox) return;

        const rows = document.getElementById('emails');
        const unreadSection = document.getElementById('unread-emails');
        const readSection = document.getElementById('read-emails');
        const refreshButton = document.getElementById('refresh-emails');
        const refreshStatus = document.getElementById('email-refresh-status');
        const errorAlert = document.getElementById('email-inbox-error');
        const emptyState = document.getElementById('empty-emails');
        const bulkActions = document.getElementById('email-bulk-actions');
        const countLabel = document.getElementById('email-count');
        const selectAll = document.getElementById('select-all-emails');
        const mailboxEmail = (inbox.dataset.mailboxEmail || '').toLowerCase();
        let currentFilter = 'all';
        let currentFilterLabel = 'All';
        let refreshing = false;

        const textNode = (tag, className, text) => {
            const node = document.createElement(tag);
            if (className) node.className = className;
            node.textContent = text || '';
            return node;
        };

        const dateLabel = (value) => {
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value || '';
            return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(date);
        };

        const makeForm = (uid, action, label, icon, extraClass = '') => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `${inbox.dataset.actionUrl.replace(/\/$/, '')}/${uid}/actions/${action}`;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = inbox.dataset.csrf;
            form.append(csrf);
            const button = document.createElement('button');
            button.type = 'submit';
            button.className = `btn btn-tertiary hover-bg-200 ${extraClass}`.trim();
            button.setAttribute('aria-label', label);
            button.title = label;
            button.innerHTML = `<span class="fas ${icon}" aria-hidden="true"></span>`;
            form.append(button);
            return form;
        };

        const createRow = (message, selectedIds) => {
            const row = document.createElement('div');
            row.className = `row border-bottom border-200 hover-actions-trigger hover-shadow py-2 px-1 mx-0 email-row ${message.seen ? 'email-row-read' : 'email-row-unread'}`;
            row.dataset.emailRow = '';
            row.dataset.uid = message.uid;
            row.dataset.seen = message.seen ? '1' : '0';
            row.dataset.flagged = message.flagged ? '1' : '0';
            row.dataset.to = (message.to || '').toLowerCase();

            const actions = document.createElement('div');
            actions.className = 'btn-group btn-group-sm z-2 hover-actions end-0 me-3 email-row-actions';
            actions.append(makeForm(message.uid, 'archive', 'Archive', 'fa-archive'));
            actions.append(makeForm(message.uid, 'delete', 'Delete', 'fa-trash-alt'));
            actions.append(makeForm(message.uid, message.seen ? 'unread' : 'read', message.seen ? 'Mark as unread' : 'Mark as read', message.seen ? 'fa-envelope' : 'fa-envelope-open'));
            row.append(actions);

            const selectCol = document.createElement('div');
            selectCol.className = 'col-auto d-none d-sm-block';
            const selectWrap = document.createElement('div');
            selectWrap.className = 'd-flex';
            const checkWrap = document.createElement('div');
            checkWrap.className = 'form-check mb-0 fs-9';
            const check = document.createElement('input');
            check.type = 'checkbox';
            check.className = 'form-check-input email-select';
            check.value = String(message.uid);
            check.setAttribute('aria-label', `Select email: ${message.subject}`);
            check.checked = selectedIds.has(String(message.uid));
            checkWrap.append(check);
            selectWrap.append(checkWrap);
            const flagForm = makeForm(message.uid, message.flagged ? 'unflag' : 'flag', message.flagged ? 'Remove flag' : 'Flag email', message.flagged ? 'fa-star text-warning' : 'fa-star text-300');
            flagForm.classList.add('ms-1');
            selectWrap.append(flagForm);
            selectCol.append(selectWrap);
            row.append(selectCol);

            const contentCol = document.createElement('div');
            contentCol.className = 'col col-md-9 col-xxl-10';
            const contentRow = document.createElement('div');
            contentRow.className = 'row';
            const senderCol = document.createElement('div');
            senderCol.className = 'col-md-4 col-xl-3 col-xxl-2 ps-md-0 mb-1 mb-md-0';
            const senderWrap = document.createElement('div');
            senderWrap.className = 'd-flex position-relative align-items-center';
            const avatar = textNode('span', 'avatar avatar-s bg-body-secondary text-primary fw-bold d-inline-flex align-items-center justify-content-center', (message.from || '?').trim().charAt(0).toUpperCase());
            avatar.setAttribute('aria-hidden', 'true');
            senderWrap.append(avatar);
            const sender = textNode('a', `flex-1 ms-2 email-sender inbox-link${message.seen ? '' : ' fw-bold'}`, message.from || 'Unknown sender');
            sender.href = `${inbox.dataset.actionUrl.replace(/\/$/, '')}/${message.uid}`;
            sender.classList.add('stretched-link');
            senderWrap.append(sender);
            senderCol.append(senderWrap);
            contentRow.append(senderCol);

            const subjectCol = document.createElement('div');
            const subjectLink = document.createElement('a');
            subjectLink.className = `d-block inbox-link email-subject${message.seen ? '' : ' fw-bold'}`;
            subjectLink.href = `${inbox.dataset.actionUrl.replace(/\/$/, '')}/${message.uid}`;
            subjectLink.append(textNode('span', '', message.subject || '(No subject)'));
            subjectCol.append(subjectLink);
            contentRow.append(subjectCol);
            contentCol.append(contentRow);
            row.append(contentCol);

            const dateCol = document.createElement('div');
            dateCol.className = `col-auto ms-auto d-flex flex-column justify-content-between${message.seen ? ' text-body-secondary' : ' fw-bold'}`;
            dateCol.append(textNode('time', 'small', dateLabel(message.date)));
            row.append(dateCol);
            return row;
        };

        const applyFilter = () => {
            const matching = [...rows.querySelectorAll('[data-email-row]')].filter((row) => {
                if (currentFilter === 'unread') return row.dataset.seen === '0';
                if (currentFilter === 'read') return row.dataset.seen === '1';
                if (currentFilter === 'flagged') return row.dataset.flagged === '1';
                if (currentFilter === 'to-me') return mailboxEmail !== '' && row.dataset.to.includes(mailboxEmail);
                return true;
            });
            [...unreadSection.children, ...readSection.children].forEach((row) => {
                if (row.matches?.('[data-email-row]')) row.classList.toggle('d-none', !matching.includes(row));
            });
            unreadSection.classList.toggle('d-none', currentFilter === 'read' || ![...unreadSection.querySelectorAll('[data-email-row]')].some((row) => !row.classList.contains('d-none')));
            readSection.classList.toggle('d-none', currentFilter === 'unread' || ![...readSection.querySelectorAll('[data-email-row]')].some((row) => !row.classList.contains('d-none')));
            emptyState.classList.toggle('d-none', matching.length > 0);
        };

        const updateSelection = () => {
            const selected = [...rows.querySelectorAll('.email-select:checked')];
            const visibleInputs = [...rows.querySelectorAll('.email-select')].filter((input) => !input.closest('[data-email-row]')?.classList.contains('d-none'));
            const visibleSelected = visibleInputs.filter((input) => input.checked);
            bulkActions.classList.toggle('d-none', selected.length === 0);
            selectAll.checked = visibleInputs.length > 0 && visibleSelected.length === visibleInputs.length;
            selectAll.indeterminate = visibleSelected.length > 0 && !selectAll.checked;
        };

        const refreshInbox = async () => {
            if (refreshing || document.hidden) return;
            refreshing = true;
            refreshButton.disabled = true;
            refreshButton.querySelector('.fas')?.classList.add('fa-spin');
            refreshStatus.textContent = 'Checking inbox…';
            try {
                const response = await fetch(inbox.dataset.pollUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload.message || 'The inbox could not be refreshed.');
                const selectedIds = new Set([...rows.querySelectorAll('.email-select:checked')].map((input) => input.value));
                unreadSection.querySelectorAll('[data-email-row]').forEach((row) => row.remove());
                readSection.querySelectorAll('[data-email-row]').forEach((row) => row.remove());
                const unreadHeading = unreadSection.querySelector('h2');
                const readHeading = readSection.querySelector('h2');
                (payload.messages || []).forEach((message) => {
                    (message.seen ? readSection : unreadSection).append(createRow(message, selectedIds));
                });
                unreadSection.prepend(unreadHeading);
                readSection.prepend(readHeading);
                countLabel.textContent = `${payload.messages.length} message${payload.messages.length === 1 ? '' : 's'}`;
                errorAlert.classList.add('d-none');
                refreshStatus.textContent = `Updated ${new Date(payload.checked_at).toLocaleTimeString()}`;
                applyFilter();
                updateSelection();
            } catch (error) {
                errorAlert.textContent = error instanceof Error ? error.message : 'The inbox could not be refreshed.';
                errorAlert.classList.remove('d-none');
                refreshStatus.textContent = 'Refresh failed';
            } finally {
                refreshing = false;
                refreshButton.disabled = false;
                refreshButton.querySelector('.fas')?.classList.remove('fa-spin');
            }
        };

        refreshButton.addEventListener('click', refreshInbox);
        selectAll.addEventListener('change', () => {
            rows.querySelectorAll('.email-select').forEach((input) => {
                if (input.closest('[data-email-row]')?.classList.contains('d-none')) return;
                input.checked = selectAll.checked;
            });
            updateSelection();
        });
        rows.addEventListener('change', (event) => {
            if (event.target.matches('.email-select')) updateSelection();
        });
        document.querySelectorAll('.email-filter-option').forEach((option) => {
            option.addEventListener('click', () => {
                currentFilter = option.dataset.filter;
                currentFilterLabel = option.textContent.trim();
                document.getElementById('email-filter-label').textContent = currentFilterLabel;
                applyFilter();
                updateSelection();
            });
        });
        document.querySelectorAll('[data-bulk-action]').forEach((button) => {
            button.addEventListener('click', () => {
                const selectedUids = [...rows.querySelectorAll('.email-select:checked')].map((input) => input.value);
                if (selectedUids.length === 0) return;
                const action = button.dataset.bulkAction;
                if (action === 'delete' && !window.confirm(`Permanently delete ${selectedUids.length} selected email(s)?`)) return;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = inbox.dataset.bulkUrl;
                [[ '_token', inbox.dataset.csrf ], [ 'action', action ]].forEach(([name, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    form.append(input);
                });
                selectedUids.forEach((uid) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'uids[]';
                    input.value = uid;
                    form.append(input);
                });
                document.body.append(form);
                form.submit();
            });
        });

        const existingRows = [...rows.querySelectorAll('[data-email-row]')];
        countLabel.textContent = `${existingRows.length} message${existingRows.length === 1 ? '' : 's'}`;
        refreshStatus.textContent = 'Auto-refresh every 30 seconds';
        applyFilter();
        updateSelection();
        window.setInterval(refreshInbox, 30000);
    })();
</script>
@endsection
