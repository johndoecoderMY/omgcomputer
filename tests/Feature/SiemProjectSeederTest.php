<?php

namespace Tests\Feature;

use App\Models\Project;
use Database\Seeders\SiemProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiemProjectSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_beginner_friendly_siem_project_and_tasks_idempotently(): void
    {
        $seeder = new SiemProjectSeeder;
        $seeder->run();
        $seeder->run();

        $project = Project::query()->where('name', 'SIEM')->firstOrFail();

        $this->assertSame(6, $project->tasks()->whereNull('parent_task_id')->count());
        $this->assertSame(18, $project->tasks()->whereNotNull('parent_task_id')->count());
        $this->assertSame(
            0,
            $project->tasks()->whereNull('parent_task_id')->where('status', '!=', 'backlog')->count(),
        );
        $this->assertStringContainsString('only computers you own', $project->requirements_notes);
        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Understand the project in simple words',
        ]);
        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Demonstrate safe file-change monitoring',
        ]);
    }
}
