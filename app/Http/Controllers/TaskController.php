<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\WorkspaceNotifier;
use App\Support\StatusBadge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class TaskController extends Controller
{
    public function index(): View
    {
        return view('tasks.index', [
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
        ]);
    }

    public function datatable(Request $request)
    {
        $query = Task::query()
            ->with(['project', 'assignees'])
            ->withCount('subtasks')
            ->whereNull('parent_task_id');

        $status = $request->input('status');
        if (is_string($status) && isset(Task::STATUSES[$status])) {
            $query->where(fn (Builder $tasks) => $tasks
                ->where('status', $status)
                ->orWhereHas('subtasks', fn (Builder $subtasks) => $subtasks->where('status', $status)));
        }
        $priority = $request->input('priority');
        if (is_string($priority) && isset(Task::PRIORITIES[$priority])) {
            $query->where(fn (Builder $tasks) => $tasks
                ->where('priority', $priority)
                ->orWhereHas('subtasks', fn (Builder $subtasks) => $subtasks->where('priority', $priority)));
        }

        return DataTables::eloquent($query)
            ->filter(function (Builder $tasks) use ($request): void {
                $search = trim((string) $request->input('search.value', ''));
                if ($search === '') {
                    return;
                }

                $tasks->where(fn (Builder $matches) => $matches
                    ->where('tasks.title', 'like', "%{$search}%")
                    ->orWhere('tasks.description', 'like', "%{$search}%")
                    ->orWhereHas('project', fn (Builder $project) => $project->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('assignees', fn (Builder $assignees) => $assignees->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subtasks', fn (Builder $subtasks) => $subtasks->where('title', 'like', "%{$search}%")));
            }, true)
            ->addColumn('task', fn (Task $task): string => sprintf(
                '<a class="fw-semibold" href="%s">%s</a><div class="small text-body-secondary">%s</div>',
                e(route('tasks.show', $task)),
                e($task->title),
                e(Str::limit($task->description ?? '', 120)),
            ))
            ->addColumn('project_name', fn (Task $task): string => $task->project
                ? '<a href="'.e(route('projects.show', $task->project)).'">'.e($task->project->name).'</a>'
                : '—')
            ->addColumn('assignees', fn (Task $task): string => e($task->assignees->pluck('name')->join(', ') ?: 'Unassigned'))
            ->addColumn('status_label', fn (Task $task): string => (string) StatusBadge::make(
                $task->status,
                Task::STATUSES[$task->status] ?? $task->status,
            ))
            ->addColumn('priority_label', fn (Task $task): string => e(Task::PRIORITIES[$task->priority] ?? $task->priority))
            ->editColumn('due_date', fn (Task $task): string => $task->due_date?->format('M j, Y') ?? '—')
            ->addColumn('actions', function (Task $task) use ($request): string {
                $details = '<a class="btn btn-sm btn-outline-primary" href="'.e(route('tasks.show', $task)).'">Details</a>';
                $canManage = $request->user()->hasRole('admin')
                    || (int) $task->created_by_user_id === (int) $request->user()->id
                    || (int) $task->assigned_to === (int) $request->user()->id
                    || $task->assignees()->whereKey($request->user()->id)->exists();
                if (! $canManage) {
                    return $details;
                }

                return sprintf(
                    '<form method="POST" action="%s" class="d-inline"><input type="hidden" name="_token" value="%s"><input type="hidden" name="_method" value="PATCH"><input type="hidden" name="done" value="%s"><button class="btn btn-sm btn-outline-%s" type="submit">%s</button></form> %s <a class="btn btn-sm btn-outline-secondary" href="%s">Edit</a>',
                    e(route('tasks.update-status', $task)),
                    e(csrf_token()),
                    $task->status === 'done' ? '0' : '1',
                    $task->status === 'done' ? 'secondary' : 'success',
                    $task->status === 'done' ? 'Reopen' : 'Mark done',
                    $details,
                    e(route('tasks.edit', $task)),
                );
            })
            ->rawColumns(['task', 'project_name', 'status_label', 'actions'])
            ->toJson();
    }

    public function create(Request $request): View
    {
        return $this->createTaskForm($request);
    }

    public function createForProject(Request $request, Project $project): View
    {
        return $this->createTaskForm($request, $project);
    }

    private function createTaskForm(Request $request, ?Project $project = null): View
    {
        $parent = null;
        if ($request->filled('parent_id')) {
            $parentQuery = $project
                ? $project->tasks()
                : Task::query();
            $parent = $parentQuery->whereNull('parent_task_id')->findOrFail($request->integer('parent_id'));
            $project ??= $parent->project;
        }

        return view('tasks.create', [
            'project' => $project,
            'task' => new Task([
                'project_id' => $project?->id,
                'parent_task_id' => $parent?->id,
                'status' => 'backlog',
                'priority' => 'medium',
            ]),
            'parent' => $parent,
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
            'users' => auth()->user()->hasRole('admin') ? User::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, WorkspaceNotifier $notifier): RedirectResponse
    {
        return $this->storeTask($request, null, $notifier);
    }

    public function storeForProject(Request $request, Project $project, WorkspaceNotifier $notifier): RedirectResponse
    {
        return $this->storeTask($request, $project, $notifier);
    }

    private function storeTask(Request $request, ?Project $project, WorkspaceNotifier $notifier): RedirectResponse
    {
        $data = $this->validatedData($request);
        $parentId = $data['parent_id'] ?? null;
        $parent = $project
            ? $this->validatedParent($project, $parentId)
            : ($parentId ? Task::query()->whereNull('parent_task_id')->findOrFail($parentId) : null);
        unset($data['parent_id']);
        $assignees = $data['assignees'];
        unset($data['assignees']);
        if (! $request->user()->hasRole('admin')) {
            $assignees = [];
        }

        if ($parent) {
            $project = $parent->project;
        } elseif (! $project && ! empty($data['project_id'])) {
            $project = Project::query()->findOrFail($data['project_id']);
        }
        unset($data['project_id']);

        $task = Task::query()->create([
            ...$data,
            'project_id' => $project?->id,
            'parent_task_id' => $parent?->id,
            'created_by_user_id' => $request->user()->id,
        ]);
        $newAssignees = $this->syncAssignees($task, $assignees);
        $this->notifyTaskAssignees($task, $newAssignees, $request->user(), $notifier, 'Task assigned to you');
        $this->notifyTaskCreator($task, $request->user(), $notifier, 'Task created');

        return redirect()
            ->route($parent ? 'tasks.show' : ($project ? 'projects.show' : 'tasks.show'), $parent ?? $project ?? $task)
            ->with('success', $parent ? 'Subtask created successfully.' : 'Task created successfully.');
    }

    public function show(Request $request, Task $task): View
    {
        $task->load(['parent', 'project', 'assignees', 'subtasks.assignees', 'attachments.uploader']);

        $canManageTask = $request->user()->hasRole('admin')
            || (int) $task->created_by_user_id === (int) $request->user()->id
            || (int) $task->assigned_to === (int) $request->user()->id
            || $task->assignees->contains('id', $request->user()->id);

        return view('tasks.show', compact('task', 'canManageTask'));
    }

    public function edit(Task $task): View
    {
        $task->load(['parent', 'project', 'assignees']);

        return view('tasks.edit', [
            'project' => $task->project,
            'task' => $task,
            'parent' => $task->parent,
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
            'users' => auth()->user()->hasRole('admin') ? User::query()->orderBy('name')->get(['id', 'name']) : collect(),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Task $task, WorkspaceNotifier $notifier): RedirectResponse
    {
        $task->loadMissing(['creator', 'assignees']);
        $existingAssigneeIds = $task->assignees->modelKeys();
        $data = $this->validatedData($request);
        unset($data['parent_id']);
        $assignees = $request->user()->hasRole('admin') ? $data['assignees'] : $existingAssigneeIds;
        $projectId = array_key_exists('project_id', $data) ? $data['project_id'] : $task->project_id;
        unset($data['assignees'], $data['project_id']);
        if ($task->parent_task_id === null) {
            $task->project_id = $projectId;
            $task->subtasks()->update(['project_id' => $projectId]);
        }
        $task->update($data);
        $newAssignees = $this->syncAssignees($task, $assignees);
        $this->notifyTaskAssignees($task, $newAssignees, $request->user(), $notifier, 'Task assigned to you');
        $recipients = collect([$task->creator])
            ->merge($existingAssigneeIds ? User::query()->whereKey($existingAssigneeIds)->get() : collect());
        $notifier->notifyUsers(
            $recipients,
            $request->user(),
            'Task updated',
            sprintf('Task “%s” was updated.', $task->title),
            route('tasks.show', $task),
        );

        return redirect()
            ->route('tasks.show', $task->parent ?? $task)
            ->with('success', 'Task updated successfully.');
    }

    public function storeSubtask(Request $request, Task $task, WorkspaceNotifier $notifier): RedirectResponse
    {
        abort_if($task->parent_task_id !== null, 404);

        $data = $this->validatedData($request);
        unset($data['parent_id'], $data['project_id']);
        $assignees = $data['assignees'];
        unset($data['assignees']);
        if (! $request->user()->hasRole('admin')) {
            $assignees = [];
        }
        $subtask = $task->subtasks()->create([
            ...$data,
            'project_id' => $task->project_id,
            'created_by_user_id' => $request->user()->id,
        ]);
        $newAssignees = $this->syncAssignees($subtask, $assignees);
        $this->notifyTaskAssignees($subtask, $newAssignees, $request->user(), $notifier, 'Task assigned to you');

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Subtask created successfully.');
    }

    public function updateStatus(Request $request, Task $task, WorkspaceNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'done' => ['sometimes', 'boolean'],
        ]);
        $status = $request->boolean('done') ? 'done' : 'backlog';
        $previousStatus = $task->status;

        DB::transaction(function () use ($task, $status): void {
            $task->update(['status' => $status]);

            if ($status === 'done' && $task->parent_task_id === null) {
                $task->subtasks()->update(['status' => 'done']);
            }
        });
        if ($previousStatus !== $status) {
            $task->load(['creator', 'assignees']);
            $notifier->notifyUsers(
                collect([$task->creator])->merge($task->assignees),
                $request->user(),
                $status === 'done' ? 'Task marked done' : 'Task reopened',
                sprintf('Task “%s” was marked %s.', $task->title, $status === 'done' ? 'done' : 'to do'),
                route('tasks.show', $task),
            );
            if ($status === 'done' && $task->parent_task_id === null) {
                foreach ($task->subtasks as $subtask) {
                    $subtask->load(['creator', 'assignees']);
                    $notifier->notifyUsers(
                        collect([$subtask->creator])->merge($subtask->assignees),
                        $request->user(),
                        'Task marked done',
                        sprintf('Task “%s” was marked done with its parent task.', $subtask->title),
                        route('tasks.show', $task),
                    );
                }
            }
        }

        return redirect()
            ->back()
            ->with('success', $status === 'done'
                ? 'Task marked done'.($task->parent_task_id === null ? '; all subtasks marked done.' : '.')
                : 'Task marked not done.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        $parent = $task->parent;
        $project = $task->project;
        $task->delete();

        return redirect()
            ->route($parent ? 'tasks.show' : ($project ? 'projects.show' : 'tasks.index'), $parent ?? $project)
            ->with('success', $parent ? 'Subtask deleted successfully.' : 'Task and its subtasks deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(Task::PRIORITIES))],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'assignees' => ['sometimes', 'array'],
            'assignees.*' => ['integer', 'distinct', 'exists:users,id'],
            'assignees_present' => ['sometimes', 'boolean'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'parent_id' => ['nullable', 'integer', 'exists:tasks,id'],
        ]);
        $assignees = $request->exists('assignees_present')
            ? ($data['assignees'] ?? [])
            : (array_key_exists('assignees', $data)
                ? $data['assignees']
                : (isset($data['assigned_to']) ? [(int) $data['assigned_to']] : []));
        unset($data['assigned_to'], $data['assignees_present']);
        $data['assignees'] = array_values(array_unique(array_map('intval', $assignees)));

        if (! empty($data['start_date']) && ! empty($data['due_date']) && $data['due_date'] < $data['start_date']) {
            throw ValidationException::withMessages([
                'due_date' => 'The due date must be on or after the planned start date.',
            ]);
        }

        return $data;
    }

    private function validatedParent(Project $project, ?int $parentId): ?Task
    {
        if ($parentId === null) {
            return null;
        }

        $parent = $project->tasks()->findOrFail($parentId);
        abort_if($parent->parent_task_id !== null, 422, 'Choose a top-level task as the parent.');

        return $parent;
    }

    private function syncAssignees(Task $task, array $assigneeIds): array
    {
        $newAssignees = array_diff($assigneeIds, $task->assignees()->pluck('users.id')->all());
        $task->assignees()->sync($assigneeIds);
        $task->update(['assigned_to' => $assigneeIds[0] ?? null]);

        return $newAssignees;
    }

    private function notifyTaskAssignees(
        Task $task,
        array $assigneeIds,
        User $actor,
        WorkspaceNotifier $notifier,
        string $title,
    ): void {
        if ($assigneeIds === []) {
            return;
        }
        $task->loadMissing('project');
        $notifier->notifyUsers(
            User::query()->whereKey($assigneeIds)->get(),
            $actor,
            $title,
            sprintf(
                'You have been assigned “%s”%s.',
                $task->title,
                $task->project ? ' in the '.$task->project->name.' project' : '',
            ),
            route('tasks.show', $task),
        );
    }

    private function notifyTaskCreator(Task $task, User $actor, WorkspaceNotifier $notifier, string $title): void
    {
        $task->loadMissing('creator');
        $notifier->notifyUsers(
            collect([$task->creator]),
            $actor,
            $title,
            sprintf('Task “%s” was created.', $task->title),
            route('tasks.show', $task),
        );
    }
}
