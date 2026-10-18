<div class="card"><div class="card-body">
    <div class="mb-3"><label class="form-label" for="department-name">Department name</label><input class="form-control" id="department-name" name="name" value="{{ old('name', $department->name) }}" required maxlength="255"></div>
    <div><label class="form-label" for="department-description">Description</label><textarea class="form-control" id="department-description" name="description" rows="3" maxlength="2000">{{ old('description', $department->description) }}</textarea></div>
</div></div>
