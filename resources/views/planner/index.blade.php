@extends('layouts.app')

@section('title', 'Planning & Progress')

@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <p class="text-primary fw-bold text-uppercase fs-10 mb-1">{{ $selectedProject->name }}</p>
                <h1 class="h2 mb-1">Project tasks</h1>
                <p class="text-700 mb-0">Plan, assign, and discuss the work that moves this project forward.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ $selectedProject->slug === 'omg-computer' ? route('planner.projects') : route('projects.show', $selectedProject) }}" class="btn btn-falcon-default btn-sm">
                    <span class="fas fa-folder-open me-2"></span>Project overview
                </a>
                @can('planner.manage-team-tasks')
                    <span class="badge badge-subtle-primary rounded-pill px-3 py-2">Planner administrator</span>
                @endcan
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <p class="fw-bold mb-1">Please check the task details:</p>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="row g-3 mb-4" id="progress">
            <div class="col-6 col-xl">
                <div class="card h-100"><div class="card-body">
                    <p class="text-600 text-uppercase fs-10 fw-bold mb-2">Visible tasks</p>
                    <p class="h3 mb-0">{{ $progress['total'] }}</p>
                </div></div>
            </div>
            <div class="col-6 col-xl">
                <div class="card h-100"><div class="card-body">
                    <p class="text-600 text-uppercase fs-10 fw-bold mb-2">In progress</p>
                    <p class="h3 text-primary mb-0">{{ $progress['active'] }}</p>
                </div></div>
            </div>
            <div class="col-6 col-xl">
                <div class="card h-100"><div class="card-body">
                    <p class="text-600 text-uppercase fs-10 fw-bold mb-2">Blocked</p>
                    <p class="h3 text-warning mb-0">{{ $progress['blocked'] }}</p>
                </div></div>
            </div>
            <div class="col-6 col-xl">
                <div class="card h-100"><div class="card-body">
                    <p class="text-600 text-uppercase fs-10 fw-bold mb-2">Overdue</p>
                    <p class="h3 text-danger mb-0">{{ $progress['overdue'] }}</p>
                </div></div>
            </div>
            <div class="col-12 col-xl">
                <div class="card h-100"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <p class="text-600 text-uppercase fs-10 fw-bold mb-0">Completed</p>
                        <span class="fw-bold">{{ $progress['percent'] }}%</span>
                    </div>
                    <div class="progress" role="progressbar" aria-label="Visible task completion" aria-valuenow="{{ $progress['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-success" style="width: {{ $progress['percent'] }}%"></div>
                    </div>
                    <p class="fs-10 text-600 mt-2 mb-0">{{ $progress['done'] }} of {{ $progress['total'] }} tasks done</p>
                </div></div>
            </div>
        </section>

        <section class="card mb-4">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h2 class="h5 mb-1">Project tasks</h2>
                    <p class="text-600 fs-10 mb-0">Track features, operations, general tasks, and milestones.</p>
                </div>
                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#newTaskModal">
                    <span class="fas fa-plus me-2"></span>New task
                </button>
            </div>
            <div class="modal fade" id="newTaskModal" tabindex="-1" aria-labelledby="newTaskModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <form method="POST" action="{{ route('planner.store') }}">
                    @csrf
                    <input type="hidden" name="ui_modal" value="newTaskModal">
                    <input type="hidden" name="project_id" value="{{ $projectId }}">
                    <div class="modal-header"><h2 class="modal-title fs-5" id="newTaskModalLabel">Create project task</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                    <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label" for="new-title">Task or milestone</label>
                            <input class="form-control" id="new-title" name="title" value="{{ old('title') }}" maxlength="180" required placeholder="e.g. Prepare the next release">
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label" for="new-category">Type</label>
                            <select class="form-select" id="new-category" name="category" required>
                                @foreach ($categories as $value => $label)
                                    <option value="{{ $value }}" @selected(old('category', 'task') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label" for="new-status">Status</label>
                            <select class="form-select" id="new-status" name="status" required>
                                @foreach ($allStatuses as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', 'backlog') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label" for="new-priority">Priority</label>
                            <select class="form-select" id="new-priority" name="priority" required>
                                @foreach ($priorities as $value => $label)
                                    <option value="{{ $value }}" @selected(old('priority', 'medium') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label" for="new-visibility">Visibility</label>
                            <select class="form-select" id="new-visibility" name="visibility" required>
                                @foreach ($visibilities as $value => $label)
                                    <option value="{{ $value }}" @selected(old('visibility', 'team') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label" for="new-assignee">Assign to <span class="text-600">(team tasks)</span></label>
                            <select class="form-select" id="new-assignee" name="assigned_to">
                                <option value="">Unassigned</option>
                                @foreach ($teamMembers as $teamMember)
                                    <option value="{{ $teamMember->id }}" @selected((string) old('assigned_to') === (string) $teamMember->id)>{{ $teamMember->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-6 col-lg-3">
                            <label class="form-label" for="new-due-date">Due date</label>
                            <input class="form-control" id="new-due-date" type="date" name="due_date" value="{{ old('due_date') }}">
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label" for="new-description">Description <span class="text-600">(optional)</span></label>
                            <input class="form-control" id="new-description" name="description" value="{{ old('description') }}" maxlength="3000" placeholder="Add a little more detail">
                        </div>
                    </div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit"><span class="fas fa-plus me-2"></span>Add task</button></div>
                </form>
            </div></div></div>
        </section>

        <section>
            <div class="card mb-3">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 mb-1">Tasks</h2>
                    <p class="text-600 fs-10 mb-0">{{ $tasks->count() }} tasks in this view</p>
                </div>
                <div class="btn-group" role="group" aria-label="Task view">
                    <a class="btn btn-sm {{ $viewStyle === 'list' ? 'btn-primary' : 'btn-falcon-default' }}" href="{{ route('planner.index', array_merge(request()->query(), ['project' => $projectId, 'view' => 'list'])) }}"><span class="fas fa-list me-1"></span>List</a>
                    <a class="btn btn-sm {{ $viewStyle === 'kanban' ? 'btn-primary' : 'btn-falcon-default' }}" href="{{ route('planner.index', array_merge(request()->query(), ['project' => $projectId, 'view' => 'kanban'])) }}"><span class="fas fa-columns me-1"></span>Kanban</a>
                </div>
                <div class="dropdown">
                <button class="btn btn-sm btn-falcon-default dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><span class="fas fa-filter me-1"></span>Filters</button>
                <div class="dropdown-menu dropdown-menu-end p-3" style="min-width: 18rem">
                <form method="GET" action="{{ route('planner.index') }}" class="d-flex flex-column gap-2">
                    <input type="hidden" name="project" value="{{ $projectId }}">
                    <input type="hidden" name="view" value="{{ $viewStyle }}">
                    <select class="form-select form-select-sm" name="scope" aria-label="Filter by visibility">
                        <option value="all" @selected($scope === 'all')>All visible tasks</option>
                        <option value="team" @selected($scope === 'team')>Team tasks</option>
                        <option value="personal" @selected($scope === 'personal')>My personal tasks</option>
                    </select>
                    <select class="form-select form-select-sm" name="type" aria-label="Filter by task type">
                        <option value="">All types</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm" name="status" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\PlanningTask::STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm" name="assignee" aria-label="Filter by assignee">
                        <option value="">All assignees</option>
                        @foreach ($teamMembers as $member)
                            <option value="{{ $member->id }}" @selected((int) $assigneeFilter === (int) $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-falcon-default" type="submit"><span class="fas fa-filter me-1"></span>Filter</button>
                    <a class="btn btn-sm btn-falcon-default" href="{{ route('planner.index', ['project' => $projectId, 'view' => $viewStyle]) }}">Clear</a>
                </form></div></div>
            </div>
            </div>

            @if ($viewStyle === 'list')
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive scrollbar">
                            <table class="table table-sm table-hover mb-0 fs-10" data-datatables='{"pageLength":10,"order":[[5,"asc"]],"language":{"emptyTable":"No tasks match this view. Add a task to get this project moving."}}'>
                                <thead class="bg-body-tertiary">
                                    <tr><th class="ps-3">Task</th><th>Type</th><th>Status</th><th>Priority</th><th>Assignee</th><th>Due date</th><th>Discussion</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($tasks as $task)
                                        @php
                                            $statusTone = match ($task->status) {
                                                'done' => 'success',
                                                'blocked' => 'danger',
                                                'in_progress' => 'primary',
                                                'planned' => 'info',
                                                default => 'secondary',
                                            };
                                            $priorityTone = match ($task->priority) {
                                                'urgent' => 'danger',
                                                'high' => 'warning',
                                                'low' => 'secondary',
                                                default => 'info',
                                            };
                                        @endphp
                                        <tr>
                                            <td class="ps-3"><a class="fw-semi-bold" href="{{ route('planner.show', $task) }}">{{ $task->title }}</a><div class="text-600">{{ $task->creator->name }}</div></td>
                                            <td>{{ $categories[$task->category] }}</td>
                                            <td><span class="badge rounded-pill badge-subtle-{{ $statusTone }}">{{ $allStatuses[$task->status] }}</span></td>
                                            <td><span class="badge rounded-pill badge-subtle-{{ $priorityTone }}">{{ $priorities[$task->priority] }}</span></td>
                                            <td>{{ $task->assignee->name ?? 'Unassigned' }}</td>
                                            <td class="{{ $task->status !== 'done' && $task->due_date?->lt(today()) ? 'text-danger fw-bold' : '' }}">{{ $task->due_date?->format('M j, Y') ?? '—' }}</td>
                                            <td><a class="text-nowrap" href="{{ route('planner.show', $task) }}#discussion"><span class="fas fa-comments me-1"></span>{{ $task->comments_count }}</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
            <div class="row flex-nowrap g-3 overflow-auto pb-3">
                @foreach ($statuses as $statusKey => $statusLabel)
                    @php
                        $columnTasks = $tasksByStatus->get($statusKey, collect());
                    @endphp
                    <div class="col-11 col-md-6 col-xl-4 col-xxl-3 flex-shrink-0">
                        <div class="card h-100 bg-body-tertiary">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h3 class="h6 mb-0">{{ $statusLabel }}</h3>
                                <span class="badge rounded-pill bg-200 text-800">{{ $columnTasks->count() }}</span>
                            </div>
                            <div class="card-body p-2">
                                @forelse ($columnTasks as $task)
                                    @php
                                        $canManage = $task->user_id === auth()->id()
                                            || ($task->visibility === 'team' && auth()->user()->can('planner.manage-team-tasks'));
                                        $priorityBadge = match ($task->priority) {
                                            'urgent' => 'danger',
                                            'high' => 'warning',
                                            'low' => 'secondary',
                                            default => 'primary',
                                        };
                                    @endphp
                                    <article class="card mb-2 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <h4 class="h6 mb-2"><a class="text-900" href="{{ route('planner.show', $task) }}">{{ $task->title }}</a></h4>
                                                <span class="badge badge-subtle-{{ $priorityBadge }}">{{ $priorities[$task->priority] }}</span>
                                            </div>
                                            @if ($task->description)
                                                <p class="fs-10 text-700 mb-3">{{ $task->description }}</p>
                                            @endif
                                            <div class="d-flex flex-wrap gap-2 mb-3">
                                                <span class="badge badge-subtle-info">{{ $categories[$task->category] }}</span>
                                                <span class="badge badge-subtle-{{ $task->visibility === 'team' ? 'success' : 'secondary' }}">
                                                    {{ $visibilities[$task->visibility] }}
                                                </span>
                                                @if ($task->project)
                                                    <span class="badge badge-subtle-primary">{{ $task->project->name }}</span>
                                                @endif
                                            </div>
                                            <div class="fs-10 text-600 mb-3">
                                                <div>Created by {{ $task->creator->name }}</div>
                                                @if ($task->assignee)
                                                    <div>Assigned to {{ $task->assignee->name }}</div>
                                                @endif
                                                @if ($task->due_date)
                                                    <div class="{{ $task->status !== 'done' && $task->due_date->lt(today()) ? 'text-danger fw-bold' : '' }}">
                                                        Due {{ $task->due_date->format('M j, Y') }}
                                                    </div>
                                                @endif
                                            </div>
                                            <a class="btn btn-sm btn-falcon-default mb-2" href="{{ route('planner.show', $task) }}#discussion">
                                                <span class="fas fa-comments me-1"></span>Details &amp; discussion ({{ $task->comments_count }})
                                            </a>

                                            @if ($canManage)
                                                <form method="POST" action="{{ route('planner.update-status', $task) }}" class="d-flex gap-2 mb-2">
                                                    @csrf
                                                    @method('PATCH')
                                                    <label class="visually-hidden" for="task-status-{{ $task->id }}">Update status for {{ $task->title }}</label>
                                                    <select class="form-select form-select-sm" id="task-status-{{ $task->id }}" name="status">
                                                        @foreach (\App\Models\PlanningTask::STATUSES as $value => $label)
                                                            <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button class="btn btn-sm btn-outline-primary flex-shrink-0" type="submit">Update</button>
                                                </form>
                                                <button class="btn btn-sm btn-falcon-default me-1" type="button" data-bs-toggle="modal" data-bs-target="#editTaskModal{{ $task->id }}">Edit</button>
                                                <button class="btn btn-sm btn-falcon-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteTaskModal{{ $task->id }}">Delete</button>
                                                <div class="modal fade" id="editTaskModal{{ $task->id }}" tabindex="-1" aria-labelledby="editTaskModalLabel{{ $task->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                                                    <form method="POST" action="{{ route('planner.update', $task) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="ui_modal" value="editTaskModal{{ $task->id }}">
                                                        <input type="hidden" name="project_id" value="{{ $projectId }}">
                                                        <div class="modal-header"><h2 class="modal-title fs-5" id="editTaskModalLabel{{ $task->id }}">Edit task</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                                        <div class="modal-body">
                                                        <label class="form-label fs-10" for="edit-title-{{ $task->id }}">Title</label>
                                                        <input class="form-control mb-2" id="edit-title-{{ $task->id }}" name="title" value="{{ $task->title }}" maxlength="180" required>
                                                        <label class="form-label fs-10" for="edit-description-{{ $task->id }}">Description</label>
                                                        <textarea class="form-control mb-2" id="edit-description-{{ $task->id }}" name="description" rows="2" maxlength="3000">{{ $task->description }}</textarea>
                                                        <label class="form-label fs-10" for="edit-category-{{ $task->id }}">Type</label>
                                                        <select class="form-select mb-2" id="edit-category-{{ $task->id }}" name="category" required>
                                                            @foreach ($categories as $value => $label)
                                                                <option value="{{ $value }}" @selected($task->category === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label class="form-label fs-10" for="edit-status-{{ $task->id }}">Status</label>
                                                        <select class="form-select mb-2" id="edit-status-{{ $task->id }}" name="status" required>
                                                            @foreach (\App\Models\PlanningTask::STATUSES as $value => $label)
                                                                <option value="{{ $value }}" @selected($task->status === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label class="form-label fs-10" for="edit-priority-{{ $task->id }}">Priority</label>
                                                        <select class="form-select mb-2" id="edit-priority-{{ $task->id }}" name="priority" required>
                                                            @foreach ($priorities as $value => $label)
                                                                <option value="{{ $value }}" @selected($task->priority === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label class="form-label fs-10" for="edit-visibility-{{ $task->id }}">Visibility</label>
                                                        <select class="form-select mb-2" id="edit-visibility-{{ $task->id }}" name="visibility" required>
                                                            @foreach ($visibilities as $value => $label)
                                                                <option value="{{ $value }}" @selected($task->visibility === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label class="form-label fs-10" for="edit-assignee-{{ $task->id }}">Assignee</label>
                                                        <select class="form-select mb-2" id="edit-assignee-{{ $task->id }}" name="assigned_to">
                                                            <option value="">Unassigned</option>
                                                            @foreach ($teamMembers as $teamMember)
                                                                <option value="{{ $teamMember->id }}" @selected($task->assigned_to === $teamMember->id)>{{ $teamMember->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label class="form-label fs-10" for="edit-due-date-{{ $task->id }}">Due date</label>
                                                        <input class="form-control mb-3" id="edit-due-date-{{ $task->id }}" type="date" name="due_date" value="{{ $task->due_date?->format('Y-m-d') }}">
                                                        </div>
                                                        <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save changes</button></div>
                                                    </form>
                                                </div></div></div>
                                                <div class="modal fade" id="deleteTaskModal{{ $task->id }}" tabindex="-1" aria-labelledby="deleteTaskModalLabel{{ $task->id }}" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                                                <form method="POST" action="{{ route('planner.destroy', $task) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-header"><h2 class="modal-title fs-5" id="deleteTaskModalLabel{{ $task->id }}">Delete task?</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                                                    <div class="modal-body">This will permanently remove “{{ $task->title }}” and its discussion.</div>
                                                    <div class="modal-footer"><button class="btn btn-falcon-default" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger" type="submit">Delete task</button></div>
                                                </form>
                                                </div></div></div>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <div class="text-center text-600 fs-10 py-4">No tasks in this status.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @endif
        </section>
    </div>
    @push('scripts')
        <script>document.addEventListener('DOMContentLoaded',function(){var modal=document.getElementById(@json(old('ui_modal')));if(modal){bootstrap.Modal.getOrCreateInstance(modal).show();}});</script>
    @endpush
@endsection
