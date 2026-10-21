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

    public function test_tasks_page_includes_a_kanban_board_and_allows_authorized_status_moves(): void
    {
        $admin = $this->adminUser();
        $task = Task::query()->create([
            'created_by_user_id' => $admin->id,
            'title' => 'Kanban card test',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $this->actingAs($admin)->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Kanban')
            ->assertSee('My Tasks')
            ->assertSee('System')
            ->assertSee('logoex2w.png')
            ->assertSee('html[data-bs-theme="dark"] .brand-logo-light', false)
            ->assertSee('aria-label="Task view"', false)
            ->assertSee('data-task-view="list"', false)
            ->assertSee('data-task-view="kanban"', false)
            ->assertSee('data-task-kanban-view', false)
            ->assertSee('kanban-container', false)
            ->assertSee('kanban-column', false)
            ->assertSee('data-task-total', false)
            ->assertSee('data-task-id="'.$task->id.'"', false)
            ->assertSee('data-bs-target="#task-details-modal"', false)
            ->assertSee('data-task-title="Kanban card test"', false)
            ->assertSee('task-kanban-drag-handle', false)
            ->assertSee('btn-falcon-default task-kanban-drag-handle', false)
            ->assertSee('aria-label="Touch and hold to drag task"', false)
            ->assertSee("sortable.option('handle', '.task-kanban-drag-handle')", false)
            ->assertSee("sortable.option('delayOnTouchOnly', true)", false)
            ->assertSee("sortable.option('scroll', true)", false)
            ->assertSee('data-task-url="'.route('tasks.show', $task).'"', false)
            ->assertSee('id="task-details-modal"', false)
            ->assertSee('data-task-modal-description', false)
            ->assertSee('data-task-count>1</span>', false)
            ->assertSee('vendors/sortablejs/Sortable.min.js')
            ->assertSee('data-sortable=\'{"draggable":".sortable-item-wrapper:not([aria-disabled=true])"}\'', false)
            ->assertSee('window.Sortable.get(zone)', false)
            ->assertSee("sortable.option('onEnd', async", false)
            ->assertSee('card.dataset.status = nextStatus', false)
            ->assertSee("if (!errorBox)", false)
            ->assertSee("boardView.prepend(errorBox)", false)
            ->assertSee("headers: {", false)
            ->assertSee('sortable-item-wrapper task-kanban-card', false)
            ->assertSee("sortable.option('filter', 'a, button')", false)
            ->assertSee('sortable-item kanban-item-card', false)
            ->assertDontSee('stretched-link fw-semibold text-900', false)
            ->assertSee('Task board')
            ->assertSee('Recently Browsed')
            ->assertSee('data-bs-dismiss="search"', false)
            ->assertSee('placeholder="Search..."', false)
            ->assertViewHas('boardTasks', fn (array $tasks): bool => count($tasks) === 1
                && $tasks[0]['title'] === 'Kanban card test');
        $this->getJson(route('tasks.kanban'))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $task->id,
                'title' => 'Kanban card test',
                'status' => 'backlog',
                'can_update' => true,
            ]);

        $this->patch(route('tasks.update-status', $task), ['status' => 'in_progress'], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'in_progress');
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);
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
            ->assertSee('Create the homepage')
            ->assertSee('All project tasks')
            ->assertSee(route('projects.tasks.index', $project), false);
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
            ->assertSee('Retry loading table', false)
            ->assertSee('d-none', false)
            ->assertSee(route('tasks.datatable'), false);
        $this->get(route('projects.tasks.index', $project))
            ->assertOk()
            ->assertSee('Tasks')
            ->assertSee(route('projects.tasks.datatable', $project), false)
            ->assertSee(route('projects.tasks.create', $project), false)
            ->assertSee('Build the website')
            ->assertDontSee('Other project task');
        $this->getJson(route('projects.tasks.kanban', $project))
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['title' => 'Build the website'])
            ->assertJsonFragment(['title' => 'Create the homepage']);
        $this->getJson(route('projects.tasks.datatable', [
            'project' => $project,
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 2);
        $this->getJson(route('tasks.kanban'))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $child->id,
                'title' => 'Create the homepage',
                'status' => 'done',
                'parent' => 'Build the website',
            ]);
        $this->getJson(route('tasks.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'Create the homepage'],
        ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.task', '<div class="small text-body-secondary"><span class="fas fa-level-up-alt fa-rotate-90 me-1" aria-hidden="true"></span>Subtask of Build the website</div><a class="fw-semibold" href="'.route('tasks.show', $child).'">Create the homepage</a><div class="small text-body-secondary"></div>');
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

        $otherProject->tasks()->create([
            'title' => 'Other project task',
            'status' => 'backlog',
            'priority' => 'low',
        ]);
        $this->get(route('projects.tasks.index', $project))
            ->assertOk()
            ->assertDontSee('Other project task');
        $this->getJson(route('projects.tasks.kanban', $project))
            ->assertOk()
            ->assertJsonCount(2);
        $this->getJson(route('tasks.kanban'))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Other project task']);
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
        $firstAssignee->assignRole('member');
        $secondAssignee->assignRole('member');

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
        $assignees->each(fn (User $assignee) => $assignee->assignRole('member'));

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
