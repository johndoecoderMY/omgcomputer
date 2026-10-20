@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
<div class="container-fluid py-3">
    <x-page-header eyebrow="Administration" title="Roles &amp; permissions"
        description="Choose a role to review its members and configure its permissions."
        :back-url="route('dashboard')" back-label="Dashboard">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('admin.users.index') }}">
                <span class="fas fa-users me-2" aria-hidden="true"></span>Members
            </a>
            <a class="btn btn-outline-primary" href="{{ route('admin.users.customers') }}">
                <span class="fas fa-user-tag me-2" aria-hidden="true"></span>Customer accounts
            </a>
        </x-slot:actions>
    </x-page-header>

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
        <div class="col-lg-12">
            <form class="card h-100 p-2" method="POST" action="{{ route('admin.access.permissions.store') }}">
                @csrf
                @if ($selectedRole)
                    <input type="hidden" name="selected_role_id" value="{{ $selectedRole->id }}">
                @endif
                <div class="card-header bg-body"><h2 class="h5 mb-0">Add permission</h2></div>
                <div class="card-body d-flex flex-wrap align-items-end gap-2">
                    <div class="flex-grow-1">
                        <label class="form-label" for="new-permission-name">Permission name</label>
                        <input class="form-control" id="new-permission-name" name="name" value="{{ old('name') }}"
                            placeholder="for example, projects.approve" maxlength="150" required>
                    </div>
                    <button class="btn btn-primary" type="submit">Create permission</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card p-2 mb-3">
                <div class="card-header bg-body d-flex justify-content-between align-items-center">
                    <h2 class="h5 mb-0">Roles</h2>
                    <span class="badge text-bg-light border">{{ $roles->count() }}</span>
                </div>
                <div class="list-group list-group-flush">
                    @forelse ($roles as $role)
                        <div class="list-group-item d-flex align-items-center justify-content-between gap-2 {{ $selectedRole?->is($role) ? 'active' : '' }}">
                            <a class="d-flex align-items-center gap-3 text-decoration-none flex-grow-1 {{ $selectedRole?->is($role) ? 'text-white' : 'text-body' }}"
                                href="{{ route('admin.access.index', ['role' => $role->id]) }}">
                                <span class="avatar avatar-l">
                                    <span class="avatar-name rounded-circle {{ $role->name === 'admin' ? 'bg-danger-subtle text-danger' : ($role->name === 'customer' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary') }}">
                                        <span class="fas {{ $role->name === 'admin' ? 'fa-shield-alt' : ($role->name === 'customer' ? 'fa-user-tag' : 'fa-user') }}"></span>
                                    </span>
                                </span>
                                <span>
                                    <span class="d-block fw-semibold">{{ ucfirst($role->name) }}</span>
                                    <span class="small {{ $selectedRole?->is($role) ? 'text-white-50' : 'text-body-secondary' }}">{{ $role->users_count }} assigned user(s)</span>
                                </span>
                            </a>
                        </div>
                    @empty
                        <p class="text-body-secondary p-3 mb-0">No roles have been created yet.</p>
                    @endforelse
                </div>
            </div>

            @if ($selectedRole)
                <div class="card p-2">
                    <div class="card-header bg-body"><h2 class="h5 mb-0">Role information</h2></div>
                    <div class="card-body">
                        <dl class="mb-0">
                            <dt class="small text-body-secondary">Role name</dt>
                            <dd class="mb-3">{{ ucfirst($selectedRole->name) }}</dd>
                            <dt class="small text-body-secondary">Description</dt>
                            <dd class="mb-3">{{ $selectedRoleDescription }}</dd>
                            <dt class="small text-body-secondary">Created</dt>
                            <dd class="mb-3">{{ $selectedRole->created_at?->format('M j, Y') ?? '—' }}</dd>
                            <dt class="small text-body-secondary">Last modified</dt>
                            <dd class="mb-0">{{ $selectedRole->updated_at?->format('M j, Y') ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-xl-8">
            @if ($selectedRole)
                <form class="card p-2 mb-3" method="POST" action="{{ route('admin.access.roles.update', $selectedRole) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" value="{{ $selectedRole->name }}">
                    <div class="card-header bg-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h2 class="h5 mb-1">Permissions matrix</h2>
                            <p class="small text-body-secondary mb-0">Configure access for the {{ ucfirst($selectedRole->name) }} role.</p>
                        </div>
                        <button class="btn btn-primary" type="submit">
                            <span class="fas fa-check me-1" aria-hidden="true"></span>Save changes
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-bordered">
                            <thead>
                                <tr><th>Module / permission</th><th class="text-center" style="width: 130px">Enabled</th></tr>
                            </thead>
                            @forelse ($permissionGroups as $module => $modulePermissions)
                                <tbody>
                                    <tr class="table-light">
                                        <th>{{ ucfirst(str_replace(['-', '_'], ' ', $module)) }}</th>
                                        <th class="text-center">
                                            <label class="form-check d-inline-flex align-items-center gap-2 mb-0">
                                                <span class="small">All</span>
                                                <input class="form-check-input mb-0" type="checkbox" data-permission-group="{{ $loop->index }}"
                                                    aria-label="Toggle all {{ $module }} permissions">
                                            </label>
                                        </th>
                                    </tr>
                                    @foreach ($modulePermissions as $permission)
                                        <tr>
                                            <td class="ps-4">{{ $permission->name }}</td>
                                            <td class="text-center">
                                                <input class="form-check-input" type="checkbox" name="permission_ids[]"
                                                    value="{{ $permission->id }}" data-permission-item="{{ $loop->parent->index }}"
                                                    aria-label="Enable {{ $permission->name }}"
                                                    @checked($selectedRole->permissions->contains('id', $permission->id))>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            @empty
                                <tbody><tr><td colspan="2" class="text-center text-body-secondary py-4">No permissions have been created yet.</td></tr></tbody>
                            @endforelse
                        </table>
                    </div>
                    <div class="card-footer bg-body d-flex justify-content-between align-items-center">
                        @if ($selectedRole->name === 'admin')
                            <span class="small text-body-secondary">The admin role is protected from deletion and renaming.</span>
                        @else
                            <span class="small text-body-secondary">Changes apply to all users assigned this role.</span>
                        @endif
                        <button class="btn btn-primary" type="submit">Save permissions</button>
                    </div>
                </form>

                <div class="card p-2">
                    <div class="card-header bg-body d-flex justify-content-between align-items-center">
                        <h2 class="h5 mb-0">Users with {{ ucfirst($selectedRole->name) }} role</h2>
                        <span class="badge text-bg-primary">{{ $selectedRole->users->count() }} users</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 table-bordered">
                            <thead><tr><th>User</th><th>Email</th><th>Status</th><th>Account created</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($selectedRole->users as $user)
                                    <tr>
                                        <td class="fw-semibold">{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td><span class="badge text-bg-success">Active</span></td>
                                        <td>{{ $user->created_at?->format('M j, Y') ?? '—' }}</td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.users.edit', $user) }}">Manage</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-body-secondary py-4">No users have this role.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="card p-2"><div class="card-body text-center text-body-secondary">Create a role to configure its permissions.</div></div>
            @endif
        </div>
    </div>

    <div class="card p-2 mt-4">
        <div class="card-header bg-body d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0">All permissions</h2>
            <span class="badge text-bg-light border">{{ $permissions->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 table-bordered">
                <thead><tr><th>Permission</th><th>Assigned to roles</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                    @forelse ($permissions as $permission)
                        <tr>
                            <td class="fw-semibold">{{ $permission->name }}</td>
                            <td>{{ $permission->roles_count }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.access.permissions.destroy', $permission) }}"
                                    onsubmit="return confirm('Delete the {{ $permission->name }} permission?');">
                                    @csrf
                                    @method('DELETE')
                                    @if ($selectedRole)
                                        <input type="hidden" name="selected_role_id" value="{{ $selectedRole->id }}">
                                    @endif
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

@push('scripts')
<script>
    document.querySelectorAll('[data-permission-group]').forEach((groupToggle) => {
        const groupId = groupToggle.dataset.permissionGroup;
        const items = [...document.querySelectorAll(`[data-permission-item="${groupId}"]`)];
        const refresh = () => {
            const checkedCount = items.filter((item) => item.checked).length;
            groupToggle.checked = items.length > 0 && checkedCount === items.length;
            groupToggle.indeterminate = checkedCount > 0 && checkedCount < items.length;
        };

        groupToggle.addEventListener('change', () => {
            items.forEach((item) => { item.checked = groupToggle.checked; });
            groupToggle.indeterminate = false;
        });
        items.forEach((item) => item.addEventListener('change', refresh));
        refresh();
    });
</script>
@endpush
