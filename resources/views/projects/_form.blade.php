<form method="POST" action="{{ $action }}" class="card">
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

        <div class="mb-3">
            <label class="form-label" for="description">Description <span class="text-body-secondary">(optional)</span></label>
            <textarea class="form-control" id="description" name="description" rows="4" maxlength="5000">{{ old('description', $project->description) }}</textarea>
            @error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="customer_id">Linked customer <span class="text-body-secondary">(optional)</span></label>
            <select class="form-select" id="customer_id" name="customer_id">
                <option value="">No customer linked</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}"
                        @selected((string) old('customer_id', $project->customers->first()?->id) === (string) $customer->id)>
                        {{ $customer->name }}{{ $customer->project_id && $customer->project_id !== $project->id ? ' · currently linked to another project' : '' }}
                    </option>
                @endforeach
            </select>
            @error('customer_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            <div class="form-text">Selecting a customer moves their project link here. Choosing “No customer linked” clears this project’s customer link.</div>
        </div>
    </div>
    <div class="card-footer bg-body d-flex justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="{{ isset($project->id) ? route('projects.show', $project) : route('projects.index') }}">Cancel</a>
        <button class="btn btn-primary" type="submit">{{ $buttonLabel }}</button>
    </div>
</form>
