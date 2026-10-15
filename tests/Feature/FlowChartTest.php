<?php

namespace Tests\Feature;

use App\Models\FlowChart;
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
}
