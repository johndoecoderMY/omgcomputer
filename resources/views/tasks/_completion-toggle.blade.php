<form method="POST" action="{{ route('tasks.update-status', $task) }}" class="d-inline-flex align-items-center gap-2">
    @csrf
    @method('PATCH')
    <label class="form-check d-inline-flex align-items-center gap-2 mb-0">
        <input class="form-check-input mt-0" type="checkbox" name="done" value="1"
            aria-label="Mark {{ $task->title }} as done" @checked($task->status === 'done')
            onchange="this.form.requestSubmit()">
        <span class="small">{{ $task->status === 'done' ? 'Done' : 'Mark done' }}</span>
    </label>
    <noscript><button class="btn btn-sm btn-outline-primary" type="submit">Update</button></noscript>
</form>
