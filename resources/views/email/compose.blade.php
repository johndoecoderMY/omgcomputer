@extends('layouts.app')

@section('title', 'Compose Email')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Email" title="Compose" description="Write a message from your cPanel mailbox."
        :back-url="route('email.inbox')" back-label="Email inbox" />

    @if (session('compose_error'))
        <div class="alert alert-danger" role="alert">{{ session('compose_error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @unless ($mailbox)
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2" role="alert">
            <span>Connect your cPanel mailbox before sending email.</span>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('email.mailbox-settings') }}">Configure mailbox</a>
        </div>
    @endunless

    <form class="card" method="POST" action="{{ route('email.send') }}" enctype="multipart/form-data">
        @csrf
        <div class="card-header bg-body-tertiary">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <h2 class="h5 mb-0">New message</h2>
                    <div class="small text-body-secondary mt-1">
                        From: {{ $mailbox?->email ?? 'Mailbox not configured' }}
                    </div>
                </div>
                <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#email-copywriting-modal">
                    <span class="bi bi-lightbulb me-1" aria-hidden="true"></span>Suggest from Copywriting
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="email-recipient-row border-bottom border-200 d-flex align-items-start px-x1 py-2">
                <label class="email-recipient-label pt-2" for="email-recipient-input">To</label>
                <div class="email-recipient-field flex-grow-1 d-flex flex-wrap align-items-center gap-1">
                    @php($oldRecipients = old('recipients', []))
                    @if (! is_array($oldRecipients))
                        @php($oldRecipients = [])
                    @endif
                    <div class="email-recipient-chips d-flex flex-wrap align-items-center gap-1" id="email-recipient-chips">
                        @foreach ($oldRecipients as $recipient)
                            @if (is_string($recipient) && $recipient !== '')
                                <span class="badge rounded-pill bg-body-secondary text-body email-recipient-chip" data-recipient="{{ $recipient }}">
                                    <span>{{ $recipient }}</span>
                                    <button class="email-recipient-remove" type="button" aria-label="Remove {{ $recipient }}">&times;</button>
                                    <input type="hidden" name="recipients[]" value="{{ $recipient }}">
                                </span>
                            @endif
                        @endforeach
                    </div>
                    <input class="form-control border-0 rounded-0 outline-none p-1 flex-grow-1 email-recipient-input" id="email-recipient-input"
                        type="text" placeholder="Add recipients" autocomplete="email" aria-describedby="email-recipient-help">
                </div>
            </div>
            <div class="visually-hidden" id="email-recipient-help">Type an email address and press Enter or comma to add another recipient.</div>
            <div class="border-bottom border-200">
                <label class="visually-hidden" for="email-subject">Subject</label>
                <input class="form-control border-0 rounded-0 outline-none px-x1" id="email-subject" name="subject"
                    type="text" value="{{ old('subject') }}" placeholder="Subject" maxlength="255" required>
            </div>
            <div class="email-compose-textarea">
                <label class="visually-hidden" for="email-body">Message</label>
                <textarea class="form-control border-0 rounded-0 px-x1 py-3" id="email-body" name="body"
                    rows="12" maxlength="100000" placeholder="Write your message…" required>{{ old('body') }}</textarea>
            </div>
            @if (filled($mailbox?->signature))
                <div class="border-top border-200 px-x1 py-3">
                    <div class="small text-body-secondary mb-2">Your signature will be added when you send:</div>
                    <div class="small" style="white-space: pre-wrap;">--&#10;{{ $mailbox->signature }}</div>
                    <a class="small d-inline-block mt-2" href="{{ route('email.mailbox-settings') }}">Edit signature</a>
                </div>
            @else
                <div class="border-top border-200 px-x1 py-2">
                    <a class="small" href="{{ route('email.mailbox-settings') }}">Set up an email signature</a>
                </div>
            @endif
            <div class="bg-body-tertiary px-x1 py-3">
                <div class="small text-body-secondary" id="email-attachment-list" aria-live="polite">No attachments selected</div>
            </div>
        </div>
        <div class="card-footer border-top border-200 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-primary btn-sm px-4" type="submit">
                    <span class="bi bi-send me-2" aria-hidden="true"></span>Send
                </button>
                <input class="visually-hidden" id="email-attachments" name="attachments[]" type="file" multiple
                    accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.txt,.csv,.doc,.docx,.xls,.xlsx,.zip">
                <label class="btn btn-tertiary btn-sm mb-0" for="email-attachments">
                    <span class="bi bi-paperclip me-1" aria-hidden="true"></span>Attach files
                </label>
                <span class="small text-body-secondary">Up to 5 files, 10 MB each</span>
            </div>
            <a class="btn btn-tertiary btn-sm" href="{{ route('email.inbox') }}" aria-label="Discard message" title="Discard message">
                <span class="bi bi-trash" aria-hidden="true"></span>
            </a>
        </div>
    </form>

    <div class="modal fade" id="email-copywriting-modal" tabindex="-1" aria-labelledby="email-copywriting-title" aria-hidden="true"
        data-suggestions-url="{{ route('customer-messages.suggestions') }}">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5" id="email-copywriting-title">Choose an email suggestion</h2>
                        <div class="small text-body-secondary">Select a saved template, then review and personalize it before sending.</div>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="list-group" id="email-copywriting-list" aria-label="Email copywriting templates"></div>
                        </div>
                        <div class="col-md-7">
                            <div id="email-copywriting-status" class="small text-body-secondary" role="status" aria-live="polite">Loading suggestions…</div>
                            <div id="email-copywriting-preview" class="mt-3" hidden>
                                <div class="small text-body-secondary" id="email-copywriting-stage"></div>
                                <h3 class="h6 mt-1" id="email-copywriting-name"></h3>
                                <div id="email-copywriting-subject-wrap" class="mb-2" hidden>
                                    <div class="small text-body-secondary">Subject</div>
                                    <div id="email-copywriting-subject" class="fw-semibold"></div>
                                </div>
                                <div class="small text-body-secondary">Message</div>
                                <pre id="email-copywriting-body" class="copywriting-preview mb-0"></pre>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <a class="small" href="{{ route('customer-messages.index') }}">Manage Copywriting templates</a>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary" id="email-copywriting-use" type="button" disabled>Use suggestion</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (() => {
        const form = document.querySelector('form[action="{{ route('email.send') }}"]');
        const recipientInput = document.getElementById('email-recipient-input');
        const recipientChips = document.getElementById('email-recipient-chips');
        const input = document.getElementById('email-attachments');
        const list = document.getElementById('email-attachment-list');
        const suggestionsModal = document.getElementById('email-copywriting-modal');
        const suggestionsList = document.getElementById('email-copywriting-list');
        const suggestionsStatus = document.getElementById('email-copywriting-status');
        const suggestionsPreview = document.getElementById('email-copywriting-preview');
        const useSuggestion = document.getElementById('email-copywriting-use');
        let suggestions = [];
        let selectedSuggestion = null;
        let suggestionsLoaded = false;
        let suggestionsLoading = false;

        const recipients = () => [...recipientChips.querySelectorAll('input[name="recipients[]"]')]
            .map((recipient) => recipient.value.trim().toLowerCase());

        const addRecipient = (rawValue) => {
            const address = rawValue.trim().replace(/[;,]+$/, '').trim();
            if (!address) return true;
            if (!/^[^\s@,;]+@[^\s@,;]+\.[^\s@,;]+$/.test(address)) {
                recipientInput.setCustomValidity('Enter a valid email address.');
                recipientInput.reportValidity();
                return false;
            }
            if (recipients().includes(address.toLowerCase())) {
                recipientInput.setCustomValidity('This recipient has already been added.');
                recipientInput.reportValidity();
                return false;
            }
            if (recipientChips.querySelectorAll('input[name="recipients[]"]').length >= 50) {
                recipientInput.setCustomValidity('You can add up to 50 recipients.');
                recipientInput.reportValidity();
                return false;
            }

            recipientInput.setCustomValidity('');
            const chip = document.createElement('span');
            chip.className = 'badge rounded-pill bg-body-secondary text-body email-recipient-chip';
            const label = document.createElement('span');
            label.textContent = address;
            const remove = document.createElement('button');
            remove.className = 'email-recipient-remove';
            remove.type = 'button';
            remove.setAttribute('aria-label', `Remove ${address}`);
            remove.textContent = '×';
            remove.addEventListener('click', () => chip.remove());
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'recipients[]';
            hidden.value = address;
            chip.append(label, remove, hidden);
            recipientChips.append(chip);
            recipientInput.value = '';
            return true;
        };

        recipientChips.querySelectorAll('.email-recipient-chip').forEach((chip) => {
            chip.querySelector('.email-recipient-remove').addEventListener('click', () => chip.remove());
        });
        recipientInput.addEventListener('input', () => recipientInput.setCustomValidity(''));
        recipientInput.addEventListener('paste', (event) => {
            const pasted = event.clipboardData.getData('text');
            if (!/[,;\r\n]/.test(pasted)) return;
            event.preventDefault();
            pasted.split(/[,;\r\n]+/).forEach((address) => {
                if (address.trim() !== '') addRecipient(address);
            });
        });
        recipientInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ',' || event.key === ';') {
                event.preventDefault();
                addRecipient(recipientInput.value);
            } else if (event.key === 'Backspace' && recipientInput.value === '') {
                recipientChips.lastElementChild?.remove();
            }
        });
        recipientInput.addEventListener('blur', () => {
            if (recipientInput.value.trim() !== '') addRecipient(recipientInput.value);
        });
        form.addEventListener('submit', (event) => {
            if (recipientInput.value.trim() !== '' && !addRecipient(recipientInput.value)) {
                event.preventDefault();
                recipientInput.focus();
                return;
            }
            if (recipients().length === 0) {
                event.preventDefault();
                recipientInput.setCustomValidity('Add at least one recipient.');
                recipientInput.reportValidity();
                recipientInput.focus();
            }
        });

        input.addEventListener('change', () => {
            const files = [...input.files].map((file) => `${file.name} (${(file.size / 1024 / 1024).toFixed(1)} MB)`);
            list.textContent = files.length ? files.join(' · ') : 'No attachments selected';
        });

        const selectSuggestion = (suggestion) => {
            selectedSuggestion = suggestion;
            suggestionsList.querySelectorAll('[data-suggestion-id]').forEach((button) => {
                button.classList.toggle('active', button.dataset.suggestionId === String(suggestion.id));
                button.setAttribute('aria-pressed', String(button.dataset.suggestionId === String(suggestion.id)));
            });
            document.getElementById('email-copywriting-stage').textContent = suggestion.stage;
            document.getElementById('email-copywriting-name').textContent = suggestion.name;
            const subjectWrap = document.getElementById('email-copywriting-subject-wrap');
            subjectWrap.hidden = !suggestion.subject;
            document.getElementById('email-copywriting-subject').textContent = suggestion.subject;
            document.getElementById('email-copywriting-body').textContent = suggestion.body;
            suggestionsPreview.hidden = false;
            suggestionsStatus.textContent = 'Review the sample wording and personalize details before sending.';
            useSuggestion.disabled = false;
        };

        const loadSuggestions = async () => {
            if (suggestionsLoaded || suggestionsLoading) return;
            suggestionsLoading = true;
            suggestionsStatus.textContent = 'Loading suggestions…';
            try {
                const url = new URL(suggestionsModal.dataset.suggestionsUrl, window.location.origin);
                url.searchParams.set('channel', 'email');
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
                    : 'No active email templates yet. Add one in Copywriting.';
            } catch {
                suggestionsStatus.textContent = 'Suggestions could not be loaded. Please try again.';
            } finally {
                suggestionsLoading = false;
            }
        };

        suggestionsModal.addEventListener('show.bs.modal', loadSuggestions);
        useSuggestion.addEventListener('click', () => {
            if (!selectedSuggestion) return;
            if (selectedSuggestion.subject) document.getElementById('email-subject').value = selectedSuggestion.subject;
            document.getElementById('email-body').value = selectedSuggestion.body;
            bootstrap.Modal.getOrCreateInstance(suggestionsModal).hide();
            document.getElementById('email-body').focus();
        });
    })();
</script>
<style>
    .email-recipient-label { width: 3rem; flex-shrink: 0; }
    .email-recipient-field { min-width: 0; }
    .email-recipient-input { min-width: 12rem; }
    .email-recipient-chip { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; padding: .35rem .5rem; }
    .email-recipient-remove { border: 0; padding: 0; color: inherit; background: transparent; font-size: 1rem; line-height: 1; }
    .copywriting-preview { font: inherit; white-space: pre-wrap; overflow-wrap: anywhere; color: inherit; border: 1px solid var(--falcon-border-color, #d8e0e8); border-radius: 4px; padding: 1rem; max-height: 16rem; overflow-y: auto; }
</style>
@endsection
