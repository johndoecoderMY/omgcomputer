<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $query = Task::query()
            ->with(['project', 'subtasks'])
            ->whereNull('parent_task_id');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('subtasks', fn ($subtasks) => $subtasks
                        ->where('title', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->where(function ($query) use ($status): void {
                $query->where('status', $status)
                    ->orWhereHas('subtasks', fn ($subtasks) => $subtasks->where('status', $status));
            });
        }

        if ($request->filled('priority')) {
            $priority = $request->input('priority');
            $query->where(function ($query) use ($priority): void {
                $query->where('priority', $priority)
                    ->orWhereHas('subtasks', fn ($subtasks) => $subtasks->where('priority', $priority));
            });
        }

        return view('tasks.index', [
            'tasks' => $query->latest()->get(),
            'statuses' => Task::STATUSES,
            'priorities' => Task::PRIORITIES,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
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

        $project->tasks()->create([
            ...$data,
            'parent_task_id' => $parent?->id,
        ]);

        return redirect()
            ->route($parent ? 'tasks.show' : 'projects.show', $parent ?? $project)
            ->with('success', $parent ? 'Subtask created successfully.' : 'Task created successfully.');
    }

    public function show(Task $task): View
    {
        $task->load(['parent', 'project', 'subtasks']);

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
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $data = $this->validatedData($request);
        unset($data['parent_id']);
        $task->update($data);

        return redirect()
            ->route('tasks.show', $task->parent ?? $task)
            ->with('success', 'Task updated successfully.');
    }

    public function storeSubtask(Request $request, Task $task): RedirectResponse
    {
        abort_if($task->parent_task_id !== null, 404);

        $data = $this->validatedData($request);
        unset($data['parent_id']);
        $task->subtasks()->create([
            ...$data,
            'project_id' => $task->project_id,
        ]);

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
                ? 'Task marked done' . ($task->parent_task_id === null ? '; all subtasks marked done.' : '.')
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
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(Task::PRIORITIES))],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'parent_id' => ['nullable', 'integer', 'exists:tasks,id'],
        ]);
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
}
