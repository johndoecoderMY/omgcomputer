<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\CafePosProjectSeeder;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CafePosProjectSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_cafe_pos_project_and_requirements_idempotently(): void
    {
        $seeder = new CafePosProjectSeeder;

        $seeder->run();
        $project = Project::query()->where('name', 'Cafe POS System')->firstOrFail();
        $project->refresh();
        $this->assertSame('Plan a point-of-sale system for cafe counter service, order handling, and sales reporting.', $project->description);
        $this->assertSame('planning', $project->planning_status);

        $project->update([
            'description' => 'Cafe owner-provided description.',
            'problem_statement' => 'Keep this project brief.',
        ]);
        $seeder->run();

        $project->refresh();
        $this->assertSame('Cafe owner-provided description.', $project->description);
        $this->assertSame('Keep this project brief.', $project->problem_statement);
        $this->assertSame('planning', $project->planning_status);
        $this->assertSame(16, $project->requirements()->count());
        $this->assertSame(3, $project->requirements()->where('requirement_type', 'business')->count());
        $this->assertSame(8, $project->requirements()->where('requirement_type', 'functional')->count());
        $this->assertSame(5, $project->requirements()->where('requirement_type', 'non_functional')->count());
        $this->assertSame(16, $project->requirements()->whereNotNull('code')->distinct('code')->count('code'));
        $this->assertDatabaseHas('project_requirements', [
            'project_id' => $project->id,
            'code' => 'FR-04',
            'requirement_type' => 'functional',
            'requirement' => 'A cashier can record cash, card, or e-wallet payment and issue a receipt without storing card details.',
        ]);
        $this->assertDatabaseHas('project_requirements', [
            'project_id' => $project->id,
            'code' => 'NFR-05',
            'requirement_type' => 'non_functional',
            'status' => 'planned',
        ]);

        $this->actingAs($this->adminUser())
            ->get(route('projects.planning', $project))
            ->assertOk()
            ->assertSee('Cafe POS System')
            ->assertSee('FR-01')
            ->assertSee('NFR-05')
            ->assertSee('A cashier can record cash, card, or e-wallet payment');

        $project->requirements()->create([
            'code' => 'CUSTOM-01',
            'requirement' => 'Cafe-specific custom requirement.',
        ]);
        $seeder->run();

        $this->assertSame(17, $project->requirements()->count());
        $this->assertDatabaseHas('project_requirements', [
            'project_id' => $project->id,
            'code' => 'CUSTOM-01',
            'requirement' => 'Cafe-specific custom requirement.',
        ]);
    }

    private function adminUser(): User
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
