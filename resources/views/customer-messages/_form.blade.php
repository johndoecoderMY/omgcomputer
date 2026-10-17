<form class="card p-2" method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')@method($method)@endif
    <div class="card-body row g-3">
        @if ($errors->any())<div class="col-12"><div class="alert alert-danger mb-0"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
        <div class="col-md-6"><label class="form-label" for="name">Template name</label><input class="form-control" id="name" name="name" value="{{ old('name', $template->name) }}" maxlength="150" required></div>
        <div class="col-md-3"><label class="form-label" for="stage">Customer moment</label><select class="form-select" id="stage" name="stage" required>@foreach ($stages as $value => $label)<option value="{{ $value }}" @selected(old('stage', $template->stage) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="channel">Channel</label><select class="form-select" id="channel" name="channel" required>@foreach ($channels as $value => $label)<option value="{{ $value }}" @selected(old('channel', $template->channel) === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label" for="subject">Subject <span class="text-body-secondary">(email only)</span></label><input class="form-control" id="subject" name="subject" value="{{ old('subject', $template->subject) }}" maxlength="255"></div>
        <div class="col-12"><div class="d-flex justify-content-between align-items-center gap-3 mb-1"><label class="form-label mb-0" for="body">Message copy</label><span class="small text-body-secondary">Be clear, kind, and specific about next steps.</span></div><textarea class="form-control font-monospace" id="body" name="body" rows="12" maxlength="10000" required>{{ old('body', $template->body) }}</textarea></div>
        <div class="col-12"><div class="small fw-semibold mb-2">Available placeholders</div><div class="d-flex flex-wrap gap-2">@foreach ($placeholders as $key => $label)<button class="btn btn-sm btn-outline-secondary placeholder-button" type="button" data-placeholder="&#123;&#123;{{ $key }}&#125;&#125;" title="Insert {{ $label }}">&#123;&#123;{{ $key }}&#125;&#125;</button>@endforeach</div></div>
        <div class="col-12"><div class="form-check"><input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', $template->exists ? $template->is_active : true))><label class="form-check-label" for="is_active">Available for staff to use</label></div></div>
    </div>
    <div class="card-footer d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ $cancelUrl }}">Cancel</a><button class="btn btn-primary">{{ $buttonLabel }}</button></div>
</form>
@push('scripts')
<script>
document.querySelectorAll('.placeholder-button').forEach((button) => button.addEventListener('click', () => {
    const field = document.getElementById('body');
    const start = field.selectionStart;
    const end = field.selectionEnd;
    field.setRangeText(button.dataset.placeholder, start, end, 'end');
    field.focus();
}));
</script>
@endpush