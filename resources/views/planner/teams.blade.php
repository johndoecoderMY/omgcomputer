@extends('layouts.app')

@section('title', 'Teams & Technicians')

@section('content')
    <div class="container-fluid py-3">
        <x-page-header eyebrow="Workspace" title="Teams & Technicians"
            description="Team directory, technician assignments, and project workload in one place."
            :breadcrumbs="[['label' => 'This project', 'url' => route('planner.projects')], ['label' => 'Teams']]">
            <x-slot:actions>
                <a class="btn btn-falcon-default" href="{{ route('planner.index') }}"><span
                        class="fas fa-list-check me-2"></span>View project tasks</a>
            </x-slot:actions>
        </x-page-header>
        <section class="card">
            <div class="card-header">
                <h2 class="h5 mb-0">Team members</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive scrollbar">
                    <table class="table table-sm table-hover mb-0 fs-10"
                        data-datatables='{"pageLength":10,"language":{"emptyTable":"No team members found."}}'>
                        <thead class="bg-body-tertiary">
                            <tr>
                                <th class="ps-3">Member</th>
                                <th>Assigned open tasks</th>
                                <th>Completed tasks</th>
                                <th>Workload</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($members as $member)
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-l">
                                                <div class="avatar-name rounded-circle">
                                                    <span>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($member->name, 0, 1)) }}</span>
                                                </div>
                                            </div>
                                            <div><strong>{{ $member->name }}</strong>
                                                <div class="text-600">{{ $member->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $member->open_tasks_count }}</td>
                                    <td>{{ $member->completed_tasks_count }}</td>
                                    <td><span
                                            class="badge rounded-pill badge-subtle-{{ $member->open_tasks_count > 5 ? 'warning' : 'success' }}">{{ $member->open_tasks_count > 5 ? 'Busy' : 'Available' }}</span>
                                    </td>
                                    <td class="text-end pe-3"><a class="btn btn-sm btn-falcon-default"
                                            href="{{ route('planner.index', ['project' => $projectId, 'scope' => 'team', 'assignee' => $member->id]) }}">View
                                            tasks</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
@endsection