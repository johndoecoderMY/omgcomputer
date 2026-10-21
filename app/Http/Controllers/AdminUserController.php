<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use App\Services\CpanelEmailProvisioner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
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

    public function create(CpanelEmailProvisioner $provisioner): View
    {
        return view('admin.users.create', [
            'teams' => Team::query()->with('department')->orderBy('name')->get(),
            'accountType' => 'members',
            'cpanelProvisioningEnabled' => $provisioner->configured(),
        ]);
    }

    public function createCustomer(): View
    {
        return view('admin.users.create', [
            'teams' => collect(),
            'accountType' => 'customers',
            'cpanelProvisioningEnabled' => false,
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

    public function store(Request $request, CpanelEmailProvisioner $provisioner): RedirectResponse
    {
        return $this->storeAccount($request, 'member', $provisioner);
    }

    public function storeCustomer(Request $request, CpanelEmailProvisioner $provisioner): RedirectResponse
    {
        return $this->storeAccount($request, 'customer', $provisioner);
    }

    private function storeAccount(Request $request, string $roleName, CpanelEmailProvisioner $provisioner): RedirectResponse
    {
        $isMember = $roleName === 'member';
        $cpanelEnabled = $isMember && $provisioner->configured();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                function (string $attribute, mixed $value, \Closure $fail) use ($request, $cpanelEnabled): void {
                    $mailboxUsername = trim((string) $request->input('mailbox_username'));
                    $mailboxEmail = $mailboxUsername.'@'.config('services.cpanel.email_domain');
                    if ($cpanelEnabled && $mailboxUsername !== '' && strcasecmp((string) $value, $mailboxEmail) === 0) {
                        $fail('Use a separate personal contact email so the member can receive the password setup link.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['required', 'integer', 'distinct', 'exists:teams,id'],
            'mailbox_username' => [
                Rule::requiredIf($cpanelEnabled),
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9](?:[A-Za-z0-9._+-]*[A-Za-z0-9])?$/',
            ],
            'password' => $isMember ? ['prohibited'] : ['required', 'string', 'min:12', 'confirmed'],
        ]);
        $accountPassword = $isMember ? Str::random(64) : $data['password'];
        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        $user = DB::transaction(function () use ($data, $role, $accountPassword, $isMember): User {
            $user = User::query()->create([
                ...collect($data)->except(['password', 'team_ids', 'mailbox_username'])->all(),
                'password' => Hash::make($accountPassword),
                'force_password_change' => ! $isMember,
            ]);
            $user->teams()->sync($data['team_ids'] ?? []);
            $user->assignRole($role);

            return $user;
        });

        if (! $isMember) {
            return redirect()->route('admin.users.edit', $user)
                ->with('success', 'Customer account created.');
        }

        $warnings = [];
        if ($cpanelEnabled) {
            try {
                $mailbox = $provisioner->provision($data['mailbox_username']);
                $user->emailMailbox()->create($mailbox);
            } catch (\RuntimeException $exception) {
                Log::warning('cPanel mailbox provisioning failed for a new member.', [
                    'user_id' => $user->id,
                    'exception' => $exception::class,
                ]);
                $warnings[] = $exception->getMessage();
            }
        } else {
            $warnings[] = 'cPanel provisioning is not configured; create the mailbox manually if the member needs one.';
        }

        try {
            $inviteStatus = Password::sendResetLink(['email' => $user->email]);
            if ($inviteStatus !== Password::RESET_LINK_SENT) {
                $warnings[] = 'The account was created, but the password setup email could not be sent. Use the password reset page to send it again.';
            }
        } catch (TransportExceptionInterface $exception) {
            Log::warning('Member password setup email could not be delivered.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);
            $warnings[] = 'The account was created, but the password setup email could not be delivered. Check SMTP settings and resend the reset link.';
        }

        $response = redirect()->route('admin.users.edit', $user)
            ->with('success', 'Member account created. A secure password setup link was sent to the contact email; no password was emailed.');

        return $warnings === [] ? $response : $response->with('warning', implode(' ', $warnings));
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load(['teams.department', 'roles', 'permissions']),
            'teams' => Team::query()->with('department')->orderBy('name')->get(),
            'roles' => Role::query()
                ->where('guard_name', 'web')
                ->whereIn('name', ['admin', 'member', 'customer'])
                ->orderBy('name')
                ->get(),
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
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
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
