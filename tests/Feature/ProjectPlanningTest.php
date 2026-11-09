<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectFeature;
use App\Models\ProjectMilestone;
use App\Models\ProjectModule;
use App\Models\ProjectQualityItem;
use App\Models\ProjectRequirement;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectPlanningTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_planning_workspace_tracks_brief_requirements_modules_milestones_and_quality_items(): void
    {
        $admin = $this->adminUser();
        $project = Project::query()->create(['name' => 'Procurement platform']);
        $this->actingAs($admin);

        $this->get(route('projects.planning', $project))
            ->assertOk()
            ->assertSee('data-project-ajax-root', false)
            ->assertSee('data-project-ajax-modal-body', false)
            ->assertSee('Project brief')
            ->assertSee('Requirements')
            ->assertSee('Modules and features')
            ->assertSee('Roadmap and milestones')
            ->assertSee('Testing and issues');

        $this->put(route('projects.planning.overview.update', $project), [
            'business_background' => 'Centralize supplier purchasing.',
            'user_roles' => "Buyer: create orders and RFQs.\nSupplier: manage quotations.",
            'objectives' => 'Reduce manual procurement work.',
            'problem_statement' => 'Orders are spread across email.',
            'proposed_solution' => 'A shared B2B platform.',
            'scope_in' => 'Company accounts and quotations.',
            'scope_out' => 'International shipping.',
            'planning_status' => 'planning',
            'start_date' => '2026-11-01',
            'target_end_date' => '2027-03-01',
            'budget' => '45000.00',
        ])->assertSessionHasNoErrors();

        $this->post(route('projects.planning.modules.store', $project), [
            'name' => 'Quotations',
            'description' => 'Request and compare supplier quotes.',
        ])->assertSessionHasNoErrors();
        $module = ProjectModule::query()->where('project_id', $project->id)->firstOrFail();

        $this->post(route('projects.planning.requirements.store', $project), [
            'requirement' => 'The platform supports business purchasing.',
            'requirement_type' => 'business',
            'priority' => 'must',
            'status' => 'approved',
        ])->assertSessionHasNoErrors();
        $businessRequirement = ProjectRequirement::query()
            ->where('project_id', $project->id)
            ->where('requirement_type', 'business')
            ->firstOrFail();

        $this->post(route('projects.planning.requirements.store', $project), [
            'requirement' => 'Buyers can request quotations.',
            'requirement_type' => 'functional',
            'priority' => 'must',
            'status' => 'approved',
            'user_story' => 'As a buyer, I want to request quotes so I can compare offers.',
            'acceptance_criteria' => 'Buyer submits an RFQ; suppliers can respond.',
            'project_module_id' => $module->id,
            'parent_requirement_id' => $businessRequirement->id,
        ])->assertSessionHasNoErrors();
        $requirement = ProjectRequirement::query()
            ->where('project_id', $project->id)
            ->where('requirement_type', 'functional')
            ->firstOrFail();
        $this->assertSame('FR-01', $requirement->code);
        $this->assertSame('approved', $requirement->status);
        $this->assertSame($businessRequirement->id, $requirement->parent_requirement_id);

        $this->post(route('projects.planning.features.store', [$project, $module]), [
            'name' => 'Create RFQ',
            'description' => 'Submit a request for quotation.',
            'priority' => 'must',
            'status' => 'planned',
        ])->assertSessionHasNoErrors();
        $feature = ProjectFeature::query()->where('project_module_id', $module->id)->firstOrFail();

        $this->post(route('projects.planning.milestones.store', $project), [
            'name' => 'Planning approved',
            'description' => 'Approve project scope and requirements.',
            'deliverable' => 'Signed-off requirements.',
            'starts_on' => '2026-11-01',
            'due_on' => '2026-11-15',
            'status' => 'planned',
        ])->assertSessionHasNoErrors();
        $milestone = ProjectMilestone::query()->where('project_id', $project->id)->firstOrFail();

        $this->post(route('projects.tasks.store', $project), [
            'title' => 'Build RFQ form',
            'description' => 'Implement the buyer-facing form.',
            'status' => 'backlog',
            'priority' => 'high',
            'project_requirement_id' => $requirement->id,
            'project_feature_id' => $feature->id,
            'project_milestone_id' => $milestone->id,
        ])->assertSessionHasNoErrors();
        $task = Task::query()->where('project_id', $project->id)->firstOrFail();
        $this->assertSame($requirement->id, $task->project_requirement_id);
        $this->assertSame($feature->id, $task->project_feature_id);
        $this->assertSame($milestone->id, $task->project_milestone_id);

        $this->post(route('projects.planning.quality-items.store', $project), [
            'kind' => 'test_case',
            'title' => 'Buyer submits a valid RFQ',
            'description' => 'Enter valid supplier and product details.',
            'expected_result' => 'The RFQ is created.',
            'actual_result' => '',
            'priority' => 'high',
            'severity' => '',
            'status' => 'not_run',
            'project_requirement_id' => $requirement->id,
        ])->assertSessionHasNoErrors();
        $this->post(route('projects.planning.quality-items.store', $project), [
            'kind' => 'issue',
            'title' => 'RFQ submission returns an error',
            'description' => 'The submit action fails for a valid RFQ.',
            'expected_result' => '',
            'actual_result' => '',
            'priority' => 'high',
            'severity' => 'high',
            'status' => 'open',
            'project_requirement_id' => $requirement->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, ProjectQualityItem::query()->where('project_id', $project->id)->count());
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'planning_status' => 'planning',
            'budget' => '45000.00',
        ]);
        $this->get(route('tasks.show', $task))
            ->assertOk()
            ->assertSee('data-project-ajax-root', false)
            ->assertSee('data-project-ajax-modal', false)
            ->assertSee('Planning traceability')
            ->assertSee('FR-01');
        $this->get(route('projects.planning', $project))
            ->assertOk()
            ->assertSee('At risk')
            ->assertSee('Buyer: create orders and RFQs.');

        $this->post(route('projects.export', $project), [
            'sections' => ['planning'],
            'flowchart_mode' => 'all',
        ])->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_planning_references_cannot_be_linked_across_projects_and_milestone_cycles_are_rejected(): void
    {
        $admin = $this->adminUser();
        $project = Project::query()->create(['name' => 'First project']);
        $otherProject = Project::query()->create(['name' => 'Second project']);
        $otherModule = $otherProject->modules()->create(['name' => 'Private module']);
        $firstMilestone = $project->milestones()->create(['name' => 'Phase one', 'status' => 'planned']);
        $secondMilestone = $project->milestones()->create([
            'name' => 'Phase two',
            'status' => 'planned',
            'depends_on_milestone_id' => $firstMilestone->id,
        ]);
        $this->actingAs($admin);

        $this->post(route('projects.planning.requirements.store', $project), [
            'requirement' => 'Invalid cross-project module.',
            'requirement_type' => 'functional',
            'priority' => 'must',
            'status' => 'planned',
            'project_module_id' => $otherModule->id,
        ])->assertSessionHasErrors('project_module_id');

        $this->put(route('projects.planning.milestones.update', [$project, $firstMilestone]), [
            'name' => 'Phase one',
            'status' => 'planned',
            'depends_on_milestone_id' => $secondMilestone->id,
        ])->assertSessionHasErrors('depends_on_milestone_id');

        $businessRequirement = $project->requirements()->create([
            'requirement' => 'Business goal',
            'requirement_type' => 'business',
            'code' => 'BR-01',
            'status' => 'planned',
        ]);
        $functionalRequirement = $project->requirements()->create([
            'requirement' => 'Functional requirement',
            'requirement_type' => 'functional',
            'code' => 'FR-01',
            'status' => 'planned',
            'parent_requirement_id' => $businessRequirement->id,
        ]);
        $this->put(route('projects.planning.requirements.update', [$project, $businessRequirement]), [
            'requirement' => 'Business goal',
            'requirement_type' => 'business',
            'priority' => 'must',
            'status' => 'planned',
            'parent_requirement_id' => $functionalRequirement->id,
        ])->assertSessionHasErrors('parent_requirement_id');
    }

    private function adminUser(): User
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
