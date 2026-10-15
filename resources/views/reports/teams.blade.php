@extends('layouts.app')

@section('title', 'Team Performance Report')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Reports" title="Team performance" :back-url="route('reports.index')" back-label="All reports" />
    @include('reports._tabs', ['active' => 'teams'])
    <div class="alert alert-info">Performance is based on tasks assigned in the project planner. {{ $unassignedTasks }} tasks are currently unassigned.</div>
    <div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Team member</th><th class="text-end">Assigned tasks</th><th class="text-end">Completed</th><th class="text-end">In progress</th><th class="text-end">Completion rate</th></tr></thead><tbody>
        @forelse ($users as $user)@php($rate = $user->assigned_tasks_count ? round($user->completed_tasks_count / $user->assigned_tasks_count * 100) : 0)<tr><td><div class="fw-semibold">{{ $user->name }}</div><div class="small text-body-secondary">{{ $user->email }}</div></td><td class="text-end">{{ $user->assigned_tasks_count }}</td><td class="text-end">{{ $user->completed_tasks_count }}</td><td class="text-end">{{ $user->active_tasks_count }}</td><td class="text-end"><div class="d-flex align-items-center justify-content-end gap-2"><div class="progress" style="width:100px;height:7px"><div class="progress-bar" role="progressbar" style="width:{{ $rate }}%" aria-valuenow="{{ $rate }}" aria-valuemin="0" aria-valuemax="100"></div></div>{{ $rate }}%</div></td></tr>
        @empty<tr><td colspan="5" class="text-center text-body-secondary py-4">No team members found.</td></tr>@endforelse
    </tbody></table></div></div>
</div>
@endsection