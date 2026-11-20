@php
    $usesOldInput = old('_requirement_form') === $formId;
    $selectedType = $usesOldInput
        ? old('requirement_type', $requirement?->requirement_type ?? array_key_first($requirementTypes))
        : ($requirement?->requirement_type ?? array_key_first($requirementTypes));
    $selectedPriority = $usesOldInput
        ? old('priority', $requirement?->priority ?? array_key_first($requirementPriorities))
        : ($requirement?->priority ?? array_key_first($requirementPriorities));
    $selectedStatus = $usesOldInput
        ? old('status', $requirement?->status ?? array_key_first($requirementStatuses))
        : ($requirement?->status ?? array_key_first($requirementStatuses));
    $selectedModule = (string) ($usesOldInput
        ? old('project_module_id', $requirement?->project_module_id)
        : ($requirement?->project_module_id ?? ''));
    $selectedParent = (string) ($usesOldInput
        ? old('parent_requirement_id', $requirement?->parent_requirement_id)
        : ($requirement?->parent_requirement_id ?? ''));
@endphp
<div class="row g-3">
    <div class="col-12">
        <label class="form-label" for="{{ $formId }}-requirement">Requirement</label>
        <textarea class="form-control" id="{{ $formId }}-requirement" name="requirement" rows="2" maxlength="500" required>{{ $usesOldInput ? old('requirement', $requirement?->requirement) : ($requirement?->requirement ?? '') }}</textarea>
        @if ($usesOldInput)
            @error('requirement')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $formId }}-type">Type</label>
        <select class="form-select" id="{{ $formId }}-type" name="requirement_type" required>
            @foreach ($requirementTypes as $key => $label)
                <option value="{{ $key }}" @selected($selectedType === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($usesOldInput)
            @error('requirement_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $formId }}-priority">Priority</label>
        <select class="form-select" id="{{ $formId }}-priority" name="priority" required>
            @foreach ($requirementPriorities as $key => $label)
                <option value="{{ $key }}" @selected($selectedPriority === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($usesOldInput)
            @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label" for="{{ $formId }}-status">Status</label>
        <select class="form-select" id="{{ $formId }}-status" name="status" required>
            @foreach ($requirementStatuses as $key => $label)
                <option value="{{ $key }}" @selected($selectedStatus === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($usesOldInput)
            @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $formId }}-module">Module</label>
        <select class="form-select" id="{{ $formId }}-module" name="project_module_id">
            <option value="">Unassigned</option>
            @foreach ($modules as $module)
                <option value="{{ $module->id }}" @selected($selectedModule === (string) $module->id)>{{ $module->name }}</option>
            @endforeach
        </select>
        @if ($usesOldInput)
            @error('project_module_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $formId }}-parent">Parent requirement</label>
        <select class="form-select" id="{{ $formId }}-parent" name="parent_requirement_id">
            <option value="">None</option>
            @foreach ($requirements->where('id', '!=', $requirement?->id) as $parentRequirement)
                <option value="{{ $parentRequirement->id }}" @selected($selectedParent === (string) $parentRequirement->id)>
                    {{ $parentRequirement->code }} · {{ $parentRequirement->requirement }}
                </option>
            @endforeach
        </select>
        @if ($usesOldInput)
            @error('parent_requirement_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $formId }}-story">User story <span class="text-body-secondary">(optional)</span></label>
        <textarea class="form-control" id="{{ $formId }}-story" name="user_story" rows="3" placeholder="As a [user], I want [capability] so that [outcome].">{{ $usesOldInput ? old('user_story', $requirement?->user_story) : ($requirement?->user_story ?? '') }}</textarea>
        @if ($usesOldInput)
            @error('user_story')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label" for="{{ $formId }}-criteria">Acceptance criteria <span class="text-body-secondary">(optional)</span></label>
        <textarea class="form-control" id="{{ $formId }}-criteria" name="acceptance_criteria" rows="3" placeholder="One testable criterion per line">{{ $usesOldInput ? old('acceptance_criteria', $requirement?->acceptance_criteria) : ($requirement?->acceptance_criteria ?? '') }}</textarea>
        @if ($usesOldInput)
            @error('acceptance_criteria')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @endif
    </div>
</div>
