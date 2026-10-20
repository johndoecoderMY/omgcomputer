<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Event;
use App\Models\FlowChart;
use App\Models\InventoryPart;
use App\Models\Project;
use App\Models\ProjectEstimateItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        return view('projects.index', [
            'customerView' => $request->user()->hasRole('customer'),
        ]);
    }

    public function datatable(Request $request)
    {
        $customerView = $request->user()->hasRole('customer');
        $projects = Project::query();
        if ($customerView) {
            $projects->visibleToCustomer($request->user());
        } else {
            $projects->with('assignees')
                ->withCount('tasks')
                ->withCount(['tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done')]);
        }

        $table = DataTables::eloquent($projects);
        if (! $customerView) {
            $table->addColumn('assignees', fn (Project $project): string => e(
                $project->assignees->pluck('name')->join(', ') ?: 'Unassigned',
            ));
            $table->addColumn('progress', fn (Project $project): string => sprintf(
                '<div class="d-flex justify-content-between small mb-1"><span>%d of %d tasks</span><strong>%d%%</strong></div><div class="progress" role="progressbar" aria-label="%s progress" aria-valuenow="%d" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar bg-success" style="width: %d%%"></div></div>',
                $project->completed_tasks_count,
                $project->tasks_count,
                $project->progress,
                e($project->name),
                $project->progress,
                $project->progress,
            ));
        }

        return $table->addColumn('actions', fn (Project $project): string => $customerView
                ? sprintf('<a class="btn btn-sm btn-outline-primary" href="%s">View project</a>', e(route('projects.show', $project)))
                : sprintf(
                    '<a class="btn btn-sm btn-outline-primary" href="%s">View</a> <a class="btn btn-sm btn-outline-secondary" href="%s">Edit</a>',
                    e(route('projects.show', $project)),
                    e(route('projects.edit', $project)),
                ))
            ->rawColumns($customerView ? ['actions'] : ['progress', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('projects.create', [
            'project' => new Project,
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'project_id']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $customerId = $data['customer_id'] ?? null;
        $requirements = $data['requirements'] ?? [];
        $assignees = $data['assignees'] ?? [];
        unset($data['customer_id'], $data['requirements'], $data['assignees'], $data['assignees_present']);

        $project = DB::transaction(function () use ($data, $customerId, $requirements, $assignees): Project {
            $project = Project::create($data);
            $this->syncCustomer($project, $customerId);
            $this->syncRequirements($project, $requirements);
            $project->assignees()->sync($assignees);

            return $project;
        });

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Request $request, Project $project): View
    {
        if ($request->user()->hasRole('customer')) {
            abort_unless(
                Project::query()->visibleToCustomer($request->user())->whereKey($project->id)->exists(),
                404,
            );

            $email = strtolower(trim($request->user()->email));
            $tickets = $project->repairTickets()
                ->with('customer')
                ->whereHas('customer', fn (Builder $customers) => $customers->whereRaw('LOWER(email) = ?', [$email]))
                ->latest('received_at')
                ->get();

            $project->load(['links' => fn ($links) => $links->where('visible_to_customer', true)->orderBy('label')]);

            return view('projects.customer-show', compact('project', 'tickets'));
        }

        $project->load([
            'tasks' => fn ($query) => $query->whereNull('parent_task_id')->with(['assignees', 'subtasks.assignees']),
            'customers',
            'technologyStack' => fn ($query) => $query->orderBy('name'),
            'flowCharts' => fn ($query) => $query->orderBy('title'),
            'inventoryParts.supplier',
            'estimateItems' => fn ($query) => $query->orderBy('category')->orderBy('description'),
            'links' => fn ($query) => $query->orderBy('label'),
            'attachments',
            'events' => fn ($query) => $query->where('starts_at', '>=', now()->startOfDay())
                ->orderBy('starts_at')
                ->limit(10),
            'requirements',
            'assignees',
        ]);
        $project->loadCount('tasks');
        $project->loadCount([
            'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done'),
        ]);

        return view('projects.show', [
            'project' => $project,
            'availableParts' => InventoryPart::query()->with('supplier')->orderBy('name')->get(),
            'partsEstimateTotal' => $project->inventoryParts->sum(
                fn (InventoryPart $part): float => $part->pivot->quantity
                    * (float) ($part->pivot->unit_price ?? $part->selling_price),
            ),
            'otherEstimateTotal' => $project->estimateItems->sum(
                fn ($item): float => $item->line_total,
            ),
            'availableFlowCharts' => FlowChart::query()
                ->where('is_example', false)
                ->whereDoesntHave('projects', fn (Builder $query) => $query->where('projects.id', $project->id))
                ->orderBy('title')
                ->get(['id', 'title', 'category', 'subject_name']),
            'availableExamples' => FlowChart::query()
                ->where('is_example', true)
                ->orderBy('title')
                ->get(['id', 'title', 'category', 'subject_name']),
        ]);
    }

    public function storeRecommendedPart(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'inventory_part_id' => [
                'required',
                'integer',
                'exists:inventory_parts,id',
                Rule::unique('project_inventory_part', 'inventory_part_id')
                    ->where('project_id', $project->id),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'price_source' => ['required', Rule::in(['selling_price', 'cost_price', 'custom'])],
            'unit_price' => ['nullable', 'required_if:price_source,custom', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'url_sources' => ['nullable', 'array', 'max:10'],
            'url_sources.*' => ['nullable', 'url', 'max:2048'],
        ]);
        $urlSources = array_values(array_filter(
            $data['url_sources'] ?? [],
            fn (?string $url): bool => $url !== null && trim($url) !== '',
        ));
        $part = InventoryPart::query()->findOrFail($data['inventory_part_id']);
        $unitPrice = $data['price_source'] === 'custom'
            ? (float) $data['unit_price']
            : (float) $part->{$data['price_source']};

        $project->inventoryParts()->attach($data['inventory_part_id'], [
            'quantity' => $data['quantity'],
            'unit_price' => $unitPrice,
            'price_source' => $data['price_source'],
            'notes' => $data['notes'] ?? null,
            'url_sources' => $urlSources,
            'added_by' => $request->user()->id,
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Recommended hardware added to the project.');
    }

    public function updateRecommendedPart(Request $request, Project $project, InventoryPart $part): RedirectResponse
    {
        abort_unless($project->inventoryParts()->whereKey($part->id)->exists(), 404);

        $data = $request->validate([
            'price_source' => ['required', Rule::in(['selling_price', 'cost_price', 'custom'])],
            'unit_price' => ['nullable', 'required_if:price_source,custom', 'numeric', 'min:0', 'max:99999999'],
        ]);
        $unitPrice = $data['price_source'] === 'custom'
            ? (float) $data['unit_price']
            : (float) $part->{$data['price_source']};

        $project->inventoryParts()->updateExistingPivot($part->id, [
            'price_source' => $data['price_source'],
            'unit_price' => $unitPrice,
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Recommended part price updated.');
    }

    public function destroyRecommendedPart(Project $project, InventoryPart $part): RedirectResponse
    {
        abort_unless($project->inventoryParts()->whereKey($part->id)->exists(), 404);
        $project->inventoryParts()->detach($part->id);

        return redirect()->route('projects.show', $project)->with('success', 'Hardware recommendation removed from the project.');
    }

    public function storeEstimateItem(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(['software', 'labor', 'other'])],
            'description' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ]);

        $project->estimateItems()->create($data);

        return redirect()->route('projects.show', $project)->with('success', 'Project estimate item added.');
    }

    public function destroyEstimateItem(Project $project, ProjectEstimateItem $item): RedirectResponse
    {
        abort_unless($item->project_id === $project->id, 404);
        $item->delete();

        return redirect()->route('projects.show', $project)->with('success', 'Project estimate item removed.');
    }

    public function flowChartsDatatable(Project $project)
    {
        return DataTables::eloquent($project->flowCharts()->where('is_example', false))
            ->addColumn('category_label', fn (FlowChart $flowChart): string => [
                'website' => 'Website',
                'process' => 'Process',
                'product' => 'Product SOP',
                'service' => 'Service SOP',
                'education' => 'Student FYP SOP',
            ][$flowChart->category] ?? 'Flow chart')
            ->addColumn('actions', fn (FlowChart $flowChart): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">View</a> <form class="d-inline" method="POST" action="%s"><input type="hidden" name="_token" value="%s"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-outline-danger" type="submit">Unlink</button></form>',
                e(route('development.flowcharts.show', $flowChart)),
                e(route('projects.flowcharts.destroy', [$project, $flowChart])),
                e(csrf_token()),
            ))
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function linkFlowCharts(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'flow_chart_ids' => ['required', 'array', 'min:1'],
            'flow_chart_ids.*' => ['required', 'integer', 'distinct', 'exists:flow_charts,id'],
        ]);
        if (FlowChart::query()->whereIn('id', $data['flow_chart_ids'])->where('is_example', true)->exists()) {
            throw ValidationException::withMessages([
                'flow_chart_ids' => 'Example flow charts must be duplicated before they can be linked to a project.',
            ]);
        }
        $project->flowCharts()->syncWithoutDetaching($data['flow_chart_ids']);

        return redirect()->route('projects.show', $project)->with('success', 'Selected flow charts linked to the project.');
    }

    public function duplicateFlowCharts(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'flow_chart_ids' => ['required', 'array', 'min:1'],
            'flow_chart_ids.*' => ['required', 'integer', 'distinct', 'exists:flow_charts,id'],
        ]);
        $sourceCharts = FlowChart::query()->whereIn('id', $data['flow_chart_ids'])->get();

        DB::transaction(function () use ($sourceCharts, $project): void {
            foreach ($sourceCharts as $sourceChart) {
                $copy = $sourceChart->replicate();
                $suffix = " — {$project->name} copy";
                $copy->title = Str::limit($sourceChart->title, 255 - Str::length($suffix), '').$suffix;
                $copy->is_example = false;
                $copy->save();
                $copy->projects()->attach($project->id);
            }
        });

        return redirect()->route('projects.show', $project)->with('success', 'Selected flow charts duplicated and linked to the project.');
    }

    public function destroyFlowChart(Project $project, FlowChart $flowChart): RedirectResponse
    {
        $project->flowCharts()->detach($flowChart->id);

        return redirect()->route('projects.show', $project)->with('success', 'Flow chart unlinked from the project.');
    }

    public function storeEvent(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'event_type' => ['required', 'string', Rule::in(array_keys(Event::TYPES))],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $project->events()->create($data);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project event scheduled.');
    }

    public function destroyEvent(Project $project, Event $event): RedirectResponse
    {
        abort_unless($event->project_id === $project->id, 404);
        $event->delete();

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project event removed.');
    }

    public function storeLink(Request $request, Project $project): RedirectResponse
    {
        $project->links()->create($this->validatedLink($request));

        return redirect()->route('projects.show', $project)->with('success', 'Project link added.');
    }

    public function updateLink(Request $request, Project $project, int $link): RedirectResponse
    {
        $project->links()->findOrFail($link)->update($this->validatedLink($request));

        return redirect()->route('projects.show', $project)->with('success', 'Project link updated.');
    }

    public function destroyLink(Project $project, int $link): RedirectResponse
    {
        $project->links()->findOrFail($link)->delete();

        return redirect()->route('projects.show', $project)->with('success', 'Project link removed.');
    }

    public function syncFlowCharts(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'flow_chart_ids' => ['nullable', 'array'],
            'flow_chart_ids.*' => ['required', 'integer', 'distinct', 'exists:flow_charts,id'],
        ]);
        if (FlowChart::query()->whereIn('id', $data['flow_chart_ids'] ?? [])->where('is_example', true)->exists()) {
            throw ValidationException::withMessages([
                'flow_chart_ids' => 'Example flow charts must be duplicated before they can be linked to a project.',
            ]);
        }

        $project->flowCharts()->sync($data['flow_chart_ids'] ?? []);

        return redirect()->route('projects.show', $project)->with('success', 'Project flow charts updated.');
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', [
            'project' => $project->load('customers', 'requirements', 'assignees'),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'project_id']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validatedData($request);
        $customerId = $data['customer_id'] ?? null;
        $requirements = $data['requirements'] ?? [];
        $shouldSyncAssignees = array_key_exists('assignees', $data) || $request->exists('assignees_present');
        $assignees = $data['assignees'] ?? [];
        unset($data['customer_id'], $data['requirements'], $data['assignees'], $data['assignees_present']);

        DB::transaction(function () use ($project, $data, $customerId, $requirements, $assignees, $shouldSyncAssignees): void {
            $project->update($data);
            $this->syncCustomer($project, $customerId);
            $this->syncRequirements($project, $requirements);
            if ($shouldSyncAssignees) {
                $project->assignees()->sync($assignees);
            }
        });

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project and its tasks deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'requirements_notes' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['sometimes', 'array', 'max:100'],
            'requirements.*.id' => ['nullable', 'integer', 'distinct'],
            'requirements.*.requirement' => ['required', 'string', 'max:500'],
            'requirements.*.is_completed' => ['sometimes', 'boolean'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'assignees' => ['sometimes', 'array'],
            'assignees.*' => ['integer', 'distinct', 'exists:users,id'],
            'assignees_present' => ['sometimes', 'boolean'],
        ]);
    }

    private function validatedLink(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'url' => ['required', 'url:http,https', 'max:2048'],
            'visible_to_customer' => ['sometimes', 'boolean'],
        ]) + ['visible_to_customer' => $request->boolean('visible_to_customer')];
    }

    private function syncRequirements(Project $project, array $requirements): void
    {
        $existingIds = $project->requirements()->pluck('id')->all();
        $submittedIds = array_values(array_filter(array_map(
            fn (array $requirement): ?int => isset($requirement['id']) ? (int) $requirement['id'] : null,
            $requirements,
        )));

        if (array_diff($submittedIds, $existingIds) !== []) {
            throw ValidationException::withMessages([
                'requirements' => 'Requirements must belong to this project.',
            ]);
        }

        $keptIds = [];
        foreach ($requirements as $index => $requirement) {
            $values = [
                'requirement' => $requirement['requirement'],
                'is_completed' => (bool) ($requirement['is_completed'] ?? false),
                'sort_order' => $index,
            ];

            if (isset($requirement['id'])) {
                $project->requirements()->whereKey($requirement['id'])->update($values);
                $keptIds[] = (int) $requirement['id'];
            } else {
                $keptIds[] = $project->requirements()->create($values)->id;
            }
        }

        $project->requirements()->whereNotIn('id', $keptIds)->delete();
    }

    private function syncCustomer(Project $project, ?int $customerId): void
    {
        $project->customers()->update(['project_id' => null]);

        if ($customerId !== null) {
            Customer::query()->whereKey($customerId)->update(['project_id' => $project->id]);
        }
    }
}
