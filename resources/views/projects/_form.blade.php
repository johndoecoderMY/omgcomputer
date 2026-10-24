<form method="POST" action="{{ $action }}" class="card p-2">
    @csrf
    @if ($method !== 'POST')
        @method($method)
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
            <label class="form-label" for="name">Project name</label>
            <input class="form-control" id="name" name="name" value="{{ old('name', $project->name) }}"
                maxlength="180" required>
            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        @role('admin')
        <div class="mb-3">
            <label class="form-label" for="description">Description <span class="text-body-secondary">(optional)</span></label>
            <textarea class="form-control" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $project->description) }}</textarea>
            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="assignees">Assign people</label>
            <input type="hidden" name="assignees_present" value="1">
            <select class="form-select js-user-search" id="assignees" name="assignees[]" multiple data-placeholder="Search and select people">
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', old('assignees', $project->assignees->pluck('id')->all())), true))>{{ $user->name }}</option>
                @endforeach
            </select>
            @error('assignees')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('assignees.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        @endrole

        <div class="mb-3">
            <label class="form-label" for="requirements_notes">Requirements notes <span class="text-body-secondary">(optional)</span></label>
            <textarea class="form-control" id="requirements_notes" name="requirements_notes" rows="3" maxlength="5000">{{ old('requirements_notes', $project->requirements_notes) }}</textarea>
            @error('requirements_notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        @php
            $requirements = old('requirements', $project->requirements->map(fn ($requirement) => [
                'id' => $requirement->id,
                'requirement' => $requirement->requirement,
                'is_completed' => $requirement->is_completed,
            ])->all());
        @endphp
        <fieldset class="mb-3">
            <legend class="form-label">Requirements checklist</legend>
            <p class="form-text">Add each requirement separately. Check items that are already complete.</p>
            <div id="project-requirements" data-next-index="{{ count($requirements) }}">
                @foreach ($requirements as $index => $requirement)
                    <div class="row g-2 align-items-center mb-2" data-requirement-row>
                        @if (!empty($requirement['id']))
                            <input type="hidden" name="requirements[{{ $index }}][id]" value="{{ $requirement['id'] }}">
                        @endif
                        <div class="col-auto">
                            <input class="form-check-input" type="checkbox" name="requirements[{{ $index }}][is_completed]"
                                value="1" aria-label="Mark requirement complete" @checked((bool) ($requirement['is_completed'] ?? false))>
                        </div>
                        <div class="col">
                            <input class="form-control" name="requirements[{{ $index }}][requirement]"
                                value="{{ $requirement['requirement'] ?? '' }}" maxlength="500" required
                                aria-label="Requirement">
                        </div>
                        <div class="col-auto">
                            <button class="btn btn-sm btn-outline-danger" type="button" data-remove-requirement>Remove</button>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('requirements')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @error('requirements.*.requirement')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <button class="btn btn-sm btn-outline-primary mt-2" type="button" id="add-project-requirement">
                <i class="bi bi-plus me-1" aria-hidden="true"></i>Add requirement
            </button>
        </fieldset>

        <div class="mb-3">
            <label class="form-label" for="customer_identity">Linked customer <span class="text-body-secondary">(optional)</span></label>
            <select class="form-select" id="customer_identity" name="customer_identity">
                <option value="">No customer linked</option>
                @foreach ($customerChoices as $choice)
                    <option value="{{ $choice['value'] }}"
                        @selected(old('customer_identity', old('customer_id') ? 'record:'.old('customer_id') : $selectedCustomerIdentity) === $choice['value'])>
                        {{ $choice['label'] }}
                    </option>
                @endforeach
            </select>
            @error('customer_identity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="form-text">Customer accounts and records with the same email appear once. Selecting a record moves its project link here; selecting an account links it without creating a duplicate customer record.</div>
        </div>
    </div>
    <div class="card-footer bg-body d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ isset($project->id) ? route('projects.show', $project) : route('projects.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit">{{ $buttonLabel }}</button>
    </div>
</form>

<template id="project-requirement-template">
    <div class="row g-2 align-items-center mb-2" data-requirement-row>
        <div class="col-auto">
            <input class="form-check-input" type="checkbox" name="requirements[__INDEX__][is_completed]"
                value="1" aria-label="Mark requirement complete">
        </div>
        <div class="col">
            <input class="form-control" name="requirements[__INDEX__][requirement]" maxlength="500" required
                aria-label="Requirement">
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-outline-danger" type="button" data-remove-requirement>Remove</button>
        </div>
    </div>
</template>

<script>
    (() => {
        const list = document.getElementById('project-requirements');
        const template = document.getElementById('project-requirement-template');
        const addButton = document.getElementById('add-project-requirement');
        if (!list || !template || !addButton) return;

        addButton.addEventListener('click', () => {
            const index = Number(list.dataset.nextIndex);
            list.dataset.nextIndex = String(index + 1);
            list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
        });

        list.addEventListener('click', (event) => {
            if (event.target.closest('[data-remove-requirement]')) {
                event.target.closest('[data-requirement-row]').remove();
            }
        });
    })();
</script>
