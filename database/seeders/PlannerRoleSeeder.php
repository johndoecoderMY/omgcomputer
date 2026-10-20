<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\WorkspacePermissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PlannerRoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(WorkspacePermissions::ALL)
            ->map(fn (string $name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));

        Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ])->syncPermissions($permissions);

        $memberRole = Role::firstOrCreate([
            'name' => 'member',
            'guard_name' => 'web',
        ]);
        $memberRole->syncPermissions($permissions->filter(
            fn (Permission $permission): bool => in_array($permission->name, WorkspacePermissions::MEMBER, true),
        ));

        Role::firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'web',
        ])->syncPermissions($permissions->filter(
            fn (Permission $permission): bool => in_array($permission->name, WorkspacePermissions::CUSTOMER, true),
        ));

        User::query()
            ->whereDoesntHave('roles')
            ->each(fn (User $user) => $user->assignRole($memberRole));
    }
}
