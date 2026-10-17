@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Administration" title="Roles &amp; permissions"
        description="Manage access rules by creating roles and assigning permissions."
        :back-url="route('dashboard')" back-label="Dashboard" />
    <div class="mb-3">
        <a class="btn btn-outline-primary" href="{{ route('admin.users.index') }}"><span class="fas fa-users me-2" aria-hidden="true"></span>Manage members</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <p class="fw-semibold mb-1">Please correct the following:</p>
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <form class="card h-100" method="POST" action="{{ route('admin.access.roles.store') }}">
                @csrf
                <div class="card-header bg-body"><h2 class="h5 mb-0">Create a role</h2></div>
                <div class="card-body">
                    <label class="form-label" for="new-role-name">Role name</label>
                    <input class="form-control" id="new-role-name" name="name" value="{{ old('name') }}"
                        placeholder="for example, supervisor" maxlength="100" required>
                    <div class="form-text">Use letters, numbers, dashes, or underscores.</div>
                </div>
                <div class="card-footer bg-body"><button class="btn btn-primary" type="submit">Create role</button></div>
            </form>
        </div>
        <div class="col-lg-6">
            <form class="card h-100" method="POST" action="{{ route('admin.access.permissions.store') }}">
                @csrf
                <div class="card-header bg-body"><h2 class="h5 mb-0">Create a permission</h2></div>
                <div class="card-body">
                    <label class="form-label" for="new-permission-name">Permission name</label>
                    <input class="form-control" id="new-permission-name" name="name" value="{{ old('name') }}"
                        placeholder="for example, projects.approve" maxlength="150" required>
                    <div class="form-text">Use a descriptive name; dots, dashes, and underscores are supported.</div>
                </div>
                <div class="card-footer bg-body"><button class="btn btn-primary" type="submit">Create permission</button></div>
            </form>
        </div>
    </div>

    <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Roles</h2>
            <span class="badge text-bg-light border">{{ $roles->count() }}</span>
        </div>
        <div class="row g-3">
            @forelse ($roles as $role)
                <div class="col-xl-6">
                    <form class="card h-100" method="POST" action="{{ route('admin.access.roles.update', $role) }}">
                        @csrf
                        @method('PUT')
                        <div class="card-header bg-body d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <h3 class="h5 mb-0">{{ $role->name }}</h3>
                                <span class="small text-body-secondary">{{ $role->users_count }} assigned user(s)</span>
                            </div>
                            @if ($role->name !== 'admin')
                                <button class="btn btn-sm btn-outline-danger" type="submit" form="delete-role-{{ $role->id }}">Delete</button>
                            @else
                                <span class="badge text-bg-warning">Admin access gate</span>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="role-name-{{ $role->id }}">Role name</label>
                                <input class="form-control" id="role-name-{{ $role->id }}" name="name"
                                    value="{{ $role->name }}" maxlength="100" required @readonly($role->name === 'admin')>
                            </div>
                            <fieldset>
                                <legend class="form-label">Permissions</legend>
                                @forelse ($permissions as $permission)
                                    <label class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="permission_ids[]"
                                            value="{{ $permission->id }}" @checked($role->permissions->contains('id', $permission->id))>
                                        <span class="form-check-label">{{ $permission->name }}</span>
                                    </label>
                                @empty
                                    <p class="small text-body-secondary">Create permissions to assign them to this role.</p>
                                @endforelse
                            </fieldset>
                        </div>
                        <div class="card-footer bg-body d-flex justify-content-between align-items-center">
                            <button class="btn btn-sm btn-primary" type="submit">Save role</button>
                            <span class="small text-body-secondary">{{ $role->permissions->count() }} permission(s)</span>
                        </div>
                    </form>
                    @if ($role->name !== 'admin')
                        <form id="delete-role-{{ $role->id }}" method="POST" action="{{ route('admin.access.roles.destroy', $role) }}"
                            onsubmit="return confirm('Delete the {{ $role->name }} role?');">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                </div>
            @empty
                <div class="col-12"><div class="alert alert-info mb-0">No roles have been created.</div></div>
            @endforelse
        </div>
    </section>

    <div class="card mb-4">        <div class="card-header bg-body d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0">Permissions</h2>
            <span class="badge text-bg-light border">{{ $permissions->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Permission</th><th>Assigned to roles</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($permissions as $permission)
                        <tr>
                            <td class="fw-semibold">{{ $permission->name }}</td>
                            <td>{{ $permission->roles()->count() }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.access.permissions.destroy', $permission) }}"
                                    onsubmit="return confirm('Delete the {{ $permission->name }} permission?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-body-secondary py-4">No permissions created yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
