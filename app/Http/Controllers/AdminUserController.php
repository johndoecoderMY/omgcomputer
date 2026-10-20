<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', ['accountType' => 'members']);
    }

    public function customers(): View
    {
        return view('admin.users.index', ['accountType' => 'customers']);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'teams' => Team::query()->with('department')->orderBy('name')->get(),
            'accountType' => 'members',
        ]);
    }

    public function createCustomer(): View
    {
        return view('admin.users.create', [
            'teams' => collect(),
            'accountType' => 'customers',
        ]);
    }

    public function datatable()
    {
        return $this->usersDatatable('members');
    }

    public function customersDatatable()
    {
        return $this->usersDatatable('customers');
    }

    private function usersDatatable(string $accountType)
    {
        $roleNames = $accountType === 'customers' ? ['customer'] : ['admin', 'member'];

        $query = User::query()
            ->whereHas('roles', fn (Builder $roles) => $roles->whereIn('name', $roleNames));
        if ($accountType === 'customers') {
            $query->whereDoesntHave('roles', fn (Builder $roles) => $roles->whereIn('name', ['admin', 'member']));
        }

        return DataTables::eloquent($query->with(['teams', 'roles', 'permissions']))
            ->addColumn('teams_list', fn (User $user): string => e($user->teams->pluck('name')->join(', ') ?: 'Unassigned'))
            ->addColumn('roles_list', fn (User $user): string => e($user->roles->pluck('name')->join(', ') ?: 'No roles'))
            ->addColumn('permissions_list', fn (User $user): string => e(
                $user->getDirectPermissions()->pluck('name')->join(', ') ?: 'Role permissions only',
            ))
            ->addColumn('actions', fn (User $user): string => sprintf(
                '<a class="btn btn-sm btn-outline-primary" href="%s">View profile</a>',
                e(route('members.show', $user)),
            ))
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->storeAccount($request, 'member');
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        return $this->storeAccount($request, 'customer');
    }

    private function storeAccount(Request $request, string $roleName): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['required', 'integer', 'distinct', 'exists:teams,id'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $user = DB::transaction(function () use ($data, $role): User {
            $user = User::query()->create([
                ...collect($data)->except(['password', 'team_ids'])->all(),
                'password' => Hash::make($data['password']),
                'force_password_change' => true,
            ]);
            $user->teams()->sync($data['team_ids'] ?? []);
            $user->assignRole($role);

            return $user;
        });

        return redirect()->route('admin.users.edit', $user)
            ->with('success', ucfirst($roleName).' account created. They must change their password at first sign-in.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load(['teams.department', 'roles', 'permissions']),
            'teams' => Team::query()->with('department')->orderBy('name')->get(),
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'accountType' => $user->hasRole('customer') && ! $user->hasAnyRole(['admin', 'member']) ? 'customers' : 'members',
        ]);
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('permissions', 'id')->where('guard_name', 'web'),
            ],
        ]);

        $user->syncPermissions(Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('id', $data['permission_ids'] ?? [])
            ->get());

        return redirect()->route('admin.users.edit', $user)->with('success', 'Member permissions updated.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['required', 'integer', 'distinct', 'exists:teams,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['sometimes', 'boolean'],
        ]);

        $previousPhotoPath = $user->profile_photo_path;
        $user->fill(collect($data)->only(['name', 'email', 'phone', 'address'])->all());

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('profile-photos', 'public');
            if ($photoPath === false) {
                throw new \RuntimeException('The profile photo could not be stored.');
            }
            $user->profile_photo_path = $photoPath;
        } elseif ($request->boolean('remove_photo')) {
            $user->profile_photo_path = null;
        }

        $user->save();
        $user->teams()->sync($data['team_ids'] ?? []);
        if ($previousPhotoPath !== null && $previousPhotoPath !== $user->profile_photo_path) {
            if (! Storage::disk('public')->delete($previousPhotoPath)) {
                throw new \RuntimeException('The previous profile photo could not be removed.');
            }
        }

        return redirect()->route('admin.users.edit', $user)->with('success', 'User profile updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $isCustomerOnly = $user->hasRole('customer') && ! $user->hasAnyRole(['admin', 'member']);
        if ($request->user()->is($user)) {
            return redirect()->route($isCustomerOnly ? 'admin.users.customers' : 'admin.users.index')
                ->with('error', $isCustomerOnly
                    ? 'You cannot delete your own customer account from account management.'
                    : 'You cannot delete your own account from member management.');
        }

        $administratorRole = Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->first();
        if ($administratorRole !== null
            && $user->hasRole($administratorRole)
            && $administratorRole->users()->count() <= 1) {
            return redirect()->route('admin.users.index')->with('error', 'The last administrator account cannot be deleted.');
        }

        $photoPath = $user->profile_photo_path;
        $user->delete();

        if ($photoPath !== null && ! Storage::disk('public')->delete($photoPath)) {
            throw new \RuntimeException('The deleted member photo could not be removed.');
        }

        return redirect()->route($isCustomerOnly ? 'admin.users.customers' : 'admin.users.index')
            ->with('success', 'Account deleted.');
    }
}
