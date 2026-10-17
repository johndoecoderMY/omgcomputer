@extends('layouts.app')

@section('title', $accountType === 'customers' ? 'Customer accounts' : 'Members')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Administration" :title="$accountType === 'customers' ? 'Customer accounts' : 'Members'"
        :description="$accountType === 'customers'
            ? 'Manage sign-in accounts with customer access.'
            : 'Manage staff accounts with admin or member roles, teams, and permissions.'"
        :back-url="route('admin.access.index')" back-label="Roles & permissions">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route($accountType === 'customers' ? 'admin.users.index' : 'admin.users.customers') }}">
                <i class="fas fa-exchange-alt me-2" aria-hidden="true"></i>{{ $accountType === 'customers' ? 'View members' : 'View customer accounts' }}
            </a>
            <a class="btn btn-primary" href="{{ route($accountType === 'customers' ? 'admin.users.customers.create' : 'admin.users.create') }}">
                <i class="fas fa-user-plus me-2" aria-hidden="true"></i>Create {{ $accountType === 'customers' ? 'customer account' : 'member' }}
            </a>
        </x-slot:actions>
    </x-page-header>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif

    <div class="card p-2">        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" data-datatable
                data-source="{{ route($accountType === 'customers' ? 'admin.users.customers.datatable' : 'admin.users.datatable') }}"
                data-columns="{{ json_encode([
                    ['data' => 'name', 'name' => 'users.name'],
                    ['data' => 'email', 'name' => 'users.email'],
                    ['data' => 'teams_list', 'orderable' => false, 'searchable' => false],
                    ['data' => 'roles_list', 'orderable' => false, 'searchable' => false],
                    ['data' => 'permissions_list', 'orderable' => false, 'searchable' => false],
                    ['data' => 'actions', 'orderable' => false, 'searchable' => false],
                ]) }}">
                <thead><tr><th>Name</th><th>Email</th><th>Teams</th><th>Roles</th><th>Additional permissions</th><th>Manage</th></tr></thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection
