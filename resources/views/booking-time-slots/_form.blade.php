<div class="card"><div class="card-body row g-3">
    <div class="col-md-6"><label class="form-label" for="day_of_week">Day of week</label><select class="form-select" id="day_of_week" name="day_of_week" required>@foreach ($days as $value => $label)<option value="{{ $value }}" @selected((string) old('day_of_week', $slot->day_of_week ?? 1) === (string) $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label" for="start_time">Start time (Malaysia time)</label><input class="form-control" id="start_time" name="start_time" type="time" value="{{ old('start_time', isset($slot->start_time) ? substr($slot->start_time, 0, 5) : '') }}" required></div>
</div></div>
