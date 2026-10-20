<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_database_seeder_creates_admin_member_and_customer_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(User::where('email', 'test@example.com')->firstOrFail()->hasRole('admin'));
        $this->assertTrue(User::where('email', 'member@example.com')->firstOrFail()->hasRole('member'));
        $customer = User::where('email', 'customer@example.com')->firstOrFail();
        $this->assertTrue($customer->hasRole('customer'));
        $this->assertTrue($customer->hasVerifiedEmail());
        $this->assertFalse($customer->force_password_change);

        $this->post(route('login'), [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($customer);
        $this->get(route('projects.index'))->assertOk();
    }

    public function test_administrator_can_create_members_with_default_role_and_assign_individual_permissions(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $this->assertDatabaseHas('roles', ['name' => 'admin', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'member', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'customer', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $department = Department::create(['name' => 'Operations']);
        $repairTeam = Team::create(['department_id' => $department->id, 'name' => 'Repair']);
        $supportTeam = Team::create(['department_id' => $department->id, 'name' => 'Support']);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Members')
            ->assertSee('Create member')
            ->assertSee('Additional permissions');
        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('member')
            ->assertSee('Temporary password');

        $this->post(route('admin.users.store'), [
            'name' => 'New Team Member',
            'email' => 'new-member@example.test',
            'phone' => '555-0102',
            'team_ids' => [$repairTeam->id, $supportTeam->id],
            'password' => 'temporary-password-123',
            'password_confirmation' => 'temporary-password-123',
        ])->assertRedirect();

        $member = User::query()->where('email', 'new-member@example.test')->firstOrFail();
        $this->assertTrue($member->hasRole('member'));
        $this->assertTrue($member->force_password_change);
        $this->assertTrue(Hash::check('temporary-password-123', $member->password));
        $this->assertEqualsCanonicalizing(
            [$repairTeam->id, $supportTeam->id],
            $member->teams->modelKeys(),
        );

        $permission = Permission::query()->create([
            'name' => 'members.export',
            'guard_name' => 'web',
        ]);
        $this->put(route('admin.users.permissions.update', $member), [
            'permission_ids' => [$permission->id],
        ])->assertRedirect(route('admin.users.edit', $member));
        $this->assertTrue($member->fresh()->hasDirectPermission($permission));
        $this->get(route('admin.users.edit', $member))
            ->assertOk()
            ->assertSee('Additional individual permissions')
            ->assertSee('members.export');
        $this->getJson(route('admin.users.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'new-member@example.test'],
        ]))
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'New Team Member',
                'roles_list' => 'member',
                'teams_list' => 'Repair, Support',
                'permissions_list' => 'members.export',
            ]);
        $memberListRows = $this->getJson(route('admin.users.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'new-member@example.test'],
        ]))->json('data');
        $memberListRow = collect($memberListRows)->firstWhere('email', 'new-member@example.test');
        $this->assertNotNull($memberListRow);
        $this->assertStringContainsString(route('members.show', $member), $memberListRow['actions']);
        $this->assertStringNotContainsString(route('admin.users.edit', $member), $memberListRow['actions']);
        $this->get(route('members.show', $member))
            ->assertOk()
            ->assertSee('Manage account')
            ->assertSee(route('admin.users.edit', $member), false);

        $viewer = User::factory()->create(['force_password_change' => false]);
        $viewer->assignRole('member');
        $this->actingAs($viewer)
            ->get(route('members.show', $admin))
            ->assertOk()
            ->assertDontSee('Manage account');
        $this->actingAs($admin);

        $this->put(route('admin.users.update', $member), [
            'name' => $member->name,
            'email' => $member->email,
            'team_ids' => [$supportTeam->id],
        ])->assertRedirect(route('admin.users.edit', $member));
        $this->assertEquals([$supportTeam->id], $member->fresh()->teams->modelKeys());

        $this->delete(route('admin.users.destroy', $member))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $member->id]);
    }

    public function test_member_management_is_restricted_and_administrator_cannot_delete_themselves(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('admin.users.create'))
            ->assertForbidden();
        $this->delete(route('admin.users.destroy', $member))->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error', 'You cannot delete your own account from member management.');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_customer_accounts_have_separate_listing_and_creation_from_staff_members(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = User::factory()->create();
        $member->assignRole('member');
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->actingAs($admin);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Members')
            ->assertSee('/admin/users/datatable', false);
        $members = $this->getJson(route('admin.users.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk();
        $members->assertJsonPath('recordsFiltered', 2);
        $members->assertJsonMissing(['email' => $customer->email]);

        $this->get(route('admin.users.customers.create'))
            ->assertOk()
            ->assertSee('Create customer account')
            ->assertDontSee('for="team_ids"', false);
        $this->post(route('admin.users.customers.store'), [
            'name' => 'New Portal Customer',
            'email' => 'portal-customer@example.test',
            'password' => 'temporary-customer-password',
            'password_confirmation' => 'temporary-customer-password',
        ])->assertRedirect();

        $createdCustomer = User::query()->where('email', 'portal-customer@example.test')->firstOrFail();
        $this->assertTrue($createdCustomer->hasRole('customer'));
        $this->assertTrue($createdCustomer->force_password_change);

        $customers = $this->getJson(route('admin.users.customers.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk();
        $customers->assertJsonPath('recordsFiltered', 2);
        $customers->assertJsonFragment(['email' => $createdCustomer->email]);
        $customers->assertJsonFragment(['email' => $customer->email]);
    }
}
