<form method="POST" action="{{ $action }}" class="card p-2">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    @if ($parent)
        <input type="hidden" name="parent_id" value="{{ $parent->id }}">
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

        @if ($parent)
            <div class="alert alert-info">
                Subtask of <a href="{{ route('tasks.show', $parent) }}">{{ $parent->title }}</a>
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label" for="title">Task name</label>
            <input class="form-control" id="title" name="title" value="{{ old('title', $task->title) }}"
                maxlength="255" required>
            @error('title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="description">Description <span class="text-body-secondary">(optional)</span></label>
            <textarea class="form-control" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $task->description) }}</textarea>
            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="assigned_to">Assignee</label>
                <select class="form-select" id="assigned_to" name="assigned_to">
                    <option value="">Unassigned</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((string) old('assigned_to', $task->assigned_to) === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('assigned_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status" required>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $task->status) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="priority">Priority</label>
                <select class="form-select" id="priority" name="priority" required>
                    @foreach ($priorities as $value => $label)
                        <option value="{{ $value }}" @selected(old('priority', $task->priority) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="due_date">Due date <span class="text-body-secondary">(optional)</span></label>
                <input class="form-control" id="due_date" name="due_date" type="date"
                    value="{{ old('due_date', $task->due_date?->format('Y-m-d')) }}">
                @error('due_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div class="card-footer bg-body d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ $cancelUrl }}">Cancel</a>
        <button class="btn btn-primary" type="submit">{{ $buttonLabel }}</button>
    </div>
</form>
