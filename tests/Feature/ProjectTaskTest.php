<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\BusinessPlanTaskSeeder;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_plan_tasks_are_seeded_idempotently_as_a_project_plan(): void
    {
        $seeder = new BusinessPlanTaskSeeder;
        $seeder->run();
        $seeder->run();

        $project = Project::query()->where('name', 'OMG Computer')->firstOrFail();
        $tasks = Task::query()->where('project_id', $project->id)->get();

        $this->assertCount(16, $tasks);
        $this->assertCount(4, $tasks->whereNull('parent_task_id'));
        $this->assertCount(12, $tasks->whereNotNull('parent_task_id'));
        $this->assertTrue($tasks->every(fn (Task $task): bool => $task->status === 'backlog'));
        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Validate the repair shop location and requirements',
            'parent_task_id' => null,
        ]);
        foreach ([
            'Register OMG Computer with SSM',
            'Register OMG Computer on Meta for business pages and social media',
            'Register the shop on an e-commerce platform',
            'Register for a payment gateway',
        ] as $title) {
            $this->assertDatabaseHas('tasks', [
                'project_id' => $project->id,
                'title' => $title,
            ]);
        }
    }

    public function test_authenticated_user_can_create_a_task_and_manage_its_subtasks(): void
    {
        $this->actingAs($this->adminUser());
        $project = Project::create(['name' => 'Website refresh']);

        $this->post(route('projects.tasks.store', $project), [
            'title' => 'Build the website homepage',
            'description' => 'Create the public landing page.',
            'status' => 'backlog',
            'priority' => 'high',
            'due_date' => '2026-12-01',
        ])->assertSessionHasNoErrors()
            ->assertRedirect();

        $parent = Task::where('title', 'Build the website homepage')->firstOrFail();
        $this->assertSame($project->id, $parent->project_id);

        $this->post(route('projects.tasks.store', $project), [
            'parent_id' => $parent->id,
            'title' => 'Add the hero section',
            'description' => 'Create the headline and call to action.',
            'status' => 'in_progress',
            'priority' => 'medium',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('tasks.show', $parent));

        $subtask = $parent->subtasks()->firstOrFail();
        $this->assertSame($parent->id, $subtask->parent_task_id);
        $this->assertSame($project->id, $subtask->project_id);

        $this->patch(route('tasks.update-status', $parent), ['done' => '1'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Task marked done; all subtasks marked done.');
        $this->assertSame('done', $parent->fresh()->status);
        $this->assertSame('done', $subtask->fresh()->status);

        $this->patch(route('tasks.update-status', $parent), [])
            ->assertSessionHasNoErrors();
        $this->assertSame('backlog', $parent->fresh()->status);
        $this->assertSame('done', $subtask->fresh()->status);

        $this->put(route('tasks.update', $parent), [
            'title' => 'Build the website homepage',
            'description' => 'Homepage updates.',
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => '2026-12-01',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('tasks.show', $parent));

        $this->get(route('tasks.show', $parent))
            ->assertOk()
            ->assertSee('Subtasks')
            ->assertSee('Mark Add the hero section as done')
            ->assertSee('Add the hero section');

        $this->put(route('tasks.update', $subtask), [
            'parent_id' => $parent->id,
            'title' => 'Finish the hero section',
            'description' => 'Review responsive layout and copy.',
            'status' => 'done',
            'priority' => 'low',
            'due_date' => null,
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('tasks.show', $parent));

        $this->assertSame('Finish the hero section', $subtask->fresh()->title);
        $this->assertSame('done', $subtask->fresh()->status);

        $this->delete(route('tasks.destroy', $parent))
            ->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseMissing('tasks', ['id' => $parent->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $subtask->id]);
    }

    public function test_projects_show_task_progress_and_subtasks_cannot_be_nested_or_cross_project(): void
    {
        $this->actingAs($this->adminUser());
        $project = Project::create(['name' => 'Main website']);
        $otherProject = Project::create(['name' => 'Shop upgrade']);
        $parent = $project->tasks()->create([
            'title' => 'Build the website',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);
        $child = $parent->subtasks()->create([
            'project_id' => $project->id,
            'title' => 'Create the homepage',
            'status' => 'done',
            'priority' => 'low',
        ]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('project-overview')
            ->assertSee('project-stat')
            ->assertSee('50%')
            ->assertSee('Create the homepage');
        $this->assertSame(50, $project->fresh()->progress);

        $this->post(route('projects.tasks.store', $project), [
            'parent_id' => $child->id,
            'title' => 'Nested task',
            'status' => 'backlog',
            'priority' => 'low',
        ])->assertStatus(422);

        $this->post(route('projects.tasks.store', $otherProject), [
            'parent_id' => $parent->id,
            'title' => 'Cross-project subtask',
            'status' => 'backlog',
            'priority' => 'low',
        ])->assertNotFound();

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('data-datatable', false)
            ->assertSee('data-datatable-filters', false)
            ->assertSee('Could not load this table.')
            ->assertSee('timeout: 30000', false)
            ->assertSee(route('tasks.datatable'), false);
        $this->get(route('tasks.index', ['status' => 'done', 'priority' => 'urgent']))
            ->assertOk()
            ->assertSee('value="">All statuses', false)
            ->assertSee(route('tasks.datatable'), false);
        $this->getJson(route('tasks.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'Build the website'],
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.status_label', '<span class="badge text-bg-secondary">To do</span>')
            ->assertSee('Build the website')
            ->assertSee('Main website');
        $this->getJson(route('tasks.datatable', [
            'status' => 'done',
            'priority' => 'low',
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1);

        $this->get(route('development.overview'))
            ->assertOk()
            ->assertViewHas('taskCounts', fn (array $counts): bool => $counts['total'] === 2
                && $counts['done'] === 1
                && $counts['remaining'] === 1
                && $counts['percent'] === 50)
            ->assertSee('50%')
            ->assertSee('1 of 2 tasks completed');
    }

    public function test_projects_can_be_created_updated_and_deleted_with_their_tasks(): void
    {
        $this->actingAs($this->adminUser());

        $this->post(route('projects.store'), [
            'name' => 'Storefront',
            'description' => 'New public website.',
        ])->assertSessionHasNoErrors()
            ->assertRedirect();

        $project = Project::where('name', 'Storefront')->firstOrFail();
        $task = $project->tasks()->create([
            'title' => 'Add product catalog',
            'status' => 'backlog',
            'priority' => 'high',
        ]);

        $this->put(route('projects.update', $project), [
            'name' => 'Computer storefront',
            'description' => 'Updated project summary.',
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('projects.show', $project));

        $this->assertSame('Computer storefront', $project->fresh()->name);

        $this->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_task_edit_form_renders_the_assignee_list(): void
    {
        $assignee = User::factory()->create(['name' => 'Jordan Lee']);
        $this->actingAs($this->adminUser());
        $project = Project::create(['name' => 'Customer portal']);
        $task = $project->tasks()->create([
            'assigned_to' => $assignee->id,
            'title' => 'Review account page',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $this->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSee('Assign people')
            ->assertSee('Jordan Lee');
    }

    public function test_standalone_tasks_can_be_created_with_multiple_assignees(): void
    {
        $owner = $this->adminUser();
        $firstAssignee = User::factory()->create(['name' => 'Jordan Lee']);
        $secondAssignee = User::factory()->create(['name' => 'Sam Rivera']);

        $this->actingAs($owner)
            ->get(route('tasks.create'))
            ->assertOk()
            ->assertSee('Create task')
            ->assertSee('No project');

        $this->actingAs($owner)->post(route('tasks.store'), [
            'title' => 'Prepare an independent task',
            'status' => 'backlog',
            'priority' => 'medium',
            'assignees_present' => '1',
            'assignees' => [$firstAssignee->id, $secondAssignee->id],
        ])->assertSessionHasNoErrors();

        $task = Task::query()->where('title', 'Prepare an independent task')->firstOrFail();
        $this->assertNull($task->project_id);
        $this->assertEqualsCanonicalizing(
            [$firstAssignee->id, $secondAssignee->id],
            $task->assignees()->pluck('users.id')->all(),
        );
        $this->assertSame($firstAssignee->id, $task->assigned_to);

        $this->actingAs($owner)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Add task');

        $this->actingAs($owner)->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('Jordan Lee')
            ->assertSee('Sam Rivera')
            ->assertSee('Take photo')
            ->assertSee('Back to tasks');

        $this->actingAs($owner)->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'));
        $this->assertDatabaseMissing('user_assignments', [
            'assignable_type' => Task::class,
            'assignable_id' => $task->id,
        ]);
    }

    public function test_project_can_be_assigned_to_multiple_people(): void
    {
        $owner = $this->adminUser();
        $assignees = User::factory()->count(2)->create();

        $this->actingAs($owner)->post(route('projects.store'), [
            'name' => 'Assigned project',
            'assignees' => $assignees->pluck('id')->all(),
        ])->assertSessionHasNoErrors();

        $project = Project::query()->where('name', 'Assigned project')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            $assignees->pluck('id')->all(),
            $project->assignees()->pluck('users.id')->all(),
        );
        $this->actingAs($owner)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Assigned people')
            ->assertSee($assignees->first()->name)
            ->assertSee($assignees->last()->name);
    }

    private function adminUser(): User
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
