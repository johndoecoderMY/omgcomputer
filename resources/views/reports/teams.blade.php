@extends('layouts.app')

@section('title', 'Team Performance Report')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Reports / People" title="Team performance"
        description="Compare assigned workload, completed work, and active tasks across the team."
        :back-url="route('reports.index')" back-label="All reports">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('teams.index') }}">Manage teams</a>
        </x-slot:actions>
    </x-page-header>
    @include('reports._tabs', ['active' => 'teams'])

    <div class="alert alert-info d-flex align-items-center gap-2" role="status">
        <span class="fas fa-circle-info" aria-hidden="true"></span>
        <span>Performance is based on tasks assigned in the project planner. <strong>{{ $unassignedTasks }}</strong> task(s) are currently unassigned.</span>
    </div>

    <div class="row g-3 mb-4">
        @foreach ([
            ['label' => 'Team members', 'value' => number_format($users->count()), 'icon' => 'fa-users'],
            ['label' => 'Assigned tasks', 'value' => number_format($users->sum('assigned_tasks_count')), 'icon' => 'fa-list-check'],
            ['label' => 'Completed tasks', 'value' => number_format($users->sum('completed_tasks_count')), 'icon' => 'fa-circle-check'],
            ['label' => 'Unassigned tasks', 'value' => number_format($unassignedTasks), 'icon' => 'fa-user-clock'],
        ] as $metric)
            <div class="col-6 col-xl-3">
                <div class="card h-100 p-2">
                    <div class="card-header bg-body"><h2 class="h5 mb-0 text-primary">{{ $metric['label'] }}</h2></div>
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <span class="fs-4 fw-semibold">{{ $metric['value'] }}</span>
                        <span class="fas {{ $metric['icon'] }} fs-3 text-primary opacity-50" aria-hidden="true"></span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card p-2 mb-4">
        <div class="card-header bg-body">
            <h2 class="h5 mb-1 text-primary">Workload comparison</h2>
            <p class="small text-body-secondary mb-0">Top 8 members by assigned tasks.</p>
        </div>
        <div class="card-body">
            @if ($teamChart->isEmpty())
                <p class="text-body-secondary text-center py-5 mb-0">No assigned task data to chart yet.</p>
            @else
                <div id="team-performance-chart" role="img" aria-label="Assigned, active, and completed tasks by team member" style="height: 340px"></div>
            @endif
        </div>
    </div>

    <div class="card p-2">
        <div class="card-header bg-body d-flex justify-content-between align-items-center">
            <div><h2 class="h5 mb-1 text-primary">Member performance</h2><p class="small text-body-secondary mb-0">Task completion rates by assignee.</p></div>
            <span class="badge text-bg-light border">{{ $users->count() }} members</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Team member</th><th class="text-end">Assigned</th><th class="text-end">Completed</th><th class="text-end">In progress</th><th style="min-width: 180px">Completion rate</th></tr></thead>
                <tbody>
                    @forelse ($users as $user)
                        @php($rate = $user->assigned_tasks_count ? round($user->completed_tasks_count / $user->assigned_tasks_count * 100) : 0)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $user->name }}</div>
                                <div class="small text-body-secondary">{{ $user->email }}</div>
                                <div class="small">{{ $user->teams->map(fn ($team) => ($team->department?->name ? $team->department->name.' / ' : '').$team->name)->join(', ') ?: 'Not assigned to a team' }}</div>
                            </td>
                            <td class="text-end">{{ $user->assigned_tasks_count }}</td>
                            <td class="text-end">{{ $user->completed_tasks_count }}</td>
                            <td class="text-end">{{ $user->active_tasks_count }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 8px">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: {{ $rate }}%"
                                            aria-valuenow="{{ $rate }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small fw-semibold">{{ $rate }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary py-5">No team members found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const element = document.getElementById('team-performance-chart');
        if (!element || !window.echarts) return;
        const rows = @js($teamChart);
        const chart = window.echarts.init(element);
        chart.setOption({
            color: ['#2c7be5', '#00d27a', '#f5803e'],
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
            legend: { top: 0 },
            grid: { top: 40, right: 18, bottom: 20, left: 120 },
            xAxis: { type: 'value', minInterval: 1, splitLine: { lineStyle: { color: '#edf2f9' } } },
            yAxis: { type: 'category', data: rows.map((row) => row.name).reverse(), axisLabel: { color: '#748194', width: 100, overflow: 'truncate' } },
            series: [
                { name: 'Assigned', type: 'bar', data: rows.map((row) => row.assigned).reverse(), barMaxWidth: 18, itemStyle: { borderRadius: [0, 4, 4, 0] } },
                { name: 'Completed', type: 'bar', data: rows.map((row) => row.completed).reverse(), barMaxWidth: 18, itemStyle: { borderRadius: [0, 4, 4, 0] } },
                { name: 'In progress', type: 'bar', data: rows.map((row) => row.active).reverse(), barMaxWidth: 18, itemStyle: { borderRadius: [0, 4, 4, 0] } },
            ],
        });
        window.addEventListener('resize', () => chart.resize());
    })();
</script>
@endpush
