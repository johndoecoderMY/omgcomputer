<?php

namespace App\Http\Controllers;

use App\Models\BillingDocument;
use App\Models\Customer;
use App\Models\Event;
use App\Models\FlowChart;
use App\Models\InventoryPart;
use App\Models\Project;
use App\Models\ProjectEstimateItem;
use App\Models\User;
use App\Rules\AssignableUser;
use App\Services\WorkspaceNotifier;
use App\Support\AssignmentAccess;
use App\Support\DataTableQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\QueryBuilder\AllowedFilter;
use Yajra\DataTables\Facades\DataTables;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        return view('projects.index', [
            'customerView' => $request->user()->hasRole('customer'),
            'canCreateProjects' => $request->user()->hasRole('admin') || $request->user()->can('projects.create'),
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

        $table = DataTables::eloquent(DataTableQueryBuilder::for($projects, $request, [
            'name' => AllowedFilter::partial('name'),
            'status' => AllowedFilter::partial('status'),
        ]));
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

        return $table->addColumn('actions', function (Project $project) use ($customerView, $request): string {
            $viewLink = sprintf('<a class="btn btn-sm btn-outline-primary" href="%s">View</a>', e(route('projects.show', $project)));
            if ($customerView) {
                return str_replace('>View</a>', '>View project</a>', $viewLink);
            }
            $canManage = $request->user()->hasRole('admin')
                || (int) $project->created_by_user_id === (int) $request->user()->id
                || $project->assignees()->whereKey($request->user()->id)->exists();

            return $canManage
                ? $viewLink.' <a class="btn btn-sm btn-outline-secondary" href="'.e(route('projects.edit', $project)).'">Edit</a>'
                : $viewLink;
        })
            ->rawColumns($customerView ? ['actions'] : ['progress', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        $project = new Project;

        return view('projects.create', [
            'project' => $project,
            ...$this->customerChoices($project),
            'users' => auth()->user()->hasRole('admin') ? AssignmentAccess::users()->get(['id', 'name']) : collect(),
        ]);
    }

    public function store(Request $request, WorkspaceNotifier $notifier): RedirectResponse
    {
        $data = $this->validatedData($request);
        $customerIdentity = $this->requestedCustomerIdentity($data);
        $requirements = $data['requirements'] ?? [];
        $assignees = $request->user()->hasRole('admin') ? ($data['assignees'] ?? []) : [];
        unset($data['customer_id'], $data['customer_identity'], $data['requirements'], $data['assignees'], $data['assignees_present']);

        $project = DB::transaction(function () use ($data, $customerIdentity, $requirements, $assignees, $request): Project {
            $project = Project::create([...$data, 'created_by_user_id' => $request->user()->id]);
            $this->syncCustomer($project, $customerIdentity);
            $this->syncRequirements($project, $requirements);
            $project->assignees()->sync($assignees);

            return $project;
        });
        $project->load(['creator', 'assignees']);
        $notifier->notifyUsers(
            collect([$project->creator])->merge($project->assignees),
            $request->user(),
            'Project created',
            sprintf('Project “%s” was created.', $project->name),
            route('projects.show', $project),
        );

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
            $project->load([
                'links' => fn ($links) => $links->where('visible_to_customer', true)->orderBy('label'),
                'requirements',
                'events' => fn ($events) => $events->where('visible_to_customer', true)->orderBy('starts_at'),
                'flowCharts' => fn ($flowCharts) => $flowCharts->orderBy('title'),
                'technologyStack' => fn ($technologies) => $technologies->orderBy('name'),
                'inventoryParts' => fn ($parts) => $parts->select([
                    'inventory_parts.id',
                    'inventory_parts.name',
                    'inventory_parts.description',
                ])->orderBy('inventory_parts.name'),
                'estimateItems' => fn ($items) => $items->orderBy('category')->orderBy('description'),
                'assignees:id,name',
                'tasks' => fn ($tasks) => $tasks->whereNull('parent_task_id')
                    ->with(['assignees:id,name', 'subtasks.assignees:id,name'])
                    ->orderBy('created_at'),
            ]);
            $billingDocuments = $this->projectBillingDocuments($project, $email);

            return view('projects.customer-show', compact('project', 'billingDocuments'));
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
            'customerAccount',
        ]);
        $billingDocuments = $this->projectBillingDocuments($project);
        $project->loadCount('tasks');
        $project->loadCount([
            'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'done'),
        ]);

        return view('projects.show', [
            'project' => $project,
            'canManageProject' => $request->user()->hasRole('admin')
                || (int) $project->created_by_user_id === (int) $request->user()->id
                || $project->assignees->contains('id', $request->user()->id),
            'canManageBilling' => $request->user()->can('billing.manage'),
            'billingDocuments' => $billingDocuments,
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

    private function projectBillingDocuments(Project $project, ?string $customerEmail = null)
    {
        return BillingDocument::query()
            ->where(function (Builder $documents) use ($project): void {
                $documents->where('project_id', $project->id)
                    ->orWhereHas('repairTicket', fn (Builder $tickets) => $tickets->where('project_id', $project->id));
            })
            ->when($customerEmail !== null, function (Builder $documents) use ($customerEmail): void {
                $documents->whereIn('type', ['invoice', 'quotation'])
                    ->where('status', '!=', 'draft')
                    ->where(function (Builder $owned) use ($customerEmail): void {
                        $owned->whereHas('project.customers', fn (Builder $customers) => $customers
                            ->whereRaw('LOWER(TRIM(email)) = ?', [$customerEmail]))
                            ->orWhereHas('project.customerAccount', fn (Builder $accounts) => $accounts
                                ->whereRaw('LOWER(TRIM(email)) = ?', [$customerEmail]))
                            ->orWhereHas('repairTicket.customer', fn (Builder $customers) => $customers
                                ->whereRaw('LOWER(TRIM(email)) = ?', [$customerEmail]));
                    });
            })
            ->with('repairTicket')
            ->latest('issue_date')
            ->get();
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

    public function flowChartsDatatable(Request $request, Project $project)
    {
        return DataTables::eloquent(DataTableQueryBuilder::for(
            $project->flowCharts()->where('is_example', false),
            $request,
            [
                'name' => AllowedFilter::partial('name'),
                'category' => AllowedFilter::exact('category'),
            ],
        ))
            ->addColumn('category_label', fn (FlowChart $flowChart): string => $flowChart->categoryLabel())
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

    public function updateEventVisibility(Request $request, Project $project, Event $event): RedirectResponse
    {
        abort_unless($event->project_id === $project->id, 404);
        $data = $request->validate([
            'visible_to_customer' => ['required', 'boolean'],
        ]);
        $event->update(['visible_to_customer' => $data['visible_to_customer']]);

        return redirect()->route('projects.show', $project)
            ->with('success', $data['visible_to_customer']
                ? 'Project event is now visible to the customer.'
                : 'Project event is now hidden from the customer.');
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
        $project->load('customers', 'customerAccount', 'requirements', 'assignees');

        return view('projects.edit', [
            'project' => $project,
            ...$this->customerChoices($project),
            'users' => auth()->user()->hasRole('admin') ? AssignmentAccess::users()->get(['id', 'name']) : collect(),
        ]);
    }

    public function update(Request $request, Project $project, WorkspaceNotifier $notifier): RedirectResponse
    {
        $data = $this->validatedData($request);
        $customerIdentity = $this->requestedCustomerIdentity($data);
        $requirements = $data['requirements'] ?? [];
        $shouldSyncAssignees = $request->user()->hasRole('admin')
            && (array_key_exists('assignees', $data) || $request->exists('assignees_present'));
        $assignees = $request->user()->hasRole('admin') ? ($data['assignees'] ?? []) : [];
        unset($data['customer_id'], $data['customer_identity'], $data['requirements'], $data['assignees'], $data['assignees_present']);

        $assigneesChanged = false;
        $projectChanged = false;
        DB::transaction(function () use ($project, $data, $customerIdentity, $requirements, $assignees, $shouldSyncAssignees, &$assigneesChanged, &$projectChanged): void {
            $project->update($data);
            $projectChanged = $project->wasChanged();
            $this->syncCustomer($project, $customerIdentity);
            $this->syncRequirements($project, $requirements);
            if ($shouldSyncAssignees) {
                $changes = $project->assignees()->sync($assignees);
                $assigneesChanged = $changes['attached'] !== [] || $changes['detached'] !== [];
            }
        });
        if ($projectChanged || $assigneesChanged) {
            $project->load(['creator', 'assignees']);
            $notifier->notifyUsers(
                collect([$project->creator])->merge($project->assignees),
                $request->user(),
                'Project updated',
                sprintf('Project “%s” was updated.', $project->name),
                route('projects.show', $project),
            );
        }

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
        AssignmentAccess::preventNonAdminAssignment($request, ['assignees', 'assignees_present']);

        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'requirements_notes' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['sometimes', 'array', 'max:100'],
            'requirements.*.id' => ['nullable', 'integer', 'distinct'],
            'requirements.*.requirement' => ['required', 'string', 'max:500'],
            'requirements.*.is_completed' => ['sometimes', 'boolean'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_identity' => ['nullable', 'string', 'regex:/^(account|record):[1-9][0-9]*$/'],
            'assignees' => ['sometimes', 'array'],
            'assignees.*' => ['integer', 'distinct', new AssignableUser],
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

    private function requestedCustomerIdentity(array $data): ?string
    {
        if (! empty($data['customer_identity'])) {
            return $data['customer_identity'];
        }

        return ! empty($data['customer_id']) ? 'record:'.$data['customer_id'] : null;
    }

    private function customerChoices(Project $project): array
    {
        $accounts = User::role('customer')->orderBy('name')->get(['id', 'name', 'email'])
            ->unique(fn (User $account): string => strtolower(trim($account->email)))
            ->values();
        $accountEmails = $accounts->map(fn (User $account): string => strtolower(trim($account->email)))
            ->filter()
            ->all();
        $records = Customer::query()->orderBy('name')->get(['id', 'name', 'email', 'project_id'])
            ->reject(fn (Customer $customer): bool => $customer->email !== null
                && in_array(strtolower(trim($customer->email)), $accountEmails, true))
            ->unique(fn (Customer $customer): string => strtolower(trim((string) $customer->email))
                ?: 'record:'.$customer->id);

        $choices = $accounts->map(fn (User $account): array => [
            'value' => 'account:'.$account->id,
            'label' => sprintf('%s · %s (account)', $account->name, $account->email),
        ])->concat($records->map(fn (Customer $customer): array => [
            'value' => 'record:'.$customer->id,
            'label' => sprintf(
                '%s%s (customer record)%s',
                $customer->name,
                $customer->email ? ' · '.$customer->email : '',
                $customer->project_id && $customer->project_id !== $project->id ? ' · currently linked to another project' : '',
            ),
        ]))->sortBy('label')->values();

        $selectedIdentity = null;
        if ($project->customer_user_id) {
            $selectedIdentity = 'account:'.$project->customer_user_id;
        } elseif ($linkedCustomer = $project->customers->first()) {
            $linkedEmail = strtolower(trim((string) $linkedCustomer->email));
            $matchingAccount = $linkedEmail === ''
                ? null
                : $accounts->first(fn (User $account): bool => strtolower(trim($account->email)) === $linkedEmail);
            $matchingRecord = $linkedEmail === ''
                ? null
                : $records->first(fn (Customer $customer): bool => strtolower(trim((string) $customer->email)) === $linkedEmail);
            $selectedIdentity = $matchingAccount
                ? 'account:'.$matchingAccount->id
                : 'record:'.($matchingRecord?->id ?? $linkedCustomer->id);
        }

        return [
            'customerChoices' => $choices,
            'selectedCustomerIdentity' => $selectedIdentity,
        ];
    }

    private function syncCustomer(Project $project, ?string $identity): void
    {
        if ($identity === null) {
            $project->customers()->update(['project_id' => null]);
            $project->customer_user_id = null;
            $project->save();

            return;
        }

        [$kind, $id] = explode(':', $identity, 2);
        if ($kind === 'account') {
            $account = User::role('customer')->find((int) $id);
            if (! $account) {
                throw ValidationException::withMessages([
                    'customer_identity' => 'Select a valid customer account.',
                ]);
            }

            $project->customers()->update(['project_id' => null]);
            $project->customer_user_id = $account->id;
            $project->save();

            return;
        }

        $customer = Customer::query()->find((int) $id);
        if (! $customer) {
            throw ValidationException::withMessages([
                'customer_identity' => 'Select a valid customer record.',
            ]);
        }

        $project->customers()->update(['project_id' => null]);
        $customer->project_id = $project->id;
        $customer->save();
        $project->customer_user_id = null;
        $project->save();
    }
}
