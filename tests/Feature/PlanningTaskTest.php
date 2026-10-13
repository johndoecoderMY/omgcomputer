<?php

namespace Tests\Feature;

use App\Models\PlanningProject;
use App\Models\PlanningTask;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlanningTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_planner(): void
    {
        $this->get(route('planner.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_the_business_plan(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('planner.business-plan'))
            ->assertOk()
            ->assertSee('PC &amp; Laptop Repair Shop', false)
            ->assertSee('Planning &amp; Progress', false)
            ->assertSee('Back to planner')
            ->assertSee('RM15,500')
            ->assertSee('RM5,880');
    }

    public function test_project_overview_uses_one_shared_omg_computer_project(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('planner.projects'))
            ->assertOk()
            ->assertSee('OMG Computer')
            ->assertSee('One project for the website, repair work, and everything needed to run the shop.');

        $this->assertDatabaseHas('planning_projects', [
            'user_id' => $user->id,
            'slug' => 'omg-computer',
            'name' => 'OMG Computer',
            'visibility' => 'team',
        ]);
        $this->assertDatabaseCount('planning_projects', 1);
    }

    public function test_project_overview_shows_progress_and_only_visible_tasks_for_the_single_project(): void
    {
        $viewer = User::factory()->create();
        $otherUser = User::factory()->create();
        $project = PlanningProject::omgComputer($otherUser);

        foreach ([
            ['Project task done', 'done', 'feature', 'team'],
            ['Project task remaining', 'in_progress', 'operations', 'team'],
            ['Private project task', 'backlog', 'task', 'personal'],
        ] as [$title, $status, $category, $visibility]) {
            PlanningTask::create([
                'user_id' => $otherUser->id,
                'project_id' => $project->id,
                'title' => $title,
                'category' => $category,
                'status' => $status,
                'priority' => 'medium',
                'visibility' => $visibility,
            ]);
        }

        $this->actingAs($viewer)
            ->get(route('planner.projects'))
            ->assertOk()
            ->assertSee('OMG Computer')
            ->assertViewHas('totals', fn (array $totals): bool => $totals['total'] === 2
                && $totals['done'] === 1
                && $totals['remaining'] === 1
                && $totals['percent'] === 50
            )
            ->assertSee('50%')
            ->assertSee('Website features')
            ->assertSee('Shop operations')
            ->assertDontSee('Private project task');
    }

    public function test_task_is_linked_to_the_omg_computer_project_and_can_be_shop_operations(): void
    {
        $user = User::factory()->create();
        $project = PlanningProject::omgComputer($user);

        $this->actingAs($user)
            ->post(route('planner.store'), [
                'title' => 'Choose shop location',
                'category' => 'operations',
                'status' => 'backlog',
                'priority' => 'medium',
                'visibility' => 'personal',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('planner.index', ['scope' => 'personal']));

        $this->assertDatabaseHas('planning_tasks', [
            'title' => 'Choose shop location',
            'project_id' => $project->id,
            'category' => 'operations',
        ]);

        $this->actingAs($user)
            ->get(route('planner.index', ['type' => 'operations']))
            ->assertOk()
            ->assertSee('Choose shop location');
    }

    public function test_task_can_be_created_in_a_selected_project(): void
    {
        $user = User::factory()->create();
        PlanningProject::omgComputer($user);
        $unrelatedProject = PlanningProject::create([
            'user_id' => $user->id,
            'name' => 'Unrelated legacy project',
            'visibility' => 'personal',
        ]);

        $this->actingAs($user)
            ->post(route('planner.store'), [
                'title' => 'Keep this in the main project',
                'category' => 'task',
                'status' => 'backlog',
                'priority' => 'medium',
                'visibility' => 'team',
                'project_id' => $unrelatedProject->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('planning_tasks', [
            'title' => 'Keep this in the main project',
            'project_id' => $unrelatedProject->id,
        ]);
    }

    public function test_parent_task_can_have_subtasks_visible_on_task_details_and_kanban(): void
    {
        $user = User::factory()->create();
        $parent = PlanningTask::create([
            'user_id' => $user->id,
            'title' => 'Prepare the repair counter',
            'category' => 'operations',
            'status' => 'planned',
            'priority' => 'medium',
            'visibility' => 'team',
        ]);

        $this->actingAs($user)->post(route('planner.subtasks.store', $parent), [
            'title' => 'Label the intake shelves',
            'status' => 'backlog',
            'priority' => 'high',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('planner.show', $parent));

        $subtask = PlanningTask::query()->where('parent_task_id', $parent->id)->firstOrFail();
        $this->assertSame($parent->project_id, $subtask->project_id);

        $this->actingAs($user)->get(route('planner.show', $parent))
            ->assertOk()
            ->assertSee('Subtasks')
            ->assertSee('Label the intake shelves');
        $this->actingAs($user)->get(route('planner.index', ['view' => 'kanban']))
            ->assertOk()
            ->assertSee('Label the intake shelves');
    }

    public function test_project_images_can_be_uploaded_and_viewed_by_authorized_users(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'Workshop refresh',
            'description' => 'Refresh the repair area.',
            'visibility' => 'team',
            'images' => [UploadedFile::fake()->image('workshop.jpg')],
        ])->assertSessionHasNoErrors();

        $project = PlanningProject::query()->where('name', 'Workshop refresh')->firstOrFail();
        $path = $project->images[0];
        Storage::disk('local')->assertExists($path);

        $this->actingAs($user)->get(route('projects.images.show', [$project, 0]))->assertOk();
    }

    public function test_overdue_filter_shows_only_unfinished_past_due_project_tasks(): void
    {
        $user = User::factory()->create();
        PlanningTask::create([
            'user_id' => $user->id,
            'title' => 'Order replacement cooling fans',
            'category' => 'operations',
            'status' => 'planned',
            'priority' => 'high',
            'visibility' => 'team',
            'due_date' => today()->subDay(),
        ]);
        PlanningTask::create([
            'user_id' => $user->id,
            'title' => 'Completed old website fix',
            'category' => 'feature',
            'status' => 'done',
            'priority' => 'low',
            'visibility' => 'team',
            'due_date' => today()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('planner.index', ['overdue' => 1]))
            ->assertOk()
            ->assertSee('Order replacement cooling fans')
            ->assertDontSee('Completed old website fix');
    }

    public function test_board_shows_shared_tasks_and_only_the_current_users_private_tasks(): void
    {
        $viewer = User::factory()->create();
        $otherUser = User::factory()->create();

        PlanningTask::create([
            'user_id' => $viewer->id,
            'title' => 'My private task',
            'category' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'visibility' => 'personal',
        ]);
        PlanningTask::create([
            'user_id' => $otherUser->id,
            'title' => 'Other private task',
            'category' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'visibility' => 'personal',
        ]);
        PlanningTask::create([
            'user_id' => $otherUser->id,
            'title' => 'Shared team task',
            'category' => 'feature',
            'status' => 'in_progress',
            'priority' => 'high',
            'visibility' => 'team',
        ]);

        $this->actingAs($viewer)
            ->get(route('planner.index'))
            ->assertOk()
            ->assertSee('My private task')
            ->assertDontSee('Other private task')
            ->assertSee('Shared team task');
    }

    public function test_user_can_create_a_team_task_and_update_its_progress(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('planner.store'), [
                'title' => 'Plan release',
                'description' => 'Prepare the feature release.',
                'category' => 'milestone',
                'status' => 'planned',
                'priority' => 'high',
                'visibility' => 'team',
                'assigned_to' => $user->id,
                'due_date' => '2026-12-01',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('planner.index', ['scope' => 'team']));

        $task = PlanningTask::query()->where('title', 'Plan release')->firstOrFail();
        $this->assertSame($user->id, $task->user_id);
        $this->assertSame($user->id, $task->assigned_to);
        $this->assertSame('milestone', $task->category);

        $this->patch(route('planner.update-status', $task), ['status' => 'done'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('planner.show', $task));

        $this->assertSame('done', $task->refresh()->status);
    }

    public function test_user_cannot_update_another_users_team_task_without_permission(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $task = PlanningTask::create([
            'user_id' => $owner->id,
            'title' => 'Team task',
            'category' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'visibility' => 'team',
        ]);

        $this->actingAs($otherUser)
            ->patch(route('planner.update-status', $task), ['status' => 'done'])
            ->assertForbidden();

        $this->assertSame('backlog', $task->refresh()->status);
    }

    public function test_assignee_can_update_progress_but_cannot_edit_task_details(): void
    {
        $owner = User::factory()->create();
        $assignee = User::factory()->create();
        $task = PlanningTask::create([
            'user_id' => $owner->id,
            'assigned_to' => $assignee->id,
            'title' => 'Assigned team task',
            'category' => 'task',
            'status' => 'planned',
            'priority' => 'medium',
            'visibility' => 'team',
        ]);

        $this->actingAs($assignee)
            ->patch(route('planner.update-status', $task), ['status' => 'in_progress'])
            ->assertSessionHasNoErrors();

        $this->assertSame('in_progress', $task->refresh()->status);

        $this->patch(route('planner.update', $task), [
            'title' => 'Unauthorized title change',
        ])->assertForbidden();

        $this->assertSame('Assigned team task', $task->refresh()->title);
    }

    public function test_owner_can_edit_and_delete_a_task(): void
    {
        $owner = User::factory()->create();
        $task = PlanningTask::create([
            'user_id' => $owner->id,
            'title' => 'Initial task',
            'category' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'visibility' => 'personal',
        ]);

        $this->actingAs($owner)
            ->patch(route('planner.update', $task), [
                'title' => 'Updated task',
                'description' => 'Updated details.',
                'category' => 'feature',
                'status' => 'planned',
                'priority' => 'high',
                'visibility' => 'personal',
                'due_date' => null,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('Updated task', $task->refresh()->title);
        $this->assertSame('feature', $task->category);

        $this->delete(route('planner.destroy', $task))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('planning_tasks', ['id' => $task->id]);
    }

    public function test_planner_admin_can_manage_a_team_task(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('planner-admin');
        $task = PlanningTask::create([
            'user_id' => $owner->id,
            'title' => 'Admin-managed task',
            'category' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'visibility' => 'team',
        ]);

        $this->actingAs($admin)
            ->patch(route('planner.update-status', $task), ['status' => 'in_progress'])
            ->assertSessionHasNoErrors();

        $this->assertSame('in_progress', $task->refresh()->status);
    }

    public function test_personal_tasks_cannot_be_seen_or_managed_by_planner_admins(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('planner-admin');
        $task = PlanningTask::create([
            'user_id' => $owner->id,
            'title' => 'Private task',
            'category' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'visibility' => 'personal',
        ]);

        $this->actingAs($admin)
            ->get(route('planner.index'))
            ->assertDontSee('Private task');

        $this->patch(route('planner.update-status', $task), ['status' => 'done'])
            ->assertNotFound();
    }

    public function test_task_cannot_be_moved_to_another_project_while_editing(): void
    {
        $user = User::factory()->create();
        $project = PlanningProject::omgComputer($user);
        $otherProject = PlanningProject::create([
            'user_id' => $user->id,
            'name' => 'Another project',
            'visibility' => 'team',
        ]);
        $task = PlanningTask::create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'Stay in this project',
            'category' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'visibility' => 'team',
        ]);

        $this->actingAs($user)
            ->patch(route('planner.update', $task), [
                'project_id' => $otherProject->id,
                'title' => 'Updated title',
                'category' => 'task',
                'status' => 'planned',
                'priority' => 'medium',
                'visibility' => 'team',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($project->id, $task->refresh()->project_id);
        $this->assertSame('Updated title', $task->title);
    }

    public function test_project_directory_lists_the_main_project_and_can_create_a_workspace(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('OMG Computer')
            ->assertSee('Project workspaces');

        $this->post(route('projects.store'), [
            'name' => 'Laptop refurbishment',
            'description' => 'Prepare refurbished devices for sale.',
            'visibility' => 'team',
        ])->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('planning_projects', [
            'name' => 'Laptop refurbishment',
            'user_id' => $user->id,
            'visibility' => 'team',
        ]);
    }

    public function test_task_list_and_detail_support_a_persistent_discussion(): void
    {
        $user = User::factory()->create();
        $task = PlanningTask::create([
            'user_id' => $user->id,
            'title' => 'Prepare repair bench',
            'description' => 'Set up the ESD mat and tools.',
            'category' => 'operations',
            'status' => 'planned',
            'priority' => 'high',
            'visibility' => 'team',
        ]);

        $this->actingAs($user)
            ->get(route('planner.index', ['view' => 'list']))
            ->assertOk()
            ->assertSee('Prepare repair bench')
            ->assertSee('Kanban')
            ->assertSee('0</a>', false);

        $this->get(route('planner.show', $task))
            ->assertOk()
            ->assertSee('What needs to be done')
            ->assertSee('Task discussion')
            ->assertSee('Set up the ESD mat and tools.');

        $this->post(route('planner.comments.store', $task), [
            'body' => 'I will finish the setup before opening.',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('planner.show', $task));

        $this->assertDatabaseHas('planning_task_comments', [
            'planning_task_id' => $task->id,
            'user_id' => $user->id,
            'body' => 'I will finish the setup before opening.',
        ]);

        $this->get(route('planner.show', $task))
            ->assertOk()
            ->assertSee('I will finish the setup before opening.');
    }

    public function test_users_cannot_view_tasks_from_a_private_project_they_cannot_access(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $privateProject = PlanningProject::create([
            'user_id' => $owner->id,
            'name' => 'Private project',
            'visibility' => 'personal',
        ]);
        $task = PlanningTask::create([
            'user_id' => $owner->id,
            'project_id' => $privateProject->id,
            'title' => 'Private project task',
            'category' => 'task',
            'status' => 'backlog',
            'priority' => 'medium',
            'visibility' => 'team',
        ]);

        $this->actingAs($viewer)
            ->get(route('projects.show', $privateProject))
            ->assertNotFound();

        $this->get(route('planner.show', $task))
            ->assertNotFound();
    }
}
