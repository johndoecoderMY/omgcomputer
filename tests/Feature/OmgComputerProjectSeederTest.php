<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\OmgComputerProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OmgComputerProjectSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_one_omg_computer_project_when_run_repeatedly(): void
    {
        $this->actingAs(User::factory()->create());
        $this->seed(OmgComputerProjectSeeder::class);
        $this->seed(OmgComputerProjectSeeder::class);

        $this->assertDatabaseCount('projects', 1);
        $this->assertDatabaseHas('projects', [
            'name' => 'OMG Computer',
            'description' => 'Website, repair services, shop operations, and student technology services.',
        ]);
        $project = Project::query()->where('name', 'OMG Computer')->firstOrFail();
        $this->assertDatabaseCount('project_requirements', 8);
        $this->assertDatabaseCount('project_technologies', 5);
        $this->assertDatabaseCount('project_estimate_items', 5);
        $this->assertDatabaseCount('events', 4);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'requirements_notes' => 'Initial launch scope for OMG Computer. Confirm business registration, licensing, provider choices, pricing, and payment processing requirements before launch.',
        ]);
        $this->assertDatabaseHas('project_requirements', [
            'project_id' => $project->id,
            'requirement' => 'Create an e-commerce storefront with product listings and stock information.',
        ]);
        $this->assertDatabaseHas('project_technologies', [
            'project_id' => $project->id,
            'name' => 'Laravel',
            'version' => '12',
        ]);
        $this->assertDatabaseHas('project_estimate_items', [
            'project_id' => $project->id,
            'category' => 'software',
            'quantity' => 12,
            'unit_price' => 180,
        ]);
        $this->assertDatabaseHas('events', [
            'project_id' => $project->id,
            'title' => 'Choose e-commerce platform and payment gateway',
            'event_type' => 'progress_review',
        ]);

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('OMG Computer');
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Project requirements')
            ->assertSee('Laravel')
            ->assertSee('Software and other estimates')
            ->assertSee('Choose e-commerce platform and payment gateway');
    }
}
