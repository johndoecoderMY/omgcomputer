@extends('layouts.app')

@section('title', 'Members')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Administration" title="Members"
        description="Manage member accounts, role assignments, and additional individual permissions."
        :back-url="route('admin.access.index')" back-label="Roles &amp; permissions">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ route('admin.users.create') }}">
                <i class="fas fa-user-plus me-2" aria-hidden="true"></i>Create member
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <section class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable
                data-source="{{ route('admin.users.datatable') }}"
                data-columns="{{ json_encode([
                    ['data' => 'name', 'name' => 'users.name'],
                    ['data' => 'email', 'name' => 'users.email'],
                    ['data' => 'team_name', 'orderable' => false, 'searchable' => false],
                    ['data' => 'roles_list', 'orderable' => false, 'searchable' => false],
                    ['data' => 'permissions_list', 'orderable' => false, 'searchable' => false],
                    ['data' => 'actions', 'orderable' => false, 'searchable' => false],
                ]) }}">
                <thead><tr><th>Name</th><th>Email</th><th>Team</th><th>Roles</th><th>Additional permissions</th><th>Manage</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </section>
</div>
@endsection
