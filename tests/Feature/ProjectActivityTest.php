<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProjectActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_backfill_adds_a_truthful_starting_entry_for_legacy_projects(): void
    {
        $admin = User::factory()->create(['name' => 'Project Admin']);
        $project = Project::query()->create([
            'name' => 'Legacy project',
            'created_by_user_id' => $admin->id,
        ]);
        $migration = require base_path('database/migrations/2026_11_21_000001_backfill_project_history.php');

        $migration->up();

        $this->assertDatabaseHas('project_activities', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'summary' => 'Project created (historical record)',
            'created_at' => $project->created_at,
        ]);
        $details = DB::table('project_activities')
            ->where('project_id', $project->id)
            ->value('details');
        $this->assertStringContainsString('older changes cannot be reconstructed', $details);
    }

    public function test_task_and_planning_changes_are_recorded_in_project_history(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create(['name' => 'Project Admin']);
        $admin->assignRole('admin');
        $this->actingAs($admin);
        $project = Project::query()->create(['name' => 'Tracked project']);

        $this->put(route('projects.planning.overview.update', $project), [
            'business_background' => 'Centralize supplier purchasing.',
            'planning_status' => 'planning',
        ])->assertSessionHasNoErrors();

        $this->post(route('projects.planning.modules.store', $project), [
            'name' => 'Quotations',
            'description' => 'Manage supplier quotes.',
        ])->assertSessionHasNoErrors();

        $this->post(route('projects.tasks.store', $project), [
            'title' => 'Build RFQ form',
            'description' => 'Implement the buyer-facing form.',
            'status' => 'backlog',
            'priority' => 'high',
        ])->assertSessionHasNoErrors();
        $task = $project->tasks()->firstOrFail();

        $this->patch(route('tasks.update-status', $task), [
            'status' => 'in_progress',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('project_activities', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'summary' => 'Updated project brief and planning details',
        ]);
        $this->assertDatabaseHas('project_activities', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'summary' => 'Added module',
        ]);
        $this->assertDatabaseHas('project_activities', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'summary' => 'Added task',
        ]);
        $this->assertDatabaseHas('project_activities', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'summary' => 'Updated task',
        ]);
    }

    public function test_project_creation_and_stakeholder_changes_are_shown_in_project_history(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create(['name' => 'Project Admin']);
        $admin->assignRole('admin');
        $member = User::factory()->create(['name' => 'Project Member']);
        $member->assignRole('member');
        $customer = User::factory()->create([
            'name' => 'Project Customer',
            'email' => 'history-customer@example.test',
        ]);
        $customer->assignRole('customer');

        $this->actingAs($admin)->post(route('projects.store'), [
            'name' => 'History project',
            'description' => 'Initial description',
            'customer_identity' => 'account:'.$customer->id,
            'assignees_present' => '1',
            'assignees' => [$member->id],
        ])->assertRedirect();

        $project = Project::query()->where('name', 'History project')->firstOrFail();
        $this->assertDatabaseHas('project_activities', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'summary' => 'Project created',
        ]);

        $this->put(route('projects.update', $project), [
            'name' => 'History project updated',
            'description' => 'Updated description',
            'customer_identity' => '',
            'assignees_present' => '1',
            'assignees' => [],
        ])->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('project_activities', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'summary' => 'Updated project details',
        ]);
        $this->assertDatabaseHas('project_activities', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'summary' => 'Unlinked the customer',
        ]);
        $this->assertDatabaseHas('project_activities', [
            'project_id' => $project->id,
            'user_id' => $admin->id,
            'summary' => 'Updated assigned people',
        ]);

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Project history')
            ->assertSee('data-bs-target="#project-history-modal"', false)
            ->assertSee('id="project-history-modal"', false)
            ->assertDontSee('id="project-history"', false)
            ->assertSee('Project created')
            ->assertSee('Updated project details')
            ->assertSee('Unlinked the customer')
            ->assertSee('Updated assigned people')
            ->assertSee('Removed 1 team member')
            ->assertSee('Project Admin');
    }
}
