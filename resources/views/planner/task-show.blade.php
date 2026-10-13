@extends('layouts.app')

@section('title', $task->title)

@section('content')
    <div class="container-fluid py-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="{{ route('planner.projects') }}">This project</a></li>
                <li class="breadcrumb-item"><a href="{{ route('planner.index', ['project' => $task->project_id, 'view' => 'list']) }}">Tasks</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $task->title }}</li>
            </ol>
        </nav>
        @if (session('status'))<div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>@endif
        @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="row g-3">
            <div class="col-xl-8">
                <section class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                            <div><p class="text-primary fw-bold text-uppercase fs-10 mb-1">{{ $task->project->name }} · {{ $categories[$task->category] }}</p><h1 class="h2 mb-2">{{ $task->title }}</h1><p class="text-700 mb-0">Created by {{ $task->creator->name }} · {{ $task->created_at->format('M j, Y') }}</p></div>
                            @if ($canUpdateProgress)
                                <form method="POST" action="{{ route('planner.update-status', $task) }}" class="d-flex gap-2 align-items-center">
                                    @csrf
                                    @method('PATCH')
                                    <label class="visually-hidden" for="task-status">Task status</label>
                                    <select class="form-select form-select-sm" name="status" id="task-status">@foreach ($statuses as $status => $label)<option value="{{ $status }}" @selected($task->status === $status)>{{ $label }}</option>@endforeach</select>
                                    <button class="btn btn-sm btn-primary" type="submit">Update</button>
                                </form>
                            @else
                                <span class="badge rounded-pill badge-subtle-primary">{{ $allStatuses[$task->status] }}</span>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <span class="badge rounded-pill badge-subtle-{{ $task->priority === 'urgent' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'info') }}">{{ $priorities[$task->priority] }} priority</span>
                            <span class="badge rounded-pill badge-subtle-secondary">{{ $task->visibility === 'team' ? 'Team task' : 'Private task' }}</span>
                            @if ($task->due_date)<span class="badge rounded-pill badge-subtle-{{ $task->status !== 'done' && $task->due_date->lt(today()) ? 'danger' : 'secondary' }}">Due {{ $task->due_date->format('M j, Y') }}</span>@endif
                        </div>
                        <h2 class="h5">What needs to be done</h2>
                        <div class="text-700">{!! nl2br(e($task->description ?: 'No additional details have been provided.')) !!}</div>
                    </div>
                </section>

                <section class="card" id="discussion">
                    <div class="card-header d-flex justify-content-between align-items-center"><h2 class="h5 mb-0">Task discussion</h2><span class="badge badge-subtle-primary">{{ $comments->count() }} replies</span></div>
                    <div class="card-body">
                        <p class="text-600 fs-10">Keep questions, decisions, and progress updates with the task so everyone can follow the conversation.</p>
                        <div class="timeline-vertical">
                            @forelse ($comments as $comment)
                                <div class="timeline-item">
                                    <div class="row g-3">
                                        <div class="col-auto"><div class="avatar avatar-l"><div class="avatar-name rounded-circle bg-body-secondary"><span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($comment->author->name, 0, 1)) }}</span></div></div></div>
                                        <div class="col">
                                            <div class="d-flex flex-wrap justify-content-between gap-2"><strong>{{ $comment->author->name }}</strong><span class="text-600 fs-10">{{ $comment->created_at->format('M j, Y · g:i A') }}</span></div>
                                            <p class="text-800 mt-2 mb-3">{!! nl2br(e($comment->body)) !!}</p>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-600 py-4"><span class="far fa-comments fs-2 d-block mb-2"></span>No replies yet. Start the conversation below.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="card-footer bg-body-tertiary">
                        <form method="POST" action="{{ route('planner.comments.store', $task) }}">
                            @csrf
                            <label class="form-label fw-semi-bold" for="comment-body">Add a reply</label>
                            <textarea class="form-control mb-2" id="comment-body" name="body" rows="3" maxlength="5000" required placeholder="Share an update, ask a question, or add a decision...">{{ old('body') }}</textarea>
                            <div class="d-flex justify-content-between align-items-center"><span class="text-600 fs-10">Replies are visible to people who can access this task.</span><button class="btn btn-primary" type="submit"><span class="fas fa-paper-plane me-2"></span>Send reply</button></div>
                        </form>
                    </div>
                </section>
            </div>
            <div class="col-xl-4">
                <section class="card mb-3">
                    <div class="card-header"><h2 class="h5 mb-0">Task details</h2></div>
                    <div class="card-body">
                        <dl class="row mb-0 fs-10">
                            <dt class="col-5 text-600">Assignee</dt><dd class="col-7">{{ $task->assignee->name ?? 'Unassigned' }}</dd>
                            <dt class="col-5 text-600">Priority</dt><dd class="col-7">{{ $priorities[$task->priority] }}</dd>
                            <dt class="col-5 text-600">Due date</dt><dd class="col-7">{{ $task->due_date?->format('M j, Y') ?? 'Not set' }}</dd>
                            <dt class="col-5 text-600">Last updated</dt><dd class="col-7">{{ $task->updated_at->diffForHumans() }}</dd>
                        </dl>
                    </div>
                </section>
                @if ($canManage)
                    <button class="btn btn-primary w-100 mb-3" type="button" data-bs-toggle="modal" data-bs-target="#editTaskModal"><span class="fas fa-edit me-2"></span>Edit task</button>
                    <button class="btn btn-falcon-danger w-100" type="button" data-bs-toggle="modal" data-bs-target="#deleteTaskModal"><span class="fas fa-trash-alt me-2"></span>Delete task</button>
                    <div class="modal fade" id="editTaskModal" tabindex="-1" aria-labelledby="editTaskModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                            <form method="POST" action="{{ route('planner.update', $task) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="ui_modal" value="editTaskModal">
                                <input type="hidden" name="project_id" value="{{ $task->project_id }}">
                                <div class="modal-header"><h2 class="modal-title fs-5" id="editTaskModalLabel">Edit task</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                <div class="modal-body">
                                <div class="mb-3"><label class="form-label" for="edit-title">Title</label><input class="form-control" id="edit-title" name="title" value="{{ old('title', $task->title) }}" maxlength="180" required></div>
                                <div class="mb-3"><label class="form-label" for="edit-description">Description</label><textarea class="form-control" id="edit-description" name="description" rows="4" maxlength="3000">{{ old('description', $task->description) }}</textarea></div>
                                <div class="mb-3"><label class="form-label" for="edit-category">Task type</label><select class="form-select" id="edit-category" name="category">@foreach ($categories as $category => $label)<option value="{{ $category }}" @selected($task->category === $category)>{{ $label }}</option>@endforeach</select></div>
                                <div class="mb-3"><label class="form-label" for="edit-priority">Priority</label><select class="form-select" id="edit-priority" name="priority">@foreach ($priorities as $priority => $label)<option value="{{ $priority }}" @selected($task->priority === $priority)>{{ $label }}</option>@endforeach</select></div>
                                <div class="mb-3"><label class="form-label" for="edit-assignee">Assignee</label><select class="form-select" id="edit-assignee" name="assigned_to"><option value="">Unassigned</option>                                @foreach ($members as $member)<option value="{{ $member->id }}" @selected((int) $task->assigned_to === (int) $member->id)>{{ $member->name }}</option>@endforeach</select></div>
                                <div class="mb-3"><label class="form-label" for="edit-due-date">Due date</label><input class="form-control" id="edit-due-date" type="date" name="due_date" value="{{ $task->due_date?->format('Y-m-d') }}"></div>
                                <div class="mb-3"><label class="form-label" for="edit-visibility">Visibility</label><select class="form-select" id="edit-visibility" name="visibility"><option value="team" @selected($task->visibility === 'team')>Team</option><option value="personal" @selected($task->visibility === 'personal')>Private</option></select></div>
                                </div>
                                <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit"><span class="fas fa-save me-2"></span>Save changes</button></div>
                            </form>
                    </div></div></div>
                    <div class="modal fade" id="deleteTaskModal" tabindex="-1" aria-labelledby="deleteTaskModalLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                            <form method="POST" action="{{ route('planner.destroy', $task) }}">
                                @csrf
                                @method('DELETE')
                                <div class="modal-header"><h2 class="modal-title fs-5" id="deleteTaskModalLabel">Delete task?</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                <div class="modal-body">This will permanently remove “{{ $task->title }}” and its discussion.</div>
                                <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" type="submit">Delete task</button></div>
                            </form>
                    </div></div></div>
                @endif
            </div>
        </div>
    </div>
    @push('scripts')
        <script>document.addEventListener('DOMContentLoaded',function(){var modal=document.getElementById(@json(old('ui_modal')));if(modal){bootstrap.Modal.getOrCreateInstance(modal).show();}});</script>
    @endpush
@endsection
