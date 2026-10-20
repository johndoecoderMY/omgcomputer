<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminAccessController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['admin', 'member', 'customer'])
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get();
        $selectedRole = $roles->firstWhere('id', $request->integer('role')) ?? $roles->first();

        if ($selectedRole) {
            $selectedRole->load(['users' => fn ($query) => $query->orderBy('name')]);
        }

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->withCount('roles')
            ->orderBy('name')
            ->get();

        return view('admin.access.index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionGroups' => $permissions->groupBy(fn (Permission $permission): string => Str::contains($permission->name, '.')
                ? Str::before($permission->name, '.')
                : 'General'),
            'selectedRole' => $selectedRole,
            'selectedRoleDescription' => match ($selectedRole?->name) {
                'admin' => 'Full administrative access to manage users, roles, and workspace settings.',
                'member' => 'Staff access to tickets, own or assigned projects and tasks, customer records, and read-only workspace sections.',
                'customer' => 'Read-only access to tickets, bookings, projects, and project-linked billing documents.',
                default => '',
            },
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        abort(403, 'Only the admin, member, and customer roles are supported.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        abort_unless(in_array($role->name, ['admin', 'member', 'customer'], true), 404);
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('roles')->where('guard_name', 'web')->ignore($role->id),
            ],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['required', 'integer', 'distinct', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ]);
        abort_unless($data['name'] === $role->name, 403, 'Built-in role names cannot be changed.');

        DB::transaction(function () use ($role, $data): void {
            if ($role->name === 'admin') {
                $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
            } else {
                $role->syncPermissions(Permission::query()
                    ->where('guard_name', 'web')
                    ->whereIn('id', $data['permission_ids'] ?? [])
                    ->get());
            }
        });

        return $this->backWith('Role permissions updated.', 'success', $role->id);
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        abort(403, 'The admin, member, and customer roles cannot be deleted.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:150',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('permissions')->where('guard_name', 'web'),
            ],
        ]);
        $permission = Permission::create(['name' => $data['name'], 'guard_name' => 'web']);
        Role::query()->where('name', 'admin')->where('guard_name', 'web')->firstOrFail()->givePermissionTo($permission);

        return $this->backWith('Permission created.', 'success', $request->integer('selected_role_id') ?: null);
    }

    public function destroyPermission(Permission $permission): RedirectResponse
    {
        abort_unless($permission->guard_name === 'web', 404);
        if ($permission->roles()->exists() || $permission->users()->exists()) {
            return $this->backWith('Remove this permission from all roles and users before deleting it.', 'error');
        }

        $permission->delete();

        return $this->backWith('Permission deleted.', 'success', request()->integer('selected_role_id') ?: null);
    }

    public function updateUserRoles(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role_ids' => ['required', 'array', 'size:1'],
            'role_ids.*' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'web')],
        ]);
        $allowedRoleIds = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['admin', 'member', 'customer'])
            ->pluck('id')
            ->all();
        abort_if(array_diff($data['role_ids'], $allowedRoleIds) !== [], 422, 'Only admin, member, and customer roles can be assigned.');
        $administratorRole = Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->firstOrFail();
        $wasAdministrator = $user->hasRole($administratorRole);
        $willBeAdministrator = in_array($administratorRole->id, $data['role_ids']);
        if ($wasAdministrator && ! $willBeAdministrator && $administratorRole->users()->count() <= 1) {
            return $this->backWith('The last administrator cannot be demoted.', 'error');
        }

        $user->syncRoles(Role::query()->where('guard_name', 'web')->whereIn('id', $data['role_ids'])->get());

        return redirect()->route('admin.users.edit', $user)->with('success', 'User roles updated.');
    }

    private function backWith(string $message, string $key = 'success', ?int $roleId = null): RedirectResponse
    {
        return redirect()->route('admin.access.index', $roleId ? ['role' => $roleId] : [])->with($key, $message);
    }
}
