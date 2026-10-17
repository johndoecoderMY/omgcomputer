<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminAccessController extends Controller
{
    public function index(): View
    {
        return view('admin.access.index', [
            'roles' => Role::query()->where('guard_name', 'web')->with('permissions')->withCount('users')->orderBy('name')->get(),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get(),
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('roles')->where('guard_name', 'web')],
        ]);
        Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        return $this->backWith('Role created.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
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

        DB::transaction(function () use ($role, $data): void {
            if ($role->name !== 'admin') {
                $role->name = $data['name'];
                $role->save();
            }
            $role->syncPermissions(Permission::query()
                ->where('guard_name', 'web')
                ->whereIn('id', $data['permission_ids'] ?? [])
                ->get());
        });

        return $this->backWith('Role permissions updated.');
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        if ($role->name === 'admin' || $role->users()->exists()) {
            return $this->backWith('The administrator role or a role assigned to users cannot be deleted.', 'error');
        }

        $role->delete();

        return $this->backWith('Role deleted.');
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
        Permission::create(['name' => $data['name'], 'guard_name' => 'web']);

        return $this->backWith('Permission created.');
    }

    public function destroyPermission(Permission $permission): RedirectResponse
    {
        abort_unless($permission->guard_name === 'web', 404);
        if ($permission->roles()->exists() || $permission->users()->exists()) {
            return $this->backWith('Remove this permission from all roles and users before deleting it.', 'error');
        }

        $permission->delete();

        return $this->backWith('Permission deleted.');
    }

    public function updateUserRoles(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'integer', 'distinct', Rule::exists('roles', 'id')->where('guard_name', 'web')],
        ]);
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

    private function backWith(string $message, string $key = 'success'): RedirectResponse
    {
        return redirect()->route('admin.access.index')->with($key, $message);
    }
}
