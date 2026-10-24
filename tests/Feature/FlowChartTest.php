<?php

namespace Tests\Feature;

use App\Models\FlowChart;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DeveloperSetupFlowChartSeeder;
use Database\Seeders\ServiceSopExampleSeeder;
use Database\Seeders\SoftwareStructureSopSeeder;
use Database\Seeders\StudentFypSopSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FlowChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_setup_flow_charts_are_seeded_with_valid_connections_and_repeat_safely(): void
    {
        $this->seed(DeveloperSetupFlowChartSeeder::class);
        $this->seed(DeveloperSetupFlowChartSeeder::class);

        $flowCharts = FlowChart::query()
            ->whereIn('title', [
                'Arduino CLI Setup & Upload',
                'ESP32 Setup with Arduino IDE',
                'Laravel Project Setup',
                'Database Tables & Relationship Example',
            ])
            ->get();

        $this->assertCount(4, $flowCharts);
        $this->assertTrue($flowCharts->every(fn (FlowChart $flowChart): bool => $flowChart->is_example && $flowChart->category === 'process'));

        foreach ($flowCharts as $flowChart) {
            $stepIds = collect($flowChart->steps)->pluck('id');
            $this->assertNotEmpty($flowChart->steps);
            foreach ($flowChart->steps as $step) {
                foreach ($step['connections'] as $targetId) {
                    $this->assertTrue($stepIds->contains($targetId), "{$flowChart->title} has a connection to an unknown step.");
                    $sides = $step['connection_sides'][$targetId] ?? null;
                    $this->assertIsArray($sides);
                    $this->assertContains($sides['source'] ?? null, ['top', 'bottom', 'left', 'right']);
                    $this->assertContains($sides['target'] ?? null, ['top', 'bottom', 'left', 'right']);
                }
            }
        }

        $this->assertStringContainsString('arduino cli', strtolower($flowCharts->firstWhere('title', 'Arduino CLI Setup & Upload')->steps[1]['label']));
        $this->assertStringContainsString('esp32', strtolower($flowCharts->firstWhere('title', 'ESP32 Setup with Arduino IDE')->steps[3]['label']));
        $this->assertStringContainsString('foreign key', strtolower($flowCharts->firstWhere('title', 'Database Tables & Relationship Example')->steps[2]['label']));
    }

    public function test_all_sop_flow_chart_seeders_save_directions_and_preserve_step_ids(): void
    {
        $seeders = [
            ServiceSopExampleSeeder::class,
            StudentFypSopSeeder::class,
            SoftwareStructureSopSeeder::class,
            DeveloperSetupFlowChartSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            $this->seed($seeder);
        }

        $flowCharts = FlowChart::query()->where('is_example', true)->orderBy('title')->get();
        $this->assertCount(14, $flowCharts);

        $stepIdsByChart = [];
        foreach ($flowCharts as $flowChart) {
            $stepIdsByChart[$flowChart->title] = collect($flowChart->steps)->pluck('id')->all();
            foreach ($flowChart->steps as $step) {
                foreach ($step['connections'] as $targetId) {
                    $sides = $step['connection_sides'][$targetId] ?? null;
                    $this->assertIsArray($sides, "{$flowChart->title} is missing endpoint directions.");
                    $this->assertContains($sides['source'] ?? null, ['top', 'bottom', 'left', 'right']);
                    $this->assertContains($sides['target'] ?? null, ['top', 'bottom', 'left', 'right']);
                }
            }
        }

        foreach ($seeders as $seeder) {
            $this->seed($seeder);
        }

        $this->assertSame(
            $stepIdsByChart,
            FlowChart::query()->where('is_example', true)->orderBy('title')
                ->get()->mapWithKeys(fn (FlowChart $flowChart): array => [
                    $flowChart->title => collect($flowChart->steps)->pluck('id')->all(),
                ])->all(),
        );
    }

    public function test_authenticated_user_can_create_view_update_and_delete_a_flow_chart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('development.flowcharts.create'))
            ->assertOk()
            ->assertSee('Website journey pages and connections')
            ->assertSee('Website structure')
            ->assertSee('Sitemap')
            ->assertSee('Live preview')
            ->assertSee('Choose a side arrow on the source node')
            ->assertDontSee('flow-chart-connections-modal');

        $home = (string) Str::uuid();
        $services = (string) Str::uuid();
        $contact = (string) Str::uuid();
        $steps = [
            [
                'id' => $home,
                'label' => 'Home',
                'kind' => 'page',
                'connections' => [$services, $contact],
                'connection_sides' => [$services => ['source' => 'right', 'target' => 'left']],
            ],
            ['id' => $services, 'label' => 'Services', 'kind' => 'page', 'connections' => []],
            ['id' => $contact, 'label' => 'Contact', 'kind' => 'page', 'connections' => []],
        ];

        $this->post(route('development.flowcharts.store'), [
            'title' => 'Website journey',
            'description' => 'Visitor path through the site.',
            'steps' => $steps,
        ])->assertRedirect();

        $flowChart = FlowChart::firstOrFail();
        $this->assertSame($steps, $flowChart->steps);

        $this->get(route('development.flowcharts.show', $flowChart))
            ->assertOk()
            ->assertSee('Website structure')
            ->assertSee('Services')
            ->assertSee('Contact')
            ->assertSee('data-flow-chart', false)
            ->assertSee('data-node-id="'.$home.'"', false)
            ->assertSee($services, false)
            ->assertSee($contact, false);

        $updatedSteps = [
            ['id' => $home, 'label' => 'Home', 'kind' => 'page', 'connections' => [$contact]],
            ['id' => $contact, 'label' => 'Contact us', 'kind' => 'action', 'connections' => []],
        ];

        $this->put(route('development.flowcharts.update', $flowChart), [
            'title' => 'Updated website journey',
            'description' => null,
            'steps' => $updatedSteps,
        ])->assertRedirect(route('development.flowcharts.show', $flowChart));

        $this->assertDatabaseHas('flow_charts', [
            'id' => $flowChart->id,
            'title' => 'Updated website journey',
        ]);
        $this->assertSame($updatedSteps, $flowChart->fresh()->steps);

        $this->delete(route('development.flowcharts.destroy', $flowChart))
            ->assertRedirect(route('development.flowcharts.index'));

        $this->assertDatabaseMissing('flow_charts', ['id' => $flowChart->id]);
    }

    public function test_flow_chart_rejects_connections_to_unknown_steps(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->from(route('development.flowcharts.create'))
            ->post(route('development.flowcharts.store'), [
                'title' => 'Invalid website map',
                'steps' => [
                    [
                        'id' => 'home',
                        'label' => 'Home',
                        'kind' => 'page',
                        'connections' => ['missing-page'],
                    ],
                ],
            ]);

        $response->assertSessionHasErrors('steps.0.connections');
        $this->assertDatabaseCount('flow_charts', 0);
    }

    public function test_example_flow_charts_can_only_be_duplicated_into_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Website launch']);
        $flowChart = FlowChart::query()->create([
            'title' => 'Reusable website journey',
            'is_example' => true,
            'steps' => [['id' => (string) Str::uuid(), 'label' => 'Build service page', 'kind' => 'page', 'connections' => []]],
        ]);

        $this->actingAs($user)->get(route('development.flowcharts.show', $flowChart))
            ->assertOk()
            ->assertSee('This example can be copied')
            ->assertDontSee('name="assigned_to"', false)
            ->assertDontSee('flowcharts/'.$flowChart->id.'/tasks', false);

        $this->post(route('projects.flowcharts.link', $project), [
            'flow_chart_ids' => [$flowChart->id],
        ])->assertSessionHasErrors('flow_chart_ids');
        $this->put(route('projects.flowcharts.sync', $project), [
            'flow_chart_ids' => [$flowChart->id],
        ])->assertSessionHasErrors('flow_chart_ids');
        $this->assertDatabaseCount('flow_chart_project', 0);

        $this->post(route('projects.flowcharts.duplicate', $project), [
            'flow_chart_ids' => [$flowChart->id],
        ])->assertRedirect(route('projects.show', $project));
        $copy = FlowChart::query()->where('title', 'Reusable website journey — Website launch copy')->firstOrFail();
        $this->assertFalse($copy->is_example);
        $this->assertTrue($project->flowCharts()->whereKey($copy->id)->exists());

        $this->post(route('development.flowcharts.store'), [
            'project_id' => $project->id,
            'title' => 'Invalid linked example',
            'is_example' => '1',
            'steps' => [['id' => (string) Str::uuid(), 'label' => 'Start', 'kind' => 'start', 'connections' => []]],
        ])->assertStatus(422);
    }

    public function test_custom_flow_chart_steps_can_be_added_as_tasks_to_a_linked_project(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Website launch']);
        $firstStepId = (string) Str::uuid();
        $secondStepId = (string) Str::uuid();
        $flowChart = FlowChart::query()->create([
            'title' => 'Website launch process',
            'steps' => [
                ['id' => $firstStepId, 'label' => 'Prepare homepage', 'kind' => 'action', 'connections' => [$secondStepId]],
                ['id' => $secondStepId, 'label' => 'Review homepage', 'kind' => 'action', 'connections' => []],
            ],
        ]);
        $flowChart->projects()->attach($project);
        $this->actingAs($user);

        $this->get(route('development.flowcharts.show', $flowChart))
            ->assertOk()
            ->assertSee('Add flow chart steps to tasks')
            ->assertSee('Add steps as tasks');

        $this->post(route('development.flowcharts.tasks.store', $flowChart), [
            'project_id' => $project->id,
        ])->assertRedirect(route('development.flowcharts.show', $flowChart))
            ->assertSessionHas('success', '2 task(s) created from the flow chart steps.');

        $this->get(route('development.flowcharts.show', $flowChart))
            ->assertOk()
            ->assertSee('data-flash-toast', false)
            ->assertSee('2 task(s) created from the flow chart steps.');

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'flow_chart_id' => $flowChart->id,
            'flow_chart_step_id' => $firstStepId,
            'title' => 'Prepare homepage',
        ]);
        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'flow_chart_id' => $flowChart->id,
            'flow_chart_step_id' => $secondStepId,
            'title' => 'Review homepage',
        ]);

        $this->post(route('development.flowcharts.tasks.store', $flowChart), [
            'project_id' => $project->id,
        ])->assertSessionHas('success', 'Tasks for this flow chart already exist in the selected project.');
        $this->assertDatabaseCount('tasks', 2);
    }

    public function test_flow_chart_steps_cannot_be_added_to_unlinked_or_example_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Website launch']);
        $flowChart = FlowChart::query()->create([
            'title' => 'Unlinked process',
            'steps' => [['id' => 'step-one', 'label' => 'Prepare homepage', 'kind' => 'action', 'connections' => []]],
        ]);
        $example = FlowChart::query()->create([
            'title' => 'Example process',
            'is_example' => true,
            'steps' => [['id' => 'example-step', 'label' => 'Prepare homepage', 'kind' => 'action', 'connections' => []]],
        ]);
        $this->actingAs($user);

        $this->from(route('development.flowcharts.show', $flowChart))
            ->post(route('development.flowcharts.tasks.store', $flowChart), ['project_id' => $project->id])
            ->assertSessionHasErrors('project_id');
        $this->post(route('development.flowcharts.tasks.store', $example), ['project_id' => $project->id])
            ->assertNotFound();
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_flow_chart_node_positions_can_be_saved_and_survive_edits(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $firstId = (string) Str::uuid();
        $secondId = (string) Str::uuid();
        $flowChart = FlowChart::query()->create([
            'title' => 'Positioned website map',
            'steps' => [
                ['id' => $firstId, 'label' => 'Home', 'kind' => 'page', 'connections' => [$secondId]],
                ['id' => $secondId, 'label' => 'Services', 'kind' => 'page', 'connections' => []],
            ],
        ]);

        $this->patch(route('development.flowcharts.layout', $flowChart), [
            'positions' => [
                ['id' => $firstId, 'x' => 140, 'y' => 90],
                ['id' => $secondId, 'x' => 560, 'y' => 280],
            ],
        ])->assertRedirect(route('development.flowcharts.show', $flowChart));

        $savedSteps = $flowChart->fresh()->steps;
        $this->assertSame(140, $savedSteps[0]['x']);
        $this->assertSame(280, $savedSteps[1]['y']);
        $this->get(route('development.flowcharts.show', $flowChart))
            ->assertOk()
            ->assertSee('data-position-x="140"', false)
            ->assertSee('data-position-y="280"', false);
        $this->get(route('development.flowcharts.edit', $flowChart))
            ->assertOk()
            ->assertSee('name="steps[0][x]" value="140"', false)
            ->assertSee('name="steps[1][y]" value="280"', false);

        $this->put(route('development.flowcharts.update', $flowChart), [
            'title' => 'Positioned website map updated',
            'steps' => [
                ['id' => $firstId, 'label' => 'Home page', 'kind' => 'page', 'connections' => [$secondId], 'x' => 140, 'y' => 90],
                ['id' => $secondId, 'label' => 'Services', 'kind' => 'page', 'connections' => [], 'x' => 560, 'y' => 280],
            ],
        ])->assertRedirect(route('development.flowcharts.show', $flowChart));

        $this->assertSame(140, $flowChart->fresh()->steps[0]['x']);
        $this->assertSame(560, $flowChart->fresh()->steps[1]['x']);
    }
}
