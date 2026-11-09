<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFeature;
use App\Models\ProjectMilestone;
use App\Models\ProjectModule;
use App\Models\ProjectQualityItem;
use App\Models\ProjectRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectPlanningController extends Controller
{
    public function show(Project $project): View
    {
        $project->load([
            'modules.features',
            'requirements' => fn ($query) => $query->with(['module', 'parent'])->withCount('tasks'),
            'milestones.dependency',
            'qualityItems.requirement',
        ]);
        $hasOverdueMilestone = $project->milestones->contains(
            fn (ProjectMilestone $milestone): bool => $milestone->due_on
                && $milestone->due_on->isBefore(today())
                && $milestone->status !== 'done',
        );
        $hasOpenCriticalIssue = $project->qualityItems->contains(
            fn (ProjectQualityItem $item): bool => $item->kind === 'issue'
                && in_array($item->severity, ['high', 'critical'], true)
                && ! in_array($item->status, ['resolved', 'closed'], true),
        );
        $projectHealth = match (true) {
            $project->planning_status === 'completed' => 'Complete',
            $project->planning_status === 'cancelled' => 'Cancelled',
            $hasOverdueMilestone || $hasOpenCriticalIssue => 'At risk',
            $project->planning_status === 'on_hold' => 'On hold',
            $project->planning_status === 'active' => 'On track',
            default => 'Not started',
        };

        return view('projects.planning', [
            'project' => $project,
            'modules' => $project->modules,
            'requirements' => $project->requirements,
            'milestones' => $project->milestones,
            'qualityItems' => $project->qualityItems->sortByDesc('updated_at'),
            'requirementTypes' => ProjectRequirement::TYPES,
            'requirementPriorities' => ProjectRequirement::PRIORITIES,
            'requirementStatuses' => ProjectRequirement::STATUSES,
            'milestoneStatuses' => ProjectMilestone::STATUSES,
            'projectHealth' => $projectHealth,
        ]);
    }

    public function updateOverview(Request $request, Project $project): RedirectResponse
    {
        $project->update($request->validate([
            'business_background' => ['nullable', 'string', 'max:10000'],
            'user_roles' => ['nullable', 'string', 'max:10000'],
            'objectives' => ['nullable', 'string', 'max:10000'],
            'problem_statement' => ['nullable', 'string', 'max:10000'],
            'proposed_solution' => ['nullable', 'string', 'max:10000'],
            'scope_in' => ['nullable', 'string', 'max:10000'],
            'scope_out' => ['nullable', 'string', 'max:10000'],
            'planning_status' => ['required', Rule::in(['draft', 'planning', 'active', 'on_hold', 'completed', 'cancelled'])],
            'start_date' => ['nullable', 'date'],
            'target_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
        ]));

        return $this->success($project, 'Project brief and planning details saved.');
    }

    public function storeRequirement(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validatedRequirement($request, $project);
        $data['code'] = $this->nextRequirementCode($project, $data['requirement_type']);
        $data['is_completed'] = $data['status'] === 'done';
        $project->requirements()->create($data);

        return $this->success($project, 'Requirement added.');
    }

    public function updateRequirement(Request $request, Project $project, ProjectRequirement $requirement): RedirectResponse
    {
        abort_unless($requirement->project_id === $project->id, 404);
        $data = $this->validatedRequirement($request, $project, $requirement);
        if ($requirement->requirement_type !== $data['requirement_type']) {
            $data['code'] = $this->nextRequirementCode($project, $data['requirement_type']);
        }
        $data['is_completed'] = $data['status'] === 'done';
        $requirement->update($data);

        return $this->success($project, 'Requirement updated.');
    }

    public function destroyRequirement(Project $project, ProjectRequirement $requirement): RedirectResponse
    {
        abort_unless($requirement->project_id === $project->id, 404);
        $requirement->delete();

        return $this->success($project, 'Requirement removed.');
    }

    public function storeModule(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180', Rule::unique('project_modules')->where('project_id', $project->id)],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $project->modules()->create($data + ['sort_order' => $project->modules()->count()]);

        return $this->success($project, 'Module added.');
    }

    public function updateModule(Request $request, Project $project, ProjectModule $module): RedirectResponse
    {
        abort_unless($module->project_id === $project->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180', Rule::unique('project_modules')->where('project_id', $project->id)->ignore($module->id)],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
        $module->update($data);

        return $this->success($project, 'Module updated.');
    }

    public function destroyModule(Project $project, ProjectModule $module): RedirectResponse
    {
        abort_unless($module->project_id === $project->id, 404);
        $module->delete();

        return $this->success($project, 'Module and its features removed.');
    }

    public function storeFeature(Request $request, Project $project, ProjectModule $module): RedirectResponse
    {
        $this->assertModuleBelongsToProject($project, $module);
        $data = $this->validatedFeature($request, $module);
        $module->features()->create($data);

        return $this->success($project, 'Feature added.');
    }

    public function updateFeature(Request $request, Project $project, ProjectModule $module, ProjectFeature $feature): RedirectResponse
    {
        $this->assertModuleBelongsToProject($project, $module);
        abort_unless($feature->project_module_id === $module->id, 404);
        $feature->update($this->validatedFeature($request, $module, $feature));

        return $this->success($project, 'Feature updated.');
    }

    public function destroyFeature(Project $project, ProjectModule $module, ProjectFeature $feature): RedirectResponse
    {
        $this->assertModuleBelongsToProject($project, $module);
        abort_unless($feature->project_module_id === $module->id, 404);
        $feature->delete();

        return $this->success($project, 'Feature removed.');
    }

    public function storeMilestone(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validatedMilestone($request, $project);
        $project->milestones()->create($data + ['sort_order' => $project->milestones()->count()]);

        return $this->success($project, 'Milestone added.');
    }

    public function updateMilestone(Request $request, Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);
        $milestone->update($this->validatedMilestone($request, $project, $milestone));

        return $this->success($project, 'Milestone updated.');
    }

    public function destroyMilestone(Project $project, ProjectMilestone $milestone): RedirectResponse
    {
        abort_unless($milestone->project_id === $project->id, 404);
        $milestone->delete();

        return $this->success($project, 'Milestone removed.');
    }

    public function storeQualityItem(Request $request, Project $project): RedirectResponse
    {
        $project->qualityItems()->create($this->validatedQualityItem($request, $project));

        return $this->success($project, 'Test case or issue added.');
    }

    public function updateQualityItem(Request $request, Project $project, ProjectQualityItem $item): RedirectResponse
    {
        abort_unless($item->project_id === $project->id, 404);
        $item->update($this->validatedQualityItem($request, $project, $item));

        return $this->success($project, 'Test case or issue updated.');
    }

    public function destroyQualityItem(Project $project, ProjectQualityItem $item): RedirectResponse
    {
        abort_unless($item->project_id === $project->id, 404);
        $item->delete();

        return $this->success($project, 'Test case or issue removed.');
    }

    private function validatedRequirement(Request $request, Project $project, ?ProjectRequirement $requirement = null): array
    {
        $data = $request->validate([
            'requirement' => ['required', 'string', 'max:500'],
            'requirement_type' => ['required', Rule::in(array_keys(ProjectRequirement::TYPES))],
            'priority' => ['required', Rule::in(array_keys(ProjectRequirement::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(ProjectRequirement::STATUSES))],
            'user_story' => ['nullable', 'string', 'max:5000'],
            'acceptance_criteria' => ['nullable', 'string', 'max:10000'],
            'project_module_id' => ['nullable', 'integer', Rule::exists('project_modules', 'id')->where('project_id', $project->id)],
            'parent_requirement_id' => ['nullable', 'integer', Rule::exists('project_requirements', 'id')->where('project_id', $project->id)],
        ]);

        if (! empty($data['parent_requirement_id'])) {
            $parentId = (int) $data['parent_requirement_id'];
            $visited = [];
            while ($parentId !== 0 && ! isset($visited[$parentId])) {
                if ($requirement && $parentId === $requirement->id) {
                    throw ValidationException::withMessages([
                        'parent_requirement_id' => 'Requirement relationships cannot form a cycle.',
                    ]);
                }
                $visited[$parentId] = true;
                $parentId = (int) ProjectRequirement::query()
                    ->where('project_id', $project->id)
                    ->whereKey($parentId)
                    ->value('parent_requirement_id');
            }
        }

        if ($data['requirement_type'] === 'functional' && empty($data['user_story'])) {
            $data['user_story'] = null;
        }

        return $data;
    }

    private function validatedFeature(Request $request, ProjectModule $module, ?ProjectFeature $feature = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:180',
                Rule::unique('project_features')->where('project_module_id', $module->id)->ignore($feature?->id),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(array_keys(ProjectRequirement::PRIORITIES))],
            'status' => ['required', Rule::in(array_keys(ProjectRequirement::STATUSES))],
        ]);

        return $data;
    }

    private function validatedMilestone(Request $request, Project $project, ?ProjectMilestone $milestone = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'deliverable' => ['nullable', 'string', 'max:5000'],
            'starts_on' => ['nullable', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', Rule::in(array_keys(ProjectMilestone::STATUSES))],
            'depends_on_milestone_id' => [
                'nullable',
                'integer',
                Rule::exists('project_milestones', 'id')->where('project_id', $project->id),
                Rule::notIn($milestone ? [$milestone->id] : []),
            ],
        ]);

        if ($milestone && ! empty($data['depends_on_milestone_id'])) {
            $dependencyId = (int) $data['depends_on_milestone_id'];
            $visited = [];
            while ($dependencyId !== 0 && ! isset($visited[$dependencyId])) {
                if ($dependencyId === $milestone->id) {
                    throw ValidationException::withMessages([
                        'depends_on_milestone_id' => 'Milestone dependencies cannot form a cycle.',
                    ]);
                }
                $visited[$dependencyId] = true;
                $dependencyId = (int) ProjectMilestone::query()
                    ->where('project_id', $project->id)
                    ->whereKey($dependencyId)
                    ->value('depends_on_milestone_id');
            }
        }

        return $data;
    }

    private function validatedQualityItem(Request $request, Project $project, ?ProjectQualityItem $item = null): array
    {
        $kind = $request->input('kind');
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(ProjectQualityItem::KINDS))],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'expected_result' => ['nullable', 'string', 'max:5000'],
            'actual_result' => ['nullable', 'string', 'max:5000'],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'severity' => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'status' => ['required', Rule::in($kind === 'issue'
                ? ['open', 'in_progress', 'resolved', 'closed']
                : ['not_run', 'passed', 'failed', 'blocked'])],
            'resolution' => ['nullable', 'string', 'max:5000'],
            'project_requirement_id' => [
                'nullable',
                'integer',
                Rule::exists('project_requirements', 'id')->where('project_id', $project->id),
            ],
        ]);

        if ($data['kind'] === 'test_case') {
            $data['severity'] = null;
            $data['resolution'] = null;
        }

        return $data;
    }

    private function nextRequirementCode(Project $project, string $type): string
    {
        $prefix = match ($type) {
            'business' => 'BR',
            'non_functional' => 'NFR',
            default => 'FR',
        };
        $lastNumber = $project->requirements()
            ->where('code', 'like', $prefix.'-%')
            ->pluck('code')
            ->map(fn (string $code): int => (int) substr($code, strlen($prefix) + 1))
            ->max() ?? 0;

        return sprintf('%s-%02d', $prefix, $lastNumber + 1);
    }

    private function assertModuleBelongsToProject(Project $project, ProjectModule $module): void
    {
        abort_unless($module->project_id === $project->id, 404);
    }

    private function success(Project $project, string $message): RedirectResponse
    {
        return redirect()->route('projects.planning', $project)->with('success', $message);
    }
}
