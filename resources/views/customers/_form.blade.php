<form method="POST" action="{{ $action }}" class="card p-2">
    @csrf
    @if ($method !== 'POST')@method($method)@endif
    <div class="card-body row g-3">
        @if ($errors->any())<div class="col-12"><div class="alert alert-danger mb-0"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
        <div class="col-md-6"><label class="form-label" for="name">Customer name</label><input class="form-control" id="name" name="name" value="{{ old('name', $customer->name) }}" required maxlength="255"></div>
        <div class="col-md-6"><label class="form-label" for="nickname">Nickname <span class="text-body-secondary">(optional)</span></label><input class="form-control" id="nickname" name="nickname" value="{{ old('nickname', $customer->nickname) }}" maxlength="255"></div>
        <div class="col-md-6"><label class="form-label" for="company">Company <span class="text-body-secondary">(optional)</span></label><input class="form-control" id="company" name="company" value="{{ old('company', $customer->company) }}" maxlength="255"></div>
        <div class="col-md-6"><label class="form-label" for="email">Email</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email', $customer->email) }}" maxlength="255"></div>
        <div class="col-md-6"><label class="form-label" for="phone">Phone</label><x-phone-input id="phone" name="phone" :value="old('phone', $customer->phone)" maxlength="64" /></div>
        <div class="col-md-6"><label class="form-label" for="project_id">Related project</label><select class="form-select js-project-search" id="project_id" name="project_id"><option value="">No project</option>@foreach ($projects as $project)<option value="{{ $project->id }}" @selected((string) old('project_id', $customer->project_id) === (string) $project->id)>{{ $project->name }}</option>@endforeach</select></div>
        <div class="col-md-12"><label class="form-label" for="address">Address</label><textarea class="form-control" id="address" name="address" rows="2" maxlength="3000">{{ old('address', $customer->address) }}</textarea></div>
        <div class="col-12">
            <label class="form-label" for="warning">Important customer warning <span class="text-body-secondary">(staff only)</span></label>
            <textarea class="form-control" id="warning" name="warning" rows="3" maxlength="5000"
                placeholder="Add a relevant safety, access, or handling warning for staff.">{{ old('warning', $customer->warning) }}</textarea>
            <div class="form-text">Shown prominently on this customer profile, bookings, and repair tickets.</div>
        </div>
        <div class="col-12"><label class="form-label" for="notes">Internal notes <span class="text-body-secondary">(staff only)</span></label><textarea class="form-control" id="notes" name="notes" rows="3" maxlength="5000">{{ old('notes', $customer->notes) }}</textarea></div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ $cancelUrl }}">Cancel</a><button class="btn btn-primary">{{ $buttonLabel }}</button></div>
</form>