<?php

namespace App\Http\Controllers;

use App\Models\PlanningProject;
use App\Models\PlanningTask;
use App\Models\RepairTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanningProjectController extends Controller
{
    public function index(Request $request): View
    {
        return $this->projectOverview($request, PlanningProject::omgComputer($request->user()));
    }

    public function listing(Request $request): View
    {
        $user = $request->user();
        PlanningProject::omgComputer($user);
        $projects = PlanningProject::query()
            ->where(function (Builder $query) use ($user): void {
                $query->where('visibility', 'team')
                    ->orWhere(fn (Builder $private): Builder => $private
                        ->where('visibility', 'personal')
                        ->where('user_id', $user->id));
            })
            ->with('owner:id,name')
            ->withCount(['tasks' => fn (Builder $query): Builder => $query
                ->where(fn (Builder $visible): Builder => $visible
                    ->where('visibility', 'team')
                    ->orWhere(fn (Builder $private): Builder => $private
                        ->where('visibility', 'personal')
                        ->where('user_id', $user->id)))])
            ->withCount(['tasks as completed_tasks_count' => fn (Builder $query): Builder => $query
                ->where('status', 'done')
                ->where(fn (Builder $visible): Builder => $visible
                    ->where('visibility', 'team')
                    ->orWhere(fn (Builder $private): Builder => $private
                        ->where('visibility', 'personal')
                        ->where('user_id', $user->id)))])
            ->latest('updated_at')
            ->get();

        return view('planner.project-list', compact('projects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'visibility' => ['required', Rule::in(array_keys(PlanningTask::VISIBILITIES))],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        unset($validated['images']);
        $project = $request->user()->planningProjects()->create($validated);
        $this->appendImages($request, $project);

        return redirect()
            ->route('projects.show', $project)
            ->with('status', 'Project created.');
    }

    public function storeImages(Request $request, PlanningProject $planningProject): RedirectResponse
    {
        $this->authorizeProject($request->user(), $planningProject);
        $request->validate([
            'images' => ['required', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->appendImages($request, $planningProject);

        return redirect()->route('projects.show', $planningProject)->with('status', 'Project photos uploaded.');
    }

    public function image(Request $request, PlanningProject $planningProject, int $image)
    {
        $this->authorizeProject($request->user(), $planningProject);
        $path = $planningProject->images[$image] ?? null;
        abort_unless(is_string($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function show(Request $request, PlanningProject $planningProject): View
    {
        $this->authorizeProject($request->user(), $planningProject);

        return $this->projectOverview($request, $planningProject);
    }

    public function teams(Request $request): View
    {
        $project = PlanningProject::omgComputer($request->user());
        $members = User::query()
            ->withCount([
                'assignedPlanningTasks as open_tasks_count' => fn (Builder $query): Builder => $query
                    ->where('project_id', $project->id)
                    ->where('visibility', 'team')
                    ->whereNot('status', 'done'),
                'assignedPlanningTasks as completed_tasks_count' => fn (Builder $query): Builder => $query
                    ->where('project_id', $project->id)
                    ->where('visibility', 'team')
                    ->where('status', 'done'),
            ])
            ->orderBy('name')
            ->get();

        return view('planner.teams', ['members' => $members, 'projectId' => $project->id]);
    }

    private function projectOverview(Request $request, PlanningProject $project): View
    {
        $user = $request->user();
        $tasks = PlanningTask::query()
            ->where('planning_tasks.project_id', $project->id)
            ->where(function (Builder $query) use ($user): void {
                $query->where('visibility', 'team')
                    ->orWhere(function (Builder $query) use ($user): void {
                        $query->where('visibility', 'personal')
                            ->where('user_id', $user->id);
                    });
            });

        $taskTotals = (clone $tasks)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done")
            ->first();

        $total = (int) $taskTotals->total;
        $done = (int) $taskTotals->done;
        $categoryCounts = (clone $tasks)
            ->selectRaw("SUM(CASE WHEN category = 'feature' THEN 1 ELSE 0 END) as features")
            ->selectRaw("SUM(CASE WHEN category = 'operations' THEN 1 ELSE 0 END) as operations")
            ->selectRaw("SUM(CASE WHEN category = 'task' THEN 1 ELSE 0 END) as general")
            ->selectRaw("SUM(CASE WHEN category = 'milestone' THEN 1 ELSE 0 END) as milestones")
            ->first();
        $openTickets = RepairTicket::query()->where('status', '!=', 'closed');
        $allTasks = (clone $tasks)
            ->with('assignee:id,name')
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
            ->orderBy('due_date')
            ->latest('updated_at')
            ->get();

        return view('planner.projects', [
            'project' => $project,
            'isMainProject' => $project->slug === 'omg-computer',
            'categoryCounts' => [
                'features' => (int) $categoryCounts->features,
                'operations' => (int) $categoryCounts->operations,
                'general' => (int) $categoryCounts->general,
                'milestones' => (int) $categoryCounts->milestones,
            ],
            'overdueTasks' => (clone $tasks)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', today())
                ->where('status', '!=', 'done')
                ->count(),
            'tickets' => [
                'open' => (clone $openTickets)->count(),
                'urgent' => (clone $openTickets)->where('priority', 'urgent')->count(),
            ],
            'totals' => [
                'total' => $total,
                'done' => $done,
                'remaining' => $total - $done,
                'active' => (clone $tasks)->whereIn('status', ['planned', 'in_progress'])->count(),
                'blocked' => (clone $tasks)->where('status', 'blocked')->count(),
                'percent' => $total === 0 ? 0 : (int) round(($done / $total) * 100),
            ],
            'tasks' => $allTasks,
            'statuses' => PlanningTask::STATUSES,
            'categories' => PlanningTask::CATEGORIES,
        ]);
    }

    private function authorizeProject(User $user, PlanningProject $project): void
    {
        abort_unless(
            $project->visibility === 'team' || $project->user_id === $user->id,
            404,
        );
    }

    private function appendImages(Request $request, PlanningProject $project): void
    {
        $paths = $project->images ?? [];
        foreach ($request->file('images', []) as $image) {
            $path = $image->store('projects/'.$project->id, 'local');
            if (! is_string($path)) {
                throw new \RuntimeException('Project image could not be saved.');
            }
            $paths[] = $path;
        }

        if ($paths !== ($project->images ?? [])) {
            $project->update(['images' => $paths]);
        }
    }
}
