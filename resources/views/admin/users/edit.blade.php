@extends('layouts.app')

@section('title', $accountType === 'customers' ? 'Manage customer account' : 'Manage member')

@section('content')
<div class="container-fluid py-3">
    <x-page-header :eyebrow="$accountType === 'customers' ? 'Manage / Customer accounts' : 'Manage / Members'" :title="$user->name"
        :description="$accountType === 'customers'
            ? 'Update this customer account, its role assignments, and individual permissions.'
            : 'Update this member’s contact details, roles, and individual permissions.'"
        :back-url="route($accountType === 'customers' ? 'admin.users.customers' : 'admin.users.index')"
        :back-label="$accountType === 'customers' ? 'All customer accounts' : 'All members'" />

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form class="card p-2" method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card-body row g-3">
            <div class="col-12 d-flex align-items-center gap-3">
                @if ($user->profile_photo_url)
                    <img src="{{ $user->profile_photo_url }}" alt="" class="rounded-circle object-fit-cover" width="72" height="72">
                @else
                    <span class="avatar avatar-3xl"><span class="avatar-name rounded-circle"><span>{{ str($user->name)->substr(0, 1)->upper() }}</span></span></span>
                @endif
                <div>
                    <div class="fw-semibold">{{ $user->name }}</div>
                    <div class="small text-body-secondary">{{ $user->teams->pluck('name')->join(', ') ?: 'No teams assigned' }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="name">Full name</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="50">
            </div>
            @if ($accountType !== 'customers')
            <div class="col-md-6">
                <label class="form-label" for="team_ids">Teams</label>
                <select class="form-select js-user-search" id="team_ids" name="team_ids[]" multiple data-placeholder="Select one or more teams">
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected(in_array((string) $team->id, array_map('strval', old('team_ids', $user->teams->modelKeys())), true))>
                            {{ $team->department->name }} · {{ $team->name }}
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Select all teams this member belongs to.</div>
            </div>
            @endif
            <div class="col-12">
                <label class="form-label" for="address">Address</label>
                <textarea class="form-control" id="address" name="address" rows="3" maxlength="2000">{{ old('address', $user->address) }}</textarea>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="photo">Profile photo</label>
                <x-image-upload-input id="photo" name="photo" :multiple="false" :required="false" />
                <div class="form-text">JPG, PNG, or WebP; maximum 10 MB.</div>
            </div>
            @if ($user->profile_photo_path)
                <div class="col-md-4 d-flex align-items-end">
                    <label class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="remove_photo" value="1">
                        <span class="form-check-label">Remove current photo</span>
                    </label>
                </div>
            @endif
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a class="btn btn-outline-secondary" href="{{ route('members.show', $user) }}">View profile</a>
            <button class="btn btn-primary" type="submit">Save account</button>
        </div>
    </form>

    <div class="card mt-4">        <div class="card-header bg-body">
            <h2 class="h5 mb-0">Account roles</h2>
            <p class="small text-body-secondary mb-0">Choose one role. The last administrator cannot be demoted.</p>
        </div>
        <form method="POST" action="{{ route('admin.access.users.roles.update', $user) }}">
            @csrf
            @method('PUT')
            <div class="card-body d-flex flex-wrap gap-3">
                @foreach ($roles as $role)
                    <label class="form-check">
                        <input class="form-check-input" type="radio" name="role_ids[]" value="{{ $role->id }}"
                            @checked($user->roles->contains('id', $role->id))>
                        <span class="form-check-label">{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
            <div class="card-footer text-end">
                <button class="btn btn-outline-primary" type="submit">Save roles</button>
            </div>
        </form>
    </div>

    <div class="card mt-4">        <div class="card-header bg-body">
            <h2 class="h5 mb-1">Additional individual permissions</h2>
            <p class="small text-body-secondary mb-0">These permissions are granted directly to this member in addition to permissions inherited from their roles.</p>
        </div>
        <form method="POST" action="{{ route('admin.users.permissions.update', $user) }}">
            @csrf
            @method('PUT')
            <div class="card-body d-flex flex-wrap gap-3">
                @forelse ($permissions as $permission)
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="permission_ids[]" value="{{ $permission->id }}"
                            @checked($user->getDirectPermissions()->contains('id', $permission->id))>
                        <span class="form-check-label">{{ $permission->name }}</span>
                    </label>
                @empty
                    <p class="small text-body-secondary mb-0">No permissions exist yet. Create them in Roles & permissions.</p>
                @endforelse
            </div>
            <div class="card-footer bg-body d-flex justify-content-between align-items-center">
                <span class="small text-body-secondary">{{ $user->getDirectPermissions()->count() }} additional permission(s)</span>
                <button class="btn btn-outline-primary" type="submit">Save additional permissions</button>
            </div>
        </form>
    </div>

    <div class="card border-danger mt-4">        <div class="card-header bg-body">
            <h2 class="h5 mb-1 text-danger">Delete member</h2>
            <p class="small text-body-secondary mb-0">This permanently removes the account. The currently signed-in account and the last administrator cannot be deleted.</p>
        </div>
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <span>{{ $user->email }}</span>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                onsubmit="return confirm('Permanently delete {{ $user->name }}? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger" type="submit">Delete account</button>
            </form>
        </div>
    </div>
</div>
@endsection
