@extends('layouts.app')

@section('title', 'WhatsApp Inbox')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Integrations" title="WhatsApp Inbox"
        description="Inbound messages delivered by the WhatsApp Cloud API webhook."
        :back-url="route('reports.integrations.whatsapp')" back-label="WhatsApp setup">
        <x-slot:actions>
            <span class="small text-body-secondary" id="whatsapp-inbox-status" role="status">Auto-refresh every 15 seconds</span>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        <div class="list-group list-group-flush" id="whatsapp-inbound-messages"
            data-poll-url="{{ route('reports.integrations.whatsapp.inbox.poll') }}"
            data-suggestions-url="{{ route('customer-messages.suggestions') }}">
            @forelse ($messages as $message)
                <article class="list-group-item p-3 d-flex gap-3 whatsapp-message" data-message-id="{{ $message->id }}">
                    <span class="avatar avatar-s bg-success-subtle text-success d-flex align-items-center justify-content-center" aria-hidden="true">
                        <span class="fab fa-whatsapp"></span>
                    </span>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div class="fw-semibold">{{ $message->contact_name ?: $message->wa_id }}</div>
                            <time class="small text-body-secondary" datetime="{{ $message->received_at->toIso8601String() }}">{{ $message->received_at->format('M j, Y H:i') }}</time>
                        </div>
                        @if ($message->contact_name)
                            <div class="small text-body-secondary">{{ $message->wa_id }}</div>
                        @endif
                        <div class="mt-2 text-break">{{ $message->message_body ?: '['.ucfirst($message->message_type).' message]' }}</div>
                        <button class="btn btn-sm btn-outline-success mt-2 whatsapp-suggest-reply" type="button"
                            data-wa-id="{{ $message->wa_id }}" data-contact-name="{{ $message->contact_name ?: $message->wa_id }}">
                            <span class="fas fa-lightbulb me-1" aria-hidden="true"></span>Suggest a reply
                        </button>
                    </div>
                </article>
            @empty
                <div class="text-center text-body-secondary py-5" id="whatsapp-inbox-empty">
                    <span class="fab fa-whatsapp fs-2 d-block mb-2" aria-hidden="true"></span>
                    No inbound WhatsApp messages yet.
                </div>
            @endforelse
        </div>
    </div>
</div>

<div class="modal fade" id="whatsapp-copywriting-modal" tabindex="-1" aria-labelledby="whatsapp-copywriting-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5" id="whatsapp-copywriting-title">Choose a WhatsApp suggestion</h2>
                    <div class="small text-body-secondary" id="whatsapp-copywriting-recipient"></div>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="list-group" id="whatsapp-copywriting-list" aria-label="WhatsApp copywriting templates"></div>
                    </div>
                    <div class="col-md-7">
                        <div id="whatsapp-copywriting-status" class="small text-body-secondary" role="status" aria-live="polite">Loading suggestions…</div>
                        <div id="whatsapp-copywriting-preview" class="mt-3" hidden>
                            <div class="small text-body-secondary" id="whatsapp-copywriting-stage"></div>
                            <h3 class="h6 mt-1" id="whatsapp-copywriting-name"></h3>
                            <pre id="whatsapp-copywriting-body" class="whatsapp-copywriting-preview mb-0"></pre>
                        </div>
                    </div>
                </div>
                <p class="small text-body-secondary mt-3 mb-0">This opens a WhatsApp draft for you to review. It does not send the message automatically.</p>
            </div>
            <div class="modal-footer justify-content-between">
                <a class="small" href="{{ route('customer-messages.index') }}">Manage Copywriting templates</a>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success" id="whatsapp-copywriting-open" type="button" disabled>
                        <span class="fab fa-whatsapp me-1" aria-hidden="true"></span>Open WhatsApp draft
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const list = document.getElementById('whatsapp-inbound-messages');
        const status = document.getElementById('whatsapp-inbox-status');
        const pollUrl = list.dataset.pollUrl;
        const suggestionsUrl = list.dataset.suggestionsUrl;
        const suggestionsModal = document.getElementById('whatsapp-copywriting-modal');
        const suggestionsList = document.getElementById('whatsapp-copywriting-list');
        const suggestionsStatus = document.getElementById('whatsapp-copywriting-status');
        const suggestionsPreview = document.getElementById('whatsapp-copywriting-preview');
        const openDraft = document.getElementById('whatsapp-copywriting-open');
        let polling = false;
        let suggestions = [];
        let selectedSuggestion = null;
        let activeRecipient = '';
        let activeContactName = '';
        let suggestionsLoaded = false;
        let suggestionsLoading = false;

        const selectSuggestion = (suggestion) => {
            selectedSuggestion = suggestion;
            suggestionsList.querySelectorAll('[data-suggestion-id]').forEach((button) => {
                const isSelected = button.dataset.suggestionId === String(suggestion.id);
                button.classList.toggle('active', isSelected);
                button.setAttribute('aria-pressed', String(isSelected));
            });
            document.getElementById('whatsapp-copywriting-stage').textContent = suggestion.stage;
            document.getElementById('whatsapp-copywriting-name').textContent = suggestion.name;
            document.getElementById('whatsapp-copywriting-body').textContent = suggestion.body;
            suggestionsPreview.hidden = false;
            suggestionsStatus.textContent = 'Review the sample wording and personalize details before opening the draft.';
            openDraft.disabled = false;
        };

        const loadSuggestions = async () => {
            if (suggestionsLoaded || suggestionsLoading) return;
            suggestionsLoading = true;
            suggestionsStatus.textContent = 'Loading suggestions…';
            try {
                const url = new URL(suggestionsUrl, window.location.origin);
                url.searchParams.set('channel', 'whatsapp');
                const response = await fetch(url, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!response.ok) throw new Error('Could not load Copywriting suggestions.');
                const payload = await response.json();
                suggestions = payload.templates || [];
                suggestionsList.replaceChildren();
                suggestions.forEach((suggestion) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'list-group-item list-group-item-action';
                    button.dataset.suggestionId = suggestion.id;
                    button.setAttribute('aria-pressed', 'false');
                    const name = document.createElement('span');
                    name.className = 'fw-semibold d-block';
                    name.textContent = suggestion.name;
                    const stage = document.createElement('span');
                    stage.className = 'small text-body-secondary';
                    stage.textContent = suggestion.stage;
                    button.append(name, stage);
                    button.addEventListener('click', () => selectSuggestion(suggestion));
                    suggestionsList.append(button);
                });
                suggestionsLoaded = true;
                suggestionsStatus.textContent = suggestions.length
                    ? 'Choose a template to preview it.'
                    : 'No active WhatsApp templates yet. Add one in Copywriting.';
            } catch {
                suggestionsStatus.textContent = 'Suggestions could not be loaded. Please try again.';
            } finally {
                suggestionsLoading = false;
            }
        };

        list.addEventListener('click', (event) => {
            const button = event.target.closest('.whatsapp-suggest-reply');
            if (!button) return;
            activeRecipient = button.dataset.waId || '';
            activeContactName = button.dataset.contactName || activeRecipient;
            selectedSuggestion = null;
            openDraft.disabled = true;
            suggestionsPreview.hidden = true;
            document.getElementById('whatsapp-copywriting-recipient').textContent = `Draft for ${activeContactName}`;
            bootstrap.Modal.getOrCreateInstance(suggestionsModal).show();
        });

        suggestionsModal.addEventListener('show.bs.modal', loadSuggestions);
        openDraft.addEventListener('click', () => {
            if (!selectedSuggestion) return;
            const recipient = activeRecipient.replace(/\D/g, '');
            if (!/^\d{5,15}$/.test(recipient)) {
                suggestionsStatus.textContent = 'This contact does not have a valid WhatsApp number.';
                return;
            }
            const url = new URL(`https://wa.me/${recipient}`);
            url.searchParams.set('text', selectedSuggestion.body);
            window.open(url.toString(), '_blank', 'noopener,noreferrer');
        });

        const renderMessage = (message) => {
            const item = document.createElement('article');
            item.className = 'list-group-item p-3 d-flex gap-3 whatsapp-message';
            item.dataset.messageId = message.id;

            const icon = document.createElement('span');
            icon.className = 'avatar avatar-s bg-success-subtle text-success d-flex align-items-center justify-content-center';
            icon.setAttribute('aria-hidden', 'true');
            const whatsappIcon = document.createElement('span');
            whatsappIcon.className = 'fab fa-whatsapp';
            icon.append(whatsappIcon);

            const content = document.createElement('div');
            content.className = 'flex-grow-1 min-w-0';
            const header = document.createElement('div');
            header.className = 'd-flex flex-wrap justify-content-between gap-2';
            const sender = document.createElement('div');
            sender.className = 'fw-semibold';
            sender.textContent = message.contact_name || message.wa_id;
            const date = document.createElement('time');
            date.className = 'small text-body-secondary';
            date.dateTime = message.received_at;
            date.textContent = new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(message.received_at));
            header.append(sender, date);
            content.append(header);

            if (message.contact_name) {
                const number = document.createElement('div');
                number.className = 'small text-body-secondary';
                number.textContent = message.wa_id;
                content.append(number);
            }

            const body = document.createElement('div');
            body.className = 'mt-2 text-break';
            body.textContent = message.message_body || `[${message.message_type} message]`;
            const reply = document.createElement('button');
            reply.className = 'btn btn-sm btn-outline-success mt-2 whatsapp-suggest-reply';
            reply.type = 'button';
            reply.dataset.waId = message.wa_id || '';
            reply.dataset.contactName = message.contact_name || message.wa_id || '';
            reply.textContent = 'Suggest a reply';
            content.append(body, reply);
            item.append(icon, content);
            return item;
        };

        const refresh = async () => {
            if (polling || document.hidden) return;
            polling = true;
            try {
                const response = await fetch(pollUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });
                if (!response.ok) throw new Error('Could not refresh WhatsApp messages.');
                const payload = await response.json();
                const empty = list.querySelector('#whatsapp-inbox-empty');
                const existing = new Map([...list.querySelectorAll('[data-message-id]')].map((item) => [item.dataset.messageId, item]));
                const visibleIds = new Set();
                (payload.messages || []).forEach((message) => {
                    const id = String(message.id);
                    visibleIds.add(id);
                    const item = existing.get(id) || renderMessage(message);
                    list.append(item);
                });
                existing.forEach((item, id) => {
                    if (!visibleIds.has(id)) item.remove();
                });
                if ((payload.messages || []).length > 0) empty?.remove();
                else if (!empty) {
                    const emptyState = document.createElement('div');
                    emptyState.id = 'whatsapp-inbox-empty';
                    emptyState.className = 'text-center text-body-secondary py-5';
                    emptyState.textContent = 'No inbound WhatsApp messages yet.';
                    list.append(emptyState);
                }
                status.textContent = `Updated ${new Date(payload.checked_at).toLocaleTimeString()}`;
            } catch {
                status.textContent = 'Refresh failed; retrying soon';
            } finally {
                polling = false;
            }
        };

        window.setInterval(refresh, 15000);
    })();
</script>
<style>
    .whatsapp-copywriting-preview { font: inherit; white-space: pre-wrap; overflow-wrap: anywhere; color: inherit; border: 1px solid var(--falcon-border-color, #d8e0e8); border-radius: 4px; padding: 1rem; max-height: 16rem; overflow-y: auto; }
</style>
@endsection
