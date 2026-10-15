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

        $this->get(route('projects.index'))
            ->assertOk()
            ->assertSee('OMG Computer');
    }
}
