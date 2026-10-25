<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Illuminate\Auth\Notifications\ResetPassword;
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
        Notification::fake();
        config(['services.cpanel.enabled' => false]);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Members')
            ->assertSee('Create member')
            ->assertSee('Additional permissions');
        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('member')
            ->assertSee('password setup link')
            ->assertSee('js-user-search', false);

        $this->post(route('admin.users.store'), [
            'name' => 'New Team Member',
            'email' => 'new-member@example.test',
            'phone' => '555-0102',
            'team_ids' => [$repairTeam->id, $supportTeam->id],
        ])->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('warning');

        $member = User::query()->where('email', 'new-member@example.test')->firstOrFail();
        $this->assertTrue($member->hasRole('member'));
        $this->assertFalse($member->force_password_change);
        Notification::assertSentTo($member, ResetPassword::class);
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

    public function test_member_creation_provisions_a_cpanel_mailbox_and_saves_its_credentials_encrypted(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Notification::fake();
        config([
            'services.cpanel.enabled' => true,
            'services.cpanel.host' => 'cpanel.example.test',
            'services.cpanel.port' => 2083,
            'services.cpanel.username' => 'cpanel-user',
            'services.cpanel.api_token' => 'private-api-token',
            'services.cpanel.email_domain' => 'example.test',
            'services.cpanel.email_quota' => 1024,
        ]);
        $this->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Preferred email name')
            ->assertSee('firstname.lastname')
            ->assertSee('@example.test');
        Http::fake([
            'https://cpanel.example.test:2083/execute/Email/add_pop' => Http::response([
                'result' => ['status' => 1],
            ]),
        ]);

        $this->post(route('admin.users.store'), [
            'name' => 'Provisioned Member',
            'email' => 'personal@example.test',
            'mailbox_username' => 'provisioned.member',
        ])->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionMissing('warning');

        $member = User::query()->where('email', 'personal@example.test')->firstOrFail();
        $this->assertSame('provisioned.member@example.test', $member->emailMailbox->email);
        $this->assertNotSame(
            $member->emailMailbox->password,
            \Illuminate\Support\Facades\DB::table('user_email_mailboxes')->where('user_id', $member->id)->value('password'),
        );
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://cpanel.example.test:2083/execute/Email/add_pop'
            && $request->hasHeader('Authorization', 'cpanel cpanel-user:private-api-token')
            && $request['email'] === 'provisioned.member'
            && $request['domain'] === 'example.test'
            && $request['quota'] === 1024
            && filled($request['password']));
        Notification::assertSentTo($member, ResetPassword::class);
    }

    public function test_member_creation_reports_cpanel_uapi_rejection_without_hiding_the_error(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);
        Notification::fake();
        config([
            'services.cpanel.enabled' => true,
            'services.cpanel.host' => 'cpanel.example.test',
            'services.cpanel.port' => 2083,
            'services.cpanel.username' => 'cpanel-user',
            'services.cpanel.api_token' => 'private-api-token',
            'services.cpanel.email_domain' => 'example.test',
            'services.cpanel.email_quota' => 1024,
        ]);
        Http::fake([
            'https://cpanel.example.test:2083/execute/Email/add_pop' => Http::response([
                'result' => ['status' => 0, 'errors' => ['Mailbox already exists']],
            ]),
        ]);

        $this->post(route('admin.users.store'), [
            'name' => 'Member with Existing Mailbox',
            'email' => 'personal@example.test',
            'mailbox_username' => 'existing.member',
        ])->assertRedirect()
            ->assertSessionHas('warning', fn (string $warning): bool => str_contains($warning, 'cPanel could not create the mailbox'));

        $member = User::query()->where('email', 'personal@example.test')->firstOrFail();
        $this->assertNull($member->emailMailbox);
        Notification::assertSentTo($member, ResetPassword::class);
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
        $this->get(route('admin.users.customers'))
            ->assertOk()
            ->assertSee('<th>Name</th><th>Email</th><th>Date joined</th>', false)
            ->assertDontSee('Additional permissions')
            ->assertDontSee('<th>Teams</th>', false)
            ->assertDontSee('<th>Roles</th>', false)
            ->assertDontSee('<th>Profile</th>', false);
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
        $this->assertSame($customer->created_at->format('M j, Y'), collect($customers->json('data'))->firstWhere('email', $customer->email)['date_joined']);
        $this->assertArrayNotHasKey('teams_list', $customers->json('data.0'));
        $this->assertArrayNotHasKey('roles_list', $customers->json('data.0'));
        $this->assertArrayNotHasKey('permissions_list', $customers->json('data.0'));
        $this->assertArrayNotHasKey('actions', $customers->json('data.0'));
    }

    public function test_customer_account_profile_hides_team_and_department_fields(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $this->actingAs($admin)
            ->get(route('members.show', $customer))
            ->assertOk()
            ->assertSee('Customer account')
            ->assertDontSee('Team member')
            ->assertDontSee('No department assigned')
            ->assertDontSee('Not assigned')
            ->assertDontSee('Team directory');
    }
}
