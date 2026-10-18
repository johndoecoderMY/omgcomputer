<?php

namespace App\Http\Controllers;

use App\Models\FlowChart;
use App\Models\ProductService;
use App\Support\SopTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class FlowChartController extends Controller
{
    private const STEP_KINDS = ['page', 'action', 'decision', 'start', 'end'];

    public function index(): View
    {
        return view('development.flowcharts.index');
    }

    public function datatable()
    {
        return DataTables::eloquent(FlowChart::query()->with('projects'))
            ->addColumn('category_label', fn (FlowChart $flowChart): string => [
                'website' => 'Website',
                'process' => 'Process',
                'product' => 'Product SOP',
                'service' => 'Service SOP',
                'education' => 'Student FYP SOP',
            ][$flowChart->category] ?? 'Flow chart')
            ->addColumn('example_label', fn (FlowChart $flowChart): string => $flowChart->is_example
                ? '<span class="badge text-bg-info">Example</span>'
                : '<span class="badge text-bg-light border">Custom</span>')
            ->addColumn('projects', fn (FlowChart $flowChart): string => $flowChart->projects->pluck('name')->join(', '))
            ->addColumn('actions', fn (FlowChart $flowChart): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">View</a> <a class="btn btn-sm btn-outline-secondary" href="%s">Edit</a> <form class="d-inline" method="POST" action="%s" onsubmit="return confirm(&quot;Delete this flow chart?&quot;);"><input type="hidden" name="_token" value="%s"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>',
                e(route('development.flowcharts.show', $flowChart)),
                e(route('development.flowcharts.edit', $flowChart)),
                e(route('development.flowcharts.destroy', $flowChart)),
                e(csrf_token()),
            ))
            ->rawColumns(['example_label', 'actions'])
            ->toJson();
    }

    public function sops(Request $request): View
    {
        $category = $request->query('category', 'product');
        abort_unless(in_array($category, ['product', 'service', 'education'], true), 404);
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(['example', 'custom'])],
            'subject_name' => ['nullable', 'string', Rule::in(ProductService::query()->orderBy('name')->pluck('name')->all())],
        ]);
        $type = $filters['type'] ?? '';

        return view('development.flowcharts.sops', [
            'category' => $category,
            'type' => $type,
            'subjectName' => $filters['subject_name'] ?? '',
            'subjects' => ProductService::query()->orderBy('name')->pluck('name'),
            'categoryLabel' => [
                'product' => 'Product',
                'service' => 'Service',
                'education' => 'Student FYP',
            ][$category],
        ]);
    }

    public function sopsDatatable(Request $request)
    {
        $category = $request->query('category', 'product');
        abort_unless(in_array($category, ['product', 'service', 'education'], true), 404);
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(['example', 'custom'])],
            'subject_name' => ['nullable', 'string', Rule::in(ProductService::query()->orderBy('name')->pluck('name')->all())],
        ]);
        $type = $filters['type'] ?? '';
        $flowCharts = FlowChart::query()->where('category', $category);

        if ($type !== '') {
            $flowCharts->where('is_example', $type === 'example');
        }
        if (($filters['subject_name'] ?? '') !== '') {
            $flowCharts->where('subject_name', $filters['subject_name']);
        }

        return DataTables::eloquent($flowCharts)
            ->addColumn('example_label', fn (FlowChart $flowChart): string => $flowChart->is_example
                ? '<span class="badge text-bg-info">Example</span>'
                : '<span class="badge text-bg-light border">Custom</span>')
            ->addColumn('actions', fn (FlowChart $flowChart): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">Open SOP</a>',
                e(route('development.flowcharts.show', $flowChart)),
            ))
            ->rawColumns(['example_label', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        $category = in_array(request()->query('category'), ['product', 'service', 'education'], true)
            ? request()->query('category')
            : 'website';
        $projectId = request()->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ])['project_id'] ?? null;

        return view('development.flowcharts.create', [
            'projectId' => $projectId,
            'flowChart' => new FlowChart(['steps' => [
                ...($category === 'website'
                    ? [
                        ['id' => (string) Str::uuid(), 'label' => '', 'kind' => 'page', 'connections' => []],
                        ['id' => (string) Str::uuid(), 'label' => '', 'kind' => 'page', 'connections' => []],
                    ]
                    : SopTemplate::steps($category)),
            ], 'category' => $category]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $projectId = $request->validate([
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ])['project_id'] ?? null;
        $validatedData = $this->validatedData($request);
        abort_if($validatedData['is_example'] && $projectId !== null, 422, 'Example flow charts must be duplicated before they can be linked to projects.');

        $flowChart = DB::transaction(function () use ($validatedData, $projectId): FlowChart {
            $flowChart = FlowChart::create($validatedData);
            if ($projectId !== null) {
                $flowChart->projects()->attach($projectId);
            }

            return $flowChart;
        });

        return redirect()
            ->route('development.flowcharts.show', $flowChart)
            ->with('success', 'Flow chart created successfully.');
    }

    public function show(FlowChart $flowchart): View
    {
        return view('development.flowcharts.show', [
            'flowChart' => $flowchart,
            'linkedProjects' => $flowchart->projects()->orderBy('name')->get(),
            'stepTasks' => $flowchart->tasks()->with('project')->orderBy('id')->get(),
        ]);
    }

    public function addStepsToTasks(Request $request, FlowChart $flowchart): RedirectResponse
    {
        abort_if($flowchart->is_example, 404);

        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
        ]);
        $project = $flowchart->projects()->whereKey($data['project_id'])->first();

        if (! $project) {
            throw ValidationException::withMessages([
                'project_id' => 'Choose a project linked to this flow chart.',
            ]);
        }

        $createdCount = 0;
        DB::transaction(function () use ($flowchart, $project, &$createdCount): void {
            foreach ($flowchart->steps as $step) {
                $task = $project->tasks()->firstOrCreate(
                    [
                        'flow_chart_id' => $flowchart->id,
                        'flow_chart_step_id' => $step['id'],
                    ],
                    [
                        'title' => $step['label'],
                        'description' => 'Created from flow chart: '.$flowchart->title,
                        'status' => 'backlog',
                        'priority' => 'medium',
                    ],
                );

                if ($task->wasRecentlyCreated) {
                    $createdCount++;
                }
            }
        });

        return redirect()
            ->route('development.flowcharts.show', $flowchart)
            ->with('success', $createdCount > 0
                ? "{$createdCount} task(s) created from the flow chart steps."
                : 'Tasks for this flow chart already exist in the selected project.');
    }

    public function saveLayout(Request $request, FlowChart $flowchart): RedirectResponse
    {
        $data = $request->validate([
            'positions' => ['required', 'array', 'min:1', 'max:30'],
            'positions.*.id' => ['required', 'string', 'max:64', 'distinct:strict'],
            'positions.*.x' => ['required', 'integer', 'min:0', 'max:20000'],
            'positions.*.y' => ['required', 'integer', 'min:0', 'max:20000'],
        ]);
        $positions = collect($data['positions'])->keyBy('id');
        $steps = collect($flowchart->steps)->map(function (array $step) use ($positions): array {
            if ($positions->has($step['id'])) {
                $step['x'] = (int) $positions[$step['id']]['x'];
                $step['y'] = (int) $positions[$step['id']]['y'];
            }

            return $step;
        });
        abort_if($positions->keys()->diff(collect($flowchart->steps)->pluck('id'))->isNotEmpty(), 422, 'A saved position referenced a node outside this flow chart.');

        $flowchart->update(['steps' => $steps->all()]);

        return redirect()->route('development.flowcharts.show', $flowchart)
            ->with('success', 'Flow chart positions saved.');
    }

    public function edit(FlowChart $flowchart): View
    {
        return view('development.flowcharts.edit', [
            'flowChart' => $flowchart,
        ]);
    }

    public function update(Request $request, FlowChart $flowchart): RedirectResponse
    {
        $data = $this->validatedData($request);
        abort_if($data['is_example'] && $flowchart->projects()->exists(), 422, 'Unlink this flow chart from its projects before marking it as an example.');
        $flowchart->update($data);

        return redirect()
            ->route('development.flowcharts.show', $flowchart)
            ->with('success', 'Flow chart updated successfully.');
    }

    public function destroy(FlowChart $flowchart): RedirectResponse
    {
        $flowchart->delete();

        return redirect()
            ->route('development.flowcharts.index')
            ->with('success', 'Flow chart deleted successfully.');
    }

    private function validatedData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['sometimes', 'required', Rule::in(['website', 'process', 'product', 'service', 'education'])],
            'subject_name' => ['nullable', 'required_if:category,product,service,education', 'string', 'max:255'],
            'is_example' => ['sometimes', 'boolean'],
            'steps' => ['required', 'array', 'min:1', 'max:30'],
            'steps.*.id' => ['required', 'string', 'max:64', 'distinct:strict'],
            'steps.*.label' => ['required', 'string', 'max:255'],
            'steps.*.kind' => ['required', Rule::in(self::STEP_KINDS)],
            'steps.*.x' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:20000'],
            'steps.*.y' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:20000'],
            'steps.*.connections' => ['sometimes', 'array', 'max:29'],
            'steps.*.connections.*' => ['required', 'string', 'max:64'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $steps = $request->input('steps', []);
            if (! is_array($steps)) {
                return;
            }
            $ids = array_column($steps, 'id');

            foreach ($steps as $index => $step) {
                if (! is_array($step)) {
                    continue;
                }
                $connections = $step['connections'] ?? [];
                if (! is_array($connections)) {
                    continue;
                }

                if (count($connections) !== count(array_unique($connections))) {
                    $validator->errors()->add(
                        "steps.{$index}.connections",
                        'A node cannot connect to the same page more than once.',
                    );
                }

                foreach ($connections as $connection) {
                    if (! in_array($connection, $ids, true)) {
                        $validator->errors()->add(
                            "steps.{$index}.connections",
                            'Choose a connection target from this flow chart.',
                        );
                    } elseif ($connection === ($step['id'] ?? null)) {
                        $validator->errors()->add(
                            "steps.{$index}.connections",
                            'A step cannot connect to itself.',
                        );
                    }
                }
            }
        });

        return [
            ...$validator->validate(),
            'is_example' => $request->boolean('is_example'),
        ];
    }
}
