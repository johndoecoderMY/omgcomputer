<?php

namespace App\Http\Controllers;

use App\Models\FlowChart;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FlowChartController extends Controller
{
    private const STEP_KINDS = ['page', 'action', 'decision', 'start', 'end'];

    public function index(): View
    {
        return view('development.flowcharts.index', [
            'flowCharts' => FlowChart::latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('development.flowcharts.create', [
            'flowChart' => new FlowChart(['steps' => [
                ['id' => (string) Str::uuid(), 'label' => '', 'kind' => 'page', 'connections' => []],
                ['id' => (string) Str::uuid(), 'label' => '', 'kind' => 'page', 'connections' => []],
            ]]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $flowChart = FlowChart::create($this->validatedData($request));

        return redirect()
            ->route('development.flowcharts.show', $flowChart)
            ->with('success', 'Flow chart created successfully.');
    }

    public function show(FlowChart $flowchart): View
    {
        return view('development.flowcharts.show', [
            'flowChart' => $flowchart,
            'projects' => Project::query()->orderBy('name')->get(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'tasks' => $flowchart->tasks()->with('project')->get()->groupBy('flow_chart_step_id'),
        ]);
    }

    public function storeTask(Request $request, FlowChart $flowchart): RedirectResponse
    {
        $data = $request->validate([
            'step_id' => ['required', 'string', 'max:64'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $step = collect($flowchart->steps)->firstWhere('id', $data['step_id']);
        abort_if($step === null, 404, 'That node is no longer part of this flow chart.');

        DB::transaction(function () use ($flowchart, $step, $data): void {
            Task::create([
                'project_id' => $data['project_id'],
                'assigned_to' => $data['assigned_to'] ?? null,
                'flow_chart_id' => $flowchart->id,
                'flow_chart_step_id' => $step['id'],
                'title' => $step['label'],
                'description' => "Created from flow chart: {$flowchart->title} ({$step['kind']} node).",
                'status' => 'backlog',
                'priority' => 'medium',
            ]);
        });

        return redirect()->route('development.flowcharts.show', $flowchart)
            ->with('success', "Task created from node: {$step['label']}.");
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
        $flowchart->update($this->validatedData($request));

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
            if (!is_array($steps)) {
                return;
            }
            $ids = array_column($steps, 'id');

            foreach ($steps as $index => $step) {
                if (!is_array($step)) {
                    continue;
                }
                $connections = $step['connections'] ?? [];
                if (!is_array($connections)) {
                    continue;
                }

                if (count($connections) !== count(array_unique($connections))) {
                    $validator->errors()->add(
                        "steps.{$index}.connections",
                        'A node cannot connect to the same page more than once.',
                    );
                }

                foreach ($connections as $connection) {
                    if (!in_array($connection, $ids, true)) {
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

        return $validator->validate();
    }
}
