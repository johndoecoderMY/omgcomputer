<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\FlowChart;
use App\Models\InventoryPart;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DepartmentTeamSeeder;
use Database\Seeders\PlannerRoleSeeder;
use Database\Seeders\ServiceSopExampleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrganizationInventorySopTest extends TestCase
{
    use RefreshDatabase;

    public function test_departments_and_teams_support_crud_and_member_assignment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get(route('departments.index'))->assertOk()->assertSee('Departments');
        $this->get(route('departments.create'))->assertOk();
        $this->get(route('teams.index'))
            ->assertOk()
            ->assertSee('Teams')
            ->assertSee('data-bs-target="#create-team-modal"', false)
            ->assertSee('id="create-team-modal"', false)
            ->assertDontSee('<details class="card mb-3">', false);
        $this->post(route('departments.store'), ['name' => 'Operations'])
            ->assertRedirect();
        $department = Department::query()->firstOrFail();
        $this->get(route('departments.show', $department))->assertOk()->assertSee('Operations');
        $this->get(route('departments.edit', $department))->assertOk();
        $this->get(route('departments.delete', $department))->assertOk();
        $this->getJson(route('departments.datatable', ['draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()->assertJsonPath('data.0.name', 'Operations');

        $this->post(route('teams.store'), [
            'department_id' => $department->id,
            'name' => 'Repair team',
            'description' => 'Handles device repairs.',
            'user_ids' => [$user->id],
        ])->assertRedirect(route('teams.index'));

        $team = Team::query()->firstOrFail();
        $this->assertTrue($user->fresh()->teams->contains('id', $team->id));
        $this->get(route('teams.index'))->assertOk()->assertSee('data-datatable', false);
        $this->getJson(route('teams.datatable', ['draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Repair team')
            ->assertJsonPath('data.0.department_name', 'Operations');
        $this->assertStringContainsString(
            route('members.show', $user),
            $this->getJson(route('teams.datatable', ['draw' => 1, 'start' => 0, 'length' => 25]))
                ->json('data.0.members'),
        );
        $this->get(route('teams.edit', $team))->assertOk()->assertSee('Repair team');
        $this->post(route('teams.store'), [
            'department_id' => '',
            'name' => 'Independent team',
        ])->assertRedirect(route('teams.index'));
        $unassignedDepartmentTeam = Team::query()->where('name', 'Independent team')->firstOrFail();
        $this->assertNull($unassignedDepartmentTeam->department_id);
        $this->getJson(route('teams.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'Independent team'],
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.department_name', '—');

        $this->post(route('teams.store'), [
            'department_id' => $department->id,
            'name' => 'Support team',
            'user_ids' => [$user->id],
        ])->assertRedirect(route('teams.index'));
        $secondTeam = Team::query()->where('name', 'Support team')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$team->id, $secondTeam->id],
            $user->fresh()->teams->modelKeys(),
        );

        $this->put(route('teams.update', $team), [
            'department_id' => $department->id,
            'name' => 'Service team',
            'description' => 'Updated team.',
            'user_ids' => [],
        ])->assertRedirect(route('teams.index'));
        $this->assertSame('Service team', $team->fresh()->name);
        $this->assertEquals([$secondTeam->id], $user->fresh()->teams->modelKeys());

        $this->delete(route('teams.destroy', $team))->assertRedirect(route('teams.index'));
        $this->assertTrue($user->fresh()->teams->contains('id', $secondTeam->id));
        $this->delete(route('teams.destroy', $unassignedDepartmentTeam))->assertRedirect(route('teams.index'));
        $this->delete(route('teams.destroy', $secondTeam))->assertRedirect(route('teams.index'));
        $this->delete(route('departments.destroy', $department))->assertRedirect(route('departments.index'));
        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
        $this->assertCount(0, $user->fresh()->teams);
    }

    public function test_department_and_team_examples_seed_repeatedly_without_duplicates(): void
    {
        $seeder = new DepartmentTeamSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertDatabaseHas('departments', ['name' => 'Student Projects']);
        $this->assertDatabaseHas('teams', ['name' => 'FYP Mentoring']);
        $this->assertDatabaseCount('departments', 3);
        $this->assertDatabaseCount('teams', 6);
    }

    public function test_supplier_crud_links_parts_and_deletion_preserves_inventory(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);
        $this->get(route('inventory.suppliers.index'))->assertOk()->assertSee('data-datatable', false)->assertSee('Add supplier');
        $this->get(route('inventory.suppliers.create'))
            ->assertOk()
            ->assertSee('name="website"', false);
        $this->post(route('inventory.suppliers.store'), [
            'name' => 'Parts Wholesale',
            'contact_name' => 'Alex Supplier',
            'email' => 'sales@example.test',
            'phone' => '+60123456789',
            'website' => 'https://supplier.example.test',
        ])->assertRedirect();
        $supplier = Supplier::query()->firstOrFail();
        $this->get(route('inventory.suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Parts Wholesale')
            ->assertSee('href="https://supplier.example.test"', false);

        $part = InventoryPart::query()->create([
            'name' => 'Laptop charger',
            'supplier_id' => $supplier->id,
            'cost_price' => 12,
            'selling_price' => 20,
            'stock_quantity' => 4,
            'reorder_level' => 1,
        ]);
        $this->get(route('inventory.parts'))->assertOk()->assertSee('data-datatable', false);
        $this->getJson(route('inventory.suppliers.datatable', ['draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Parts Wholesale')
            ->assertJsonPath('data.0.website_link', '<a href="https://supplier.example.test" target="_blank" rel="noopener noreferrer">Visit website</a>');
        $this->getJson(route('inventory.parts.datatable', ['draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()->assertJsonPath('data.0.supplier_name', 'Parts Wholesale');
        $this->get(route('inventory.suppliers.edit', $supplier))->assertOk()->assertSee('Parts Wholesale');

        $this->put(route('inventory.suppliers.update', $supplier), [
            'name' => 'Parts Wholesale MY',
            'email' => 'updated@example.test',
            'website' => 'https://updated-supplier.example.test',
        ])->assertRedirect(route('inventory.suppliers.show', $supplier));
        $this->assertSame('Parts Wholesale MY', $supplier->fresh()->name);
        $this->assertSame('https://updated-supplier.example.test', $supplier->fresh()->website);

        $this->get(route('inventory.suppliers.delete', $supplier))->assertOk();
        $this->delete(route('inventory.suppliers.destroy', $supplier))
            ->assertRedirect(route('inventory.suppliers.index'));
        $this->assertNull($part->fresh()->supplier_id);
        $this->assertDatabaseHas('inventory_parts', ['id' => $part->id, 'name' => 'Laptop charger']);
    }

    public function test_product_and_service_sops_can_be_created_and_linked_to_projects(): void
    {
        $this->actingAs(User::factory()->create());
        $project = Project::query()->create(['name' => 'Service operations']);
        $stepId = (string) Str::uuid();

        $this->post(route('development.flowcharts.store'), [
            'title' => 'Laptop cleaning procedure',
            'category' => 'service',
            'subject_name' => 'Laptop cleaning',
            'description' => 'Safe standard procedure.',
            'steps' => [
                ['id' => $stepId, 'label' => 'Inspect and isolate device', 'kind' => 'action', 'connections' => []],
            ],
        ])->assertRedirect();

        $flowChart = FlowChart::query()->firstOrFail();
        $this->assertSame('service', $flowChart->category);
        $this->assertSame('Laptop cleaning', $flowChart->subject_name);
        FlowChart::query()->create([
            'title' => 'Example service procedure',
            'category' => 'service',
            'subject_name' => 'Example service',
            'is_example' => true,
            'steps' => [['id' => 'example-step', 'label' => 'Review request', 'kind' => 'action', 'connections' => []]],
        ]);
        FlowChart::query()->create([
            'title' => 'Laptop maintenance product SOP',
            'category' => 'product',
            'subject_name' => 'Laptop maintenance',
            'steps' => [['id' => 'product-step', 'label' => 'Inspect item', 'kind' => 'action', 'connections' => []]],
        ]);
        $this->get(route('development.sops', ['category' => 'service']))
            ->assertOk()
            ->assertSee('SOP Library')
            ->assertSee('data-datatable-filter', false)
            ->assertSee('Add new SOP')
            ->assertSee(route('development.sops.datatable'), false);
        $this->getJson(route('development.sops.datatable', ['draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 3);
        $this->getJson(route('development.sops.datatable', ['category' => 'service', 'draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson(route('development.sops.datatable', ['category' => 'service', 'type' => 'custom', 'draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.title', 'Laptop cleaning procedure');
        $this->getJson(route('development.sops.datatable', ['category' => 'service', 'type' => 'example', 'draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.title', 'Example service procedure');
        $this->getJson(route('development.sops.datatable', [
            'category' => 'service',
            'subject_name' => 'Laptop cleaning',
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.subject_name', 'Laptop cleaning');
        $this->getJson(route('development.flowcharts.datatable', ['draw' => 1, 'start' => 0, 'length' => 25]))
            ->assertOk()->assertJsonPath('data.0.subject_name', 'Laptop cleaning');
        $this->get(route('development.sops', ['category' => 'invalid']))->assertOk();
        $this->get(route('development.sops.create'))
            ->assertOk()
            ->assertSee('Create SOP')
            ->assertSee('Product');

        $this->put(route('projects.flowcharts.sync', $project), [
            'flow_chart_ids' => [$flowChart->id],
        ])->assertRedirect(route('projects.show', $project));

        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Flow charts &amp; SOPs', false)
            ->assertSee('data-datatable', false);
        $this->getJson(route('projects.flowcharts.datatable', [
            'project' => $project->id,
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Laptop cleaning procedure')
            ->assertJsonPath('recordsTotal', 1);
        $this->assertDatabaseHas('flow_chart_project', [
            'project_id' => $project->id,
            'flow_chart_id' => $flowChart->id,
        ]);

        $this->put(route('projects.flowcharts.sync', $project), ['flow_chart_ids' => []]);
        $this->assertDatabaseMissing('flow_chart_project', [
            'project_id' => $project->id,
            'flow_chart_id' => $flowChart->id,
        ]);
    }

    public function test_new_sops_start_with_a_connected_category_template_and_example_seeder_is_repeatable(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('development.flowcharts.create', ['category' => 'service']))
            ->assertOk()
            ->assertSee('Receive the service request and open a job ticket')
            ->assertSee('Does the customer approve the scope or quote?');
        $this->get(route('development.flowcharts.create', ['category' => 'product']))
            ->assertOk()
            ->assertSee('Prepare or produce the item using the approved procedure');

        $seeder = new ServiceSopExampleSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertDatabaseCount('flow_charts', 1);
        $example = FlowChart::query()->firstOrFail();
        $this->assertSame('Laptop Repair Service SOP', $example->title);
        $this->assertTrue($example->is_example);
        $this->assertSame('service', $example->category);
        $this->assertCount(10, $example->steps);
        $this->assertSame(
            [$example->steps[4]['id'], $example->steps[8]['id']],
            $example->steps[3]['connections'],
        );
        $this->get(route('development.sops', ['category' => 'service']))
            ->assertOk()
            ->assertSee('Browse all product, service, and student FYP SOPs');
    }

    public function test_supplier_table_pages_records_on_the_server(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (range(1, 30) as $number) {
            Supplier::query()->create(['name' => "Supplier {$number}"]);
        }

        $this->getJson(route('inventory.suppliers.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 30)
            ->assertJsonPath('recordsFiltered', 30)
            ->assertJsonCount(10, 'data');
    }
}
