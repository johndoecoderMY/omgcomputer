<?php

namespace Tests\Feature;

use App\Models\FlowChart;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlowChartTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_view_update_and_delete_a_flow_chart(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('development.flowcharts.create'))
            ->assertOk()
            ->assertSee('Website pages and connections')
            ->assertSee('Connect this node to')
            ->assertSee('select any number');

        $home = (string) Str::uuid();
        $services = (string) Str::uuid();
        $contact = (string) Str::uuid();
        $steps = [
            ['id' => $home, 'label' => 'Home', 'kind' => 'page', 'connections' => [$services, $contact]],
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
            ->assertSee('data-node-id="' . $home . '"', false)
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

    public function test_a_flow_chart_node_can_create_a_linked_project_task(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Website launch']);
        $stepId = (string) Str::uuid();
        $flowChart = FlowChart::query()->create([
            'title' => 'Website journey',
            'steps' => [[
                'id' => $stepId,
                'label' => 'Build service page',
                'kind' => 'page',
                'connections' => [],
            ]],
        ]);

        $this->actingAs($user)->get(route('development.flowcharts.show', $flowChart))
            ->assertOk()
            ->assertSee('Create task')
            ->assertSee('Website launch');

        $this->actingAs($user)->post(route('development.flowcharts.tasks.store', $flowChart), [
            'step_id' => $stepId,
            'project_id' => $project->id,
            'assigned_to' => $user->id,
        ])->assertRedirect(route('development.flowcharts.show', $flowChart));

        $task = Task::query()->firstOrFail();
        $this->assertSame('Build service page', $task->title);
        $this->assertSame($project->id, $task->project_id);
        $this->assertSame($user->id, $task->assigned_to);
        $this->assertSame($flowChart->id, $task->flow_chart_id);
        $this->assertSame($stepId, $task->flow_chart_step_id);
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
