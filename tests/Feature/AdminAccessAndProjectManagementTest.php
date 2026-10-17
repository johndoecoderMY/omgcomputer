<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FlowChart;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessAndProjectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_planner_administrators_can_manage_roles_permissions_and_user_roles(): void
    {
        $member = User::factory()->create();
        $this->seed(PlannerRoleSeeder::class);
        $member->assignRole('member');

        $this->actingAs($member)->get(route('admin.access.index'))
            ->assertForbidden()
            ->assertSee('Access denied');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('admin.access.index'))
            ->assertOk()
            ->assertSee('Roles & permissions', false)
            ->assertSee('Permissions matrix')
            ->assertSee('Role information')
            ->assertDontSee('User roles');
        $this->get(route('admin.users.index'))->assertOk()->assertSee('Members');
        $this->get(route('admin.users.customers'))->assertOk()->assertSee('Customer accounts');
        $this->get(route('admin.users.edit', $member))->assertOk()->assertSee('Account roles');

        $this->post(route('admin.access.roles.store'), ['name' => 'supervisor'])
            ->assertRedirect(route('admin.access.index', ['role' => Role::query()->where('name', 'supervisor')->value('id')]));
        $supervisor = Role::query()->where('name', 'supervisor')->firstOrFail();
        $this->post(route('admin.access.permissions.store'), ['name' => 'projects.approve'])
            ->assertRedirect(route('admin.access.index'));
        $permission = Permission::query()->where('name', 'projects.approve')->firstOrFail();

        $this->put(route('admin.access.roles.update', $supervisor), [
            'name' => 'supervisor',
            'permission_ids' => [$permission->id],
        ])->assertRedirect(route('admin.access.index', ['role' => $supervisor->id]));
        $this->assertTrue($supervisor->fresh()->hasPermissionTo($permission));

        $this->put(route('admin.access.users.roles.update', $member), [
            'role_ids' => [$supervisor->id],
        ])->assertRedirect(route('admin.users.edit', $member));
        $this->assertTrue($member->fresh()->hasRole('supervisor'));
        $this->get(route('admin.access.index', ['role' => $supervisor->id]))
            ->assertOk()
            ->assertSee('projects.approve')
            ->assertSee($member->email);
        $this->delete(route('admin.access.roles.destroy', $supervisor))
            ->assertSessionHas('error');
        $this->delete(route('admin.access.permissions.destroy', $permission))
            ->assertSessionHas('error');

        $this->put(route('admin.access.users.roles.update', $admin), [
            'role_ids' => [$supervisor->id],
        ])->assertSessionHas('error', 'The last administrator cannot be demoted.');
        $this->assertTrue($admin->fresh()->hasRole('admin'));

        $this->put(route('admin.access.users.roles.update', $member), [
            'role_ids' => [Role::query()->where('name', 'member')->value('id')],
        ])->assertRedirect(route('admin.users.edit', $member));
        $this->put(route('admin.access.roles.update', $supervisor), [
            'name' => 'supervisor',
        ])->assertRedirect(route('admin.access.index', ['role' => $supervisor->id]));
        $this->delete(route('admin.access.permissions.destroy', $permission))
            ->assertRedirect(route('admin.access.index'));
        $this->delete(route('admin.access.roles.destroy', $supervisor))
            ->assertRedirect(route('admin.access.index'));
    }

    public function test_admin_can_edit_user_profile_without_requiring_email_verification(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = User::factory()->create(['email_verified_at' => now()]);
        $verifiedAt = $member->email_verified_at;
        $this->actingAs($admin);

        $this->put(route('admin.users.update', $member), [
            'name' => 'Updated Member',
            'email' => 'updated-member@example.com',
            'phone' => '555-0100',
            'address' => 'New address',
            'team_ids' => [],
        ])->assertRedirect(route('admin.users.edit', $member))
            ->assertSessionHas('success', 'User profile updated.');

        $member->refresh();
        $this->assertSame('Updated Member', $member->name);
        $this->assertSame('updated-member@example.com', $member->email);
        $this->assertEquals($verifiedAt, $member->email_verified_at);
        $this->assertSame('555-0100', $member->phone);
    }

    public function test_non_administrators_cannot_open_user_management(): void
    {
        $member = User::factory()->create();
        $this->seed(PlannerRoleSeeder::class);
        $member->assignRole('member');

        $this->actingAs($member)->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_project_edit_can_move_or_clear_its_customer_link(): void
    {
        $this->actingAs(User::factory()->create());
        $existingProject = Project::query()->create(['name' => 'Existing project']);
        $project = Project::query()->create(['name' => 'Student FYP']);
        $previousCustomer = Customer::query()->create([
            'name' => 'Previous customer',
            'project_id' => $project->id,
        ]);
        $nextCustomer = Customer::query()->create([
            'name' => 'New customer',
            'project_id' => $existingProject->id,
        ]);

        $this->get(route('projects.edit', $project))
            ->assertOk()
            ->assertSee('Linked customer')
            ->assertSee('No customer linked');
        $this->put(route('projects.update', $project), [
            'name' => $project->name,
            'description' => $project->description,
            'customer_id' => $nextCustomer->id,
        ])->assertRedirect(route('projects.show', $project));

        $this->assertNull($previousCustomer->fresh()->project_id);
        $this->assertSame($project->id, $nextCustomer->fresh()->project_id);
        $this->put(route('projects.update', $project), [
            'name' => $project->name,
            'description' => $project->description,
            'customer_id' => '',
        ])->assertRedirect(route('projects.show', $project));
        $this->assertNull($nextCustomer->fresh()->project_id);
    }

    public function test_project_flow_chart_datatable_can_link_duplicate_and_create_for_a_project(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'FYP capstone']);
        $source = FlowChart::query()->create([
            'title' => 'Research review flow',
            'category' => 'education',
            'subject_name' => 'Research programme',
            'description' => 'Source template.',
            'steps' => [
                ['id' => (string) Str::uuid(), 'label' => 'Review evidence', 'kind' => 'action', 'connections' => []],
            ],
        ]);
        $longTitleSource = FlowChart::query()->create([
            'title' => str_repeat('Long title ', 23),
            'category' => 'process',
            'description' => 'Long title source.',
            'steps' => [],
        ]);

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('data-datatable', false);
        $this->getJson(route('projects.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'FYP capstone'],
        ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'FYP capstone');

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('data-datatable', false)
            ->assertSee('Duplicate selected for project')
            ->assertSee('Find custom flow charts to link')
            ->assertSee('Find SOP examples to duplicate');
        $this->getJson(route('projects.flowcharts.datatable', [
            'project' => $project->id,
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 0)
            ->assertJsonCount(0, 'data');

        $this->post(route('projects.flowcharts.link', $project), [
            'flow_chart_ids' => [$source->id],
        ])->assertRedirect(route('projects.show', $project));
        $this->assertTrue($project->flowCharts()->whereKey($source->id)->exists());
        $this->getJson(route('projects.flowcharts.datatable', [
            'project' => $project->id,
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.title', 'Research review flow')
            ->assertJsonMissing(['title' => str_repeat('Long title ', 23)]);

        $this->post(route('projects.flowcharts.duplicate', $project), [
            'flow_chart_ids' => [$source->id, $longTitleSource->id],
        ])->assertRedirect(route('projects.show', $project));
        $copy = FlowChart::query()->where('title', 'Research review flow — FYP capstone copy')->firstOrFail();
        $this->assertTrue($project->flowCharts()->whereKey($copy->id)->exists());
        $this->assertNotSame($source->id, $copy->id);
        $longCopy = FlowChart::query()->where('title', 'like', 'Long title %FYP capstone copy')->firstOrFail();
        $this->assertLessThanOrEqual(255, mb_strlen($longCopy->title));
        $this->assertTrue($project->flowCharts()->whereKey($longCopy->id)->exists());

        $this->delete(route('projects.flowcharts.destroy', [$project, $source]))
            ->assertRedirect(route('projects.show', $project));
        $this->assertFalse($project->flowCharts()->whereKey($source->id)->exists());

        $stepId = (string) Str::uuid();
        $this->get(route('development.flowcharts.create', [
            'category' => 'education',
            'project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertSee('name="project_id" value="'.$project->id.'"', false);
        $this->post(route('development.flowcharts.store'), [
            'project_id' => $project->id,
            'title' => 'New project SOP',
            'category' => 'education',
            'subject_name' => 'FYP support',
            'steps' => [
                ['id' => $stepId, 'label' => 'Agree next step', 'kind' => 'action', 'connections' => []],
            ],
        ])->assertRedirect();
        $created = FlowChart::query()->where('title', 'New project SOP')->firstOrFail();
        $this->assertTrue($project->flowCharts()->whereKey($created->id)->exists());
    }

    public function test_branded_not_found_error_page_is_rendered(): void
    {
        $this->get('/route-that-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('404');
    }
}
