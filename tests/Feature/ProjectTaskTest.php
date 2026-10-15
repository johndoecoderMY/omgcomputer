<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_task_and_manage_its_subtasks(): void
    {
        $this->actingAs(User::factory()->create());
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
        $this->actingAs(User::factory()->create());
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
            ->assertSee('Build the website')
            ->assertSee('Main website');

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
        $this->actingAs(User::factory()->create());

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
        $this->actingAs(User::factory()->create());
        $project = Project::create(['name' => 'Customer portal']);
        $task = $project->tasks()->create([
            'assigned_to' => $assignee->id,
            'title' => 'Review account page',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $this->get(route('tasks.edit', $task))
            ->assertOk()
            ->assertSee('Assignee')
            ->assertSee('Jordan Lee');
    }
}
