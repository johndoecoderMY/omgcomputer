<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
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
            ->with(['project', 'assignee'])
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
                    ->orWhereHas('assignee', fn (Builder $assignee) => $assignee->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subtasks', fn (Builder $subtasks) => $subtasks->where('title', 'like', "%{$search}%")));
            }, true)
            ->addColumn('task', fn (Task $task): string => sprintf(
                '<a class="fw-semibold" href="%s">%s</a><div class="small text-body-secondary">%s</div>',
                e(route('tasks.show', $task)),
                e($task->title),
                e(Str::limit($task->description ?? '', 120)),
            ))
            ->addColumn('project_name', fn (Task $task): string => sprintf(
                '<a href="%s">%s</a>',
                e(route('projects.show', $task->project)),
                e($task->project->name),
            ))
            ->addColumn('status_label', fn (Task $task): string => (string) StatusBadge::make(
                $task->status,
                Task::STATUSES[$task->status] ?? $task->status,
            ))
            ->addColumn('priority_label', fn (Task $task): string => e(Task::PRIORITIES[$task->priority] ?? $task->priority))
            ->editColumn('due_date', fn (Task $task): string => $task->due_date?->format('M j, Y') ?? '—')
            ->addColumn('actions', fn (Task $task): string => sprintf(
                '<form method="POST" action="%s" class="d-inline"><input type="hidden" name="_token" value="%s"><input type="hidden" name="_method" value="PATCH"><input type="hidden" name="done" value="%s"><button class="btn btn-sm btn-outline-%s" type="submit">%s</button></form> <a class="btn btn-sm btn-outline-primary" href="%s">Details</a> <a class="btn btn-sm btn-outline-secondary" href="%s">Edit</a>',
                e(route('tasks.update-status', $task)),
                e(csrf_token()),
                $task->status === 'done' ? '0' : '1',
                $task->status === 'done' ? 'secondary' : 'success',
                $task->status === 'done' ? 'Reopen' : 'Mark done',
                e(route('tasks.show', $task)),
                e(route('tasks.edit', $task)),
            ))
            ->rawColumns(['task', 'project_name', 'status_label', 'actions'])
            ->toJson();
    }

    public function create(Project $project, Request $request): View
    {
        $parent = null;
        if ($request->filled('parent_id')) {
            $parent = $project->tasks()
                ->whereNull('parent_task_id')
                ->findOrFail($request->integer('parent_id'));
        }

        return view('tasks.create', [
            'project' => $project,
            'task' => new Task([
                'project_id' => $project->id,
                'parent_task_id' => $parent?->id,
                'status' => 'backlog',
                'priority' => 'medium',
            ]),
            'parent' => $parent,
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Project $project, Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $parent = $this->validatedParent($project, $data['parent_id'] ?? null);
        unset($data['parent_id']);

        $task = $project->tasks()->create([
            ...$data,
            'parent_task_id' => $parent?->id,
        ]);
        $this->notifyAssignee($task);

        return redirect()
            ->route($parent ? 'tasks.show' : 'projects.show', $parent ?? $project)
            ->with('success', $parent ? 'Subtask created successfully.' : 'Task created successfully.');
    }

    public function show(Task $task): View
    {
        $task->load(['parent', 'project', 'subtasks', 'attachments.uploader']);

        return view('tasks.show', compact('task'));
    }

    public function edit(Task $task): View
    {
        $task->load(['parent', 'project']);

        return view('tasks.edit', [
            'project' => $task->project,
            'task' => $task,
            'parent' => $task->parent,
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $data = $this->validatedData($request);
        unset($data['parent_id']);
        $assigneeChanged = (int) $task->assigned_to !== (int) ($data['assigned_to'] ?? 0);
        $task->update($data);
        if ($assigneeChanged) {
            $this->notifyAssignee($task);
        }

        return redirect()
            ->route('tasks.show', $task->parent ?? $task)
            ->with('success', 'Task updated successfully.');
    }

    public function storeSubtask(Request $request, Task $task): RedirectResponse
    {
        abort_if($task->parent_task_id !== null, 404);

        $data = $this->validatedData($request);
        unset($data['parent_id']);
        $subtask = $task->subtasks()->create([
            ...$data,
            'project_id' => $task->project_id,
        ]);
        $this->notifyAssignee($subtask);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'Subtask created successfully.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        $data = $request->validate([
            'done' => ['sometimes', 'boolean'],
        ]);
        $status = $request->boolean('done') ? 'done' : 'backlog';

        DB::transaction(function () use ($task, $status): void {
            $task->update(['status' => $status]);

            if ($status === 'done' && $task->parent_task_id === null) {
                $task->subtasks()->update(['status' => 'done']);
            }
        });

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
            ->route($parent ? 'tasks.show' : 'projects.show', $parent ?? $project)
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
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'parent_id' => ['nullable', 'integer', 'exists:tasks,id'],
        ]);

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

    private function notifyAssignee(Task $task): void
    {
        if (! $task->assigned_to || (int) $task->assigned_to === (int) auth()->id()) {
            return;
        }

        $task->loadMissing('project', 'assignee');
        $task->assignee?->notify(new WorkspaceNotification(
            'Task assigned to you',
            sprintf('You have been assigned “%s” in the %s project.', $task->title, $task->project->name),
            route('tasks.show', $task),
        ));
    }
}
