<?php

namespace App\Http\Controllers;

use App\Models\PlanningProject;
use App\Models\PlanningTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanningTaskController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $requestedProject = $request->query('project');
        $projectId = is_string($requestedProject) || is_int($requestedProject)
            ? filter_var($requestedProject, FILTER_VALIDATE_INT)
            : false;
        $project = $projectId === false
            ? PlanningProject::omgComputer($user)
            : PlanningProject::query()
                ->whereKey($projectId)
                ->where(fn (Builder $query): Builder => $query
                    ->where('visibility', 'team')
                    ->orWhere(fn (Builder $private): Builder => $private
                        ->where('visibility', 'personal')
                        ->where('user_id', $user->id)))
                ->firstOrFail();
        $viewStyle = $request->query('view') === 'kanban' ? 'kanban' : 'list';
        $scope = in_array($request->query('scope'), ['team', 'personal'], true)
            ? $request->query('scope')
            : 'all';
        $categoryFilter = $request->query('type');
        $category = in_array($categoryFilter, array_keys(PlanningTask::CATEGORIES), true)
            ? $categoryFilter
            : null;
        $statusFilter = $request->query('status');
        $status = in_array($statusFilter, array_keys(PlanningTask::STATUSES), true)
            ? $statusFilter
            : null;
        $assignee = filter_var($request->query('assignee'), FILTER_VALIDATE_INT);
        $assigneeId = $assignee === false || $assignee < 1 ? null : $assignee;
        $overdue = $request->boolean('overdue');

        $query = PlanningTask::query()
            ->with(['creator:id,name', 'assignee:id,name', 'project:id,name,visibility', 'subtasks.assignee:id,name'])
            ->withCount('comments')
            ->where('project_id', $project->id)
            ->where(function (Builder $query) use ($user): void {
                $query->where('visibility', 'team')
                    ->orWhere(function (Builder $query) use ($user): void {
                        $query->where('visibility', 'personal')
                            ->where('user_id', $user->id);
                    });
            });

        if ($scope === 'team') {
            $query->where('visibility', 'team');
        } elseif ($scope === 'personal') {
            $query->where('visibility', 'personal')
                ->where('user_id', $user->id);
        }

        if ($category !== null) {
            $query->where('category', $category);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($assigneeId !== null) {
            $query->where('assigned_to', $assigneeId)
                ->where('visibility', 'team');
        }

        if ($overdue) {
            $query->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())
                ->where('status', '!=', 'done');
        }

        $tasks = $query
            ->orderBy('due_date')
            ->orderByDesc('updated_at')
            ->get();
        $total = $tasks->count();
        $done = $tasks->where('status', 'done')->count();

        return view('planner.index', [
            'tasksByStatus' => $tasks->groupBy('status'),
            'allStatuses' => PlanningTask::STATUSES,
            'statuses' => $status === null
                ? PlanningTask::STATUSES
                : [$status => PlanningTask::STATUSES[$status]],
            'categories' => PlanningTask::CATEGORIES,
            'priorities' => PlanningTask::PRIORITIES,
            'visibilities' => PlanningTask::VISIBILITIES,
            'teamMembers' => User::query()->orderBy('name')->get(['id', 'name']),
            'tasks' => $tasks,
            'selectedProject' => $project,
            'viewStyle' => $viewStyle,
            'projectId' => $project->id,
            'scope' => $scope,
            'category' => $category,
            'statusFilter' => $status,
            'assigneeFilter' => $assigneeId,
            'progress' => [
                'total' => $total,
                'done' => $done,
                'active' => $tasks->whereIn('status', ['planned', 'in_progress'])->count(),
                'blocked' => $tasks->where('status', 'blocked')->count(),
                'overdue' => $tasks->filter(fn (PlanningTask $task): bool => $task->due_date !== null
                    && $task->due_date->lt(today())
                    && $task->status !== 'done'
                )->count(),
                'percent' => $total === 0 ? 0 : (int) round(($done / $total) * 100),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->taskRules(requireProject: false));
        $projectId = (int) ($validated['project_id'] ?? PlanningProject::omgComputer($request->user())->id);
        $project = $this->accessibleProject($request->user(), $projectId);
        $validated['project_id'] = $project->id;
        $validated['user_id'] = $request->user()->id;
        $validated['assigned_to'] = $validated['visibility'] === 'team'
            ? ($validated['assigned_to'] ?? null)
            : null;

        PlanningTask::create($validated);

        return redirect()
            ->route('planner.index', $this->plannerFilters($validated['visibility'], $project->id))
            ->with('status', 'Task added to the planner.');
    }

    public function update(Request $request, PlanningTask $planningTask): RedirectResponse
    {
        $this->authorizeTaskAccess($request->user(), $planningTask);
        $this->authorizeTaskManagement($request->user(), $planningTask);

        $validated = $request->validate($this->taskRules(requireProject: false));
        unset($validated['project_id']);
        $validated['assigned_to'] = $validated['visibility'] === 'team'
            ? ($validated['assigned_to'] ?? null)
            : null;

        $planningTask->update($validated);

        return redirect()
            ->route('planner.show', $planningTask)
            ->with('status', 'Task updated.');
    }

    public function updateStatus(Request $request, PlanningTask $planningTask): RedirectResponse
    {
        $this->authorizeTaskAccess($request->user(), $planningTask);
        $this->authorizeProgressUpdate($request->user(), $planningTask);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(PlanningTask::STATUSES))],
        ]);

        $planningTask->update($validated);

        return redirect()
            ->route('planner.show', $planningTask)
            ->with('status', 'Task progress updated.');
    }

    public function storeSubtask(Request $request, PlanningTask $planningTask): RedirectResponse
    {
        $this->authorizeTaskAccess($request->user(), $planningTask);
        $this->authorizeTaskManagement($request->user(), $planningTask);
        abort_if($planningTask->parent_task_id !== null, 422, 'Subtasks cannot have their own subtasks.');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(array_keys(PlanningTask::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(PlanningTask::PRIORITIES))],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $planningTask->subtasks()->create([
            ...$validated,
            'user_id' => $request->user()->id,
            'project_id' => $planningTask->project_id,
            'category' => 'task',
            'visibility' => $planningTask->visibility,
            'assigned_to' => $planningTask->visibility === 'team' ? ($validated['assigned_to'] ?? null) : null,
        ]);

        return redirect()->route('planner.show', $planningTask)->with('status', 'Subtask added.');
    }

    public function destroy(Request $request, PlanningTask $planningTask): RedirectResponse
    {
        $this->authorizeTaskAccess($request->user(), $planningTask);
        $this->authorizeTaskManagement($request->user(), $planningTask);
        $scope = $planningTask->visibility;
        $projectId = $planningTask->project_id;
        $planningTask->delete();

        return redirect()
            ->route('planner.index', $this->plannerFilters($scope, $projectId))
            ->with('status', 'Task deleted.');
    }

    public function show(Request $request, PlanningTask $planningTask): View
    {
        $this->authorizeTaskAccess($request->user(), $planningTask);
        $planningTask->load([
            'creator:id,name',
            'assignee:id,name',
            'project:id,name,slug,visibility',
            'parentTask:id,title,project_id',
            'subtasks.creator:id,name',
            'subtasks.assignee:id,name',
        ]);
        $comments = $planningTask->comments()
            ->with('author:id,name')
            ->oldest()
            ->get();

        return view('planner.task-show', [
            'task' => $planningTask,
            'parentTask' => $planningTask->parentTask,
            'statuses' => PlanningTask::STATUSES,
            'allStatuses' => PlanningTask::STATUSES,
            'categories' => PlanningTask::CATEGORIES,
            'priorities' => PlanningTask::PRIORITIES,
            'visibilities' => PlanningTask::VISIBILITIES,
            'members' => User::query()->orderBy('name')->get(['id', 'name']),
            'comments' => $comments,
            'canManage' => $planningTask->user_id === $request->user()->id
                || ($planningTask->visibility === 'team' && Gate::forUser($request->user())->allows('planner.manage-team-tasks')),
            'canUpdateProgress' => $planningTask->user_id === $request->user()->id
                || ($planningTask->visibility === 'team' && $planningTask->assigned_to === $request->user()->id)
                || ($planningTask->visibility === 'team' && Gate::forUser($request->user())->allows('planner.manage-team-tasks')),
        ]);
    }

    public function comment(Request $request, PlanningTask $planningTask): RedirectResponse
    {
        $this->authorizeTaskAccess($request->user(), $planningTask);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $planningTask->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return redirect()
            ->route('planner.show', $planningTask)
            ->with('status', 'Comment added to the task discussion.');
    }

    private function taskRules(bool $requireProject = true): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'category' => ['required', Rule::in(array_keys(PlanningTask::CATEGORIES))],
            'status' => ['required', Rule::in(array_keys(PlanningTask::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(PlanningTask::PRIORITIES))],
            'visibility' => ['required', Rule::in(array_keys(PlanningTask::VISIBILITIES))],
            'project_id' => [$requireProject ? 'required' : 'nullable', 'integer', 'exists:planning_projects,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ];
    }

    private function authorizeTaskManagement(User $user, PlanningTask $task): void
    {
        $isOwner = $task->user_id === $user->id;
        $canManageTeamTask = $task->visibility === 'team'
            && Gate::forUser($user)->allows('planner.manage-team-tasks');

        abort_unless($isOwner || $canManageTeamTask, 403);
    }

    private function authorizeTaskAccess(User $user, PlanningTask $task): void
    {
        $this->accessibleProject($user, (int) $task->project_id);
        abort_unless($task->visibility === 'team' || $task->user_id === $user->id, 404);
    }

    private function accessibleProject(User $user, int $projectId): PlanningProject
    {
        return PlanningProject::query()
            ->whereKey($projectId)
            ->where(fn (Builder $query): Builder => $query
                ->where('visibility', 'team')
                ->orWhere(fn (Builder $private): Builder => $private
                    ->where('visibility', 'personal')
                    ->where('user_id', $user->id)))
            ->firstOrFail();
    }

    private function authorizeProgressUpdate(User $user, PlanningTask $task): void
    {
        $isOwner = $task->user_id === $user->id;
        $isTeamAssignee = $task->visibility === 'team' && $task->assigned_to === $user->id;
        $canManageTeamTask = $task->visibility === 'team'
            && Gate::forUser($user)->allows('planner.manage-team-tasks');

        abort_unless($isOwner || $isTeamAssignee || $canManageTeamTask, 403);
    }

    private function plannerFilters(string $visibility, int $projectId): array
    {
        $filters = ['scope' => $visibility];
        $project = PlanningProject::query()->findOrFail($projectId);

        if ($project->slug !== 'omg-computer') {
            $filters['project'] = $projectId;
        }

        return $filters;
    }
}
