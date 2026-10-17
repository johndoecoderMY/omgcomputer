@php
    $steps = old('steps', $flowChart->steps ?? []);
@endphp

<form method="POST" action="{{ $action }}" class="card p-2" data-flow-chart-category="{{ old('category', $flowChart->category ?? 'website') }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    @if ($method === 'POST' && !empty($projectId))
        <input type="hidden" name="project_id" value="{{ $projectId }}">
    @endif
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <p class="fw-semibold mb-1">Please correct the following:</p>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label" for="title">Flow chart name</label>
            <input class="form-control" id="title" name="title" value="{{ old('title', $flowChart->title) }}"
                maxlength="255" required>
            @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
            <label class="form-label" for="category">Flow chart purpose</label>
            <select class="form-select" id="category" name="category" required>
                @foreach (['website' => 'Website journey', 'process' => 'Business process', 'product' => 'Product SOP', 'service' => 'Service SOP', 'education' => 'Student FYP SOP'] as $key => $label)
                    <option value="{{ $key }}" @selected(old('category', $flowChart->category ?? 'website') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4" id="sop-subject-field">
            <label class="form-label" for="subject_name">Product, service, or programme name <span class="text-body-secondary">(for SOPs)</span></label>
            <input class="form-control" id="subject_name" name="subject_name" value="{{ old('subject_name', $flowChart->subject_name) }}" maxlength="255">
            @error('subject_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-4">
            <label class="form-label" for="description">Description <span class="text-body-secondary">(optional)</span></label>
            <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000">{{ old('description', $flowChart->description) }}</textarea>
            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="form-check mb-4">
            <input type="hidden" name="is_example" value="0">
            <input class="form-check-input" type="checkbox" id="is_example" name="is_example" value="1"
                @checked(old('is_example', $flowChart->is_example ?? false))>
            <label class="form-check-label" for="is_example">Example flow chart or SOP</label>
            <div class="form-text">Examples appear in the SOP library and can only be duplicated into projects.</div>
        </div>
        @error('is_example')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
            <div>
                <h2 class="h5 mb-1" id="flow-steps-heading">Website pages and connections</h2>
                <p class="small text-body-secondary mb-0" id="flow-steps-help">Each node can link to multiple pages. Use the checkboxes under a node to create branches.</p>
            </div>
            <button class="btn btn-sm btn-outline-primary" type="button" id="add-flow-step">
                <i class="fas fa-plus me-1"></i>Add step
            </button>
        </div>

        <div id="flow-steps" class="d-grid gap-2">
            @foreach ($steps as $index => $step)
                <div class="flow-step-row flow-chart-editor-node border rounded-2 p-3" data-step-id="{{ $step['id'] ?? '' }}">
                    <input class="step-id-input" type="hidden" name="steps[{{ $index }}][id]" value="{{ $step['id'] ?? '' }}">
                    @if (isset($step['x'], $step['y']))
                        <input class="step-position-x" type="hidden" name="steps[{{ $index }}][x]" value="{{ $step['x'] }}">
                        <input class="step-position-y" type="hidden" name="steps[{{ $index }}][y]" value="{{ $step['y'] }}">
                    @endif
                    <div class="flow-chart-editor-heading mb-2">
                        <span class="flow-chart-node-badge">{{ $index + 1 }}</span>
                        <span class="fw-semibold">Node {{ $index + 1 }}</span>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-7">
                            <label class="form-label" for="step-label-{{ $index }}">Step name</label>
                            <input class="form-control" id="step-label-{{ $index }}"
                                name="steps[{{ $index }}][label]" value="{{ $step['label'] ?? '' }}"
                                maxlength="255" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="step-kind-{{ $index }}">Step type</label>
                            <select class="form-select" id="step-kind-{{ $index }}" name="steps[{{ $index }}][kind]" required>
                                @foreach (['page' => 'Website page', 'action' => 'User action', 'decision' => 'Decision', 'start' => 'Start', 'end' => 'End'] as $kind => $label)
                                    <option value="{{ $kind }}" @selected(($step['kind'] ?? 'page') === $kind)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 d-grid">
                            <button class="btn btn-outline-danger remove-flow-step" type="button" aria-label="Remove step">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <fieldset class="flow-step-connections mt-3">
                        <legend class="form-label fs-9 mb-2">Connect this node to <span class="text-body-secondary">(select any number)</span></legend>
                        <div class="flow-step-targets d-flex flex-wrap gap-2">
                            @foreach ($steps as $targetIndex => $target)
                                @if ($targetIndex !== $index)
                                    <label class="form-check form-check-inline border rounded-2 px-2 py-1 mb-0">
                                        <input class="form-check-input ms-0 me-1" type="checkbox"
                                            name="steps[{{ $index }}][connections][]"
                                            value="{{ $target['id'] ?? '' }}"
                                            @checked(in_array($target['id'] ?? '', $step['connections'] ?? [], true))>
                                        <span class="form-check-label">{{ $target['label'] ?: 'Untitled step ' . ($targetIndex + 1) }}</span>
                                    </label>
                                @endif
                            @endforeach
                            @if (count($steps) <= 1)
                                <span class="small text-body-secondary">Add another page to create a connection.</span>
                            @endif
                        </div>
                    </fieldset>
                </div>
            @endforeach
        </div>
        @error('steps')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('steps.*.label')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('steps.*.kind')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('steps.*.connections')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="card-footer bg-body d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('development.flowcharts.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit">{{ $buttonLabel }}</button>
    </div>
</form>

@push('scripts')
<script>
    (() => {
        const container = document.getElementById('flow-steps');
        const addButton = document.getElementById('add-flow-step');
        const maxSteps = 30;
        const categorySelect = document.getElementById('category');
        const subjectField = document.getElementById('sop-subject-field');
        const syncPurpose = () => {
            const website = categorySelect.value === 'website';
            document.getElementById('flow-steps-heading').textContent = website
                ? 'Website pages and connections'
                : 'Process steps and connections';
            document.getElementById('flow-steps-help').textContent = website
                ? 'Each node can link to multiple pages. Use the checkboxes under a node to create branches.'
                : 'Describe the actions, decisions and hand-offs in this workflow.';
            subjectField.hidden = !['product', 'service', 'education'].includes(categorySelect.value);
            document.getElementById('subject_name').required = !subjectField.hidden;
            document.querySelectorAll('.flow-chart-editor-node select[name$="[kind]"] option[value="page"]').forEach((option) => {
                option.textContent = categorySelect.value === 'website' ? 'Website page' : 'Step';
            });
        };
        categorySelect.addEventListener('change', syncPurpose);

        const updateRows = () => {
            const rows = [...container.querySelectorAll('.flow-step-row')];
            rows.forEach((row, index) => {
                const stepId = row.dataset.stepId;
                row.querySelector('.flow-chart-node-badge').textContent = index + 1;
                row.querySelector('.flow-chart-editor-heading .fw-semibold').textContent = `Node ${index + 1}`;
                const idInput = row.querySelector('.step-id-input');
                idInput.name = `steps[${index}][id]`;
                idInput.value = stepId;
                const positionX = row.querySelector('.step-position-x');
                const positionY = row.querySelector('.step-position-y');
                if (positionX) positionX.name = `steps[${index}][x]`;
                if (positionY) positionY.name = `steps[${index}][y]`;
                row.querySelector('label[for^="step-label-"]').htmlFor = `step-label-${index}`;
                row.querySelector('[name$="[label]"]').id = `step-label-${index}`;
                row.querySelector('label[for^="step-kind-"]').htmlFor = `step-kind-${index}`;
                row.querySelector('[name$="[kind]"]').id = `step-kind-${index}`;
                row.querySelector('[name$="[label]"]').name = `steps[${index}][label]`;
                row.querySelector('[name$="[kind]"]').name = `steps[${index}][kind]`;

                const selectedConnections = new Set(
                    [...row.querySelectorAll('.flow-step-targets input:checked')].map((input) => input.value)
                );
                const targets = row.querySelector('.flow-step-targets');
                targets.replaceChildren();
                rows.forEach((target, targetIndex) => {
                    if (target === row) return;

                    const targetId = target.dataset.stepId;
                    const targetLabel = target.querySelector('[name$="[label]"]').value.trim()
                        || `Untitled step ${targetIndex + 1}`;
                    const label = document.createElement('label');
                    label.className = 'form-check form-check-inline flow-chart-target border rounded-2 px-2 py-1 mb-0';

                    const checkbox = document.createElement('input');
                    checkbox.className = 'form-check-input ms-0 me-1';
                    checkbox.type = 'checkbox';
                    checkbox.name = `steps[${index}][connections][]`;
                    checkbox.value = targetId;
                    checkbox.checked = selectedConnections.has(targetId);

                    const text = document.createElement('span');
                    text.className = 'form-check-label';
                    text.textContent = targetLabel;

                    label.append(checkbox, text);
                    targets.append(label);
                });

                if (rows.length <= 1) {
                    const hint = document.createElement('span');
                    hint.className = 'small text-body-secondary';
                    hint.textContent = 'Add another page to create a connection.';
                    targets.append(hint);
                }
            });
            addButton.disabled = container.children.length >= maxSteps;
        };

        addButton.addEventListener('click', () => {
            if (container.children.length >= maxSteps) return;

            const index = container.children.length;
            const row = document.createElement('div');
            row.className = 'flow-step-row flow-chart-editor-node border rounded-2 p-3';
            row.dataset.stepId = window.crypto?.randomUUID
                ? window.crypto.randomUUID()
                : `step-${Date.now()}-${Math.random().toString(36).slice(2)}`;
            row.innerHTML = `
                <input class="step-id-input" type="hidden">
                <div class="flow-chart-editor-heading mb-2">
                    <span class="flow-chart-node-badge"></span>
                    <span class="fw-semibold"></span>
                </div>
                <div class="row g-2 align-items-end">
                    <div class="col-md-7">
                        <label class="form-label" for="step-label-${index}">Step name</label>
                        <input class="form-control" id="step-label-${index}" name="steps[${index}][label]" maxlength="255" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="step-kind-${index}">Step type</label>
                        <select class="form-select" id="step-kind-${index}" name="steps[${index}][kind]" required>
                            <option value="page">${categorySelect.value === 'website' ? 'Website page' : 'Step'}</option>
                            <option value="action">User action</option>
                            <option value="decision">Decision</option>
                            <option value="start">Start</option>
                            <option value="end">End</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-grid">
                        <button class="btn btn-outline-danger remove-flow-step" type="button" aria-label="Remove step">
                            <i class="fas fa-trash" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <fieldset class="flow-step-connections mt-3">
                    <legend class="form-label fs-9 mb-2">Connect this node to <span class="text-body-secondary">(select any number)</span></legend>
                    <div class="flow-step-targets d-flex flex-wrap gap-2"></div>
                </fieldset>`;
            container.append(row);
            updateRows();
            row.querySelector('[name$="[label]"]').focus();
        });

        container.addEventListener('click', (event) => {
            const button = event.target.closest('.remove-flow-step');
            if (!button) return;
            if (container.children.length <= 1) {
                container.querySelector('[name$="[label]"]').setCustomValidity('A flow chart needs at least one step.');
                container.querySelector('[name$="[label]"]').reportValidity();
                container.querySelector('[name$="[label]"]').setCustomValidity('');
                return;
            }
            button.closest('.flow-step-row').remove();
            updateRows();
        });

        container.addEventListener('input', (event) => {
            if (event.target.matches('[name$="[label]"]')) updateRows();
        });

        updateRows();
        syncPurpose();
    })();
</script>
@endpush
