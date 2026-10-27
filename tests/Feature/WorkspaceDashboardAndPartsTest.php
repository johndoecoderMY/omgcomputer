<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Event;
use App\Models\FlowChart;
use App\Models\InventoryPart;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use Database\Seeders\SoftwareStructureSopSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceDashboardAndPartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summarizes_projects_tickets_schedule_and_structure_examples(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Student capstone']);
        $project->tasks()->create([
            'title' => 'Completed prototype',
            'status' => 'done',
            'priority' => 'medium',
            'assigned_to' => $user->id,
        ]);
        $project->tasks()->create([
            'title' => 'Write test plan',
            'status' => 'in_progress',
            'priority' => 'high',
            'assigned_to' => $user->id,
            'due_date' => today()->subDay(),
        ]);
        $customer = Customer::query()->create(['name' => 'Dashboard customer']);
        RepairTicket::query()->create([
            'ticket_number' => 'RT-DASH-0001',
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'device_model' => 'ThinkPad',
            'issue_description' => 'Battery issue.',
            'status' => 'waiting_parts',
            'priority' => 'urgent',
            'received_at' => now(),
        ]);
        Event::query()->create([
            'project_id' => $project->id,
            'title' => 'Design review',
            'event_type' => 'consultation',
            'starts_at' => now()->addDays(2),
        ]);
        (new SoftwareStructureSopSeeder)->run();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('1 overdue')
            ->assertSee('1 high priority')
            ->assertSee('Student capstone')
            ->assertSee('Design review')
            ->assertSee('dashboard-task-status-chart', false)
            ->assertSee('dashboard-ticket-status-chart', false)
            ->assertSee('dashboard-ticket-arrivals-chart', false)
            ->assertSee('text-bg-secondary')
            ->assertSee('text-bg-primary')
            ->assertSee('text-bg-success')
            ->assertSee('Website Structure & Build SOP')
            ->assertSee('Database Design & Migration SOP');
    }

    public function test_project_parts_can_be_recommended_without_changing_stock(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Hardware refresh']);
        $part = InventoryPart::query()->create([
            'name' => '16GB DDR4 memory',
            'sku' => 'RAM-16-DDR4',
            'cost_price' => 9.50,
            'selling_price' => 12.50,
            'stock_quantity' => 5,
            'reorder_level' => 1,
        ]);

        $this->actingAs($user)->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Parts & hardware recommendations')
            ->assertSee('Find a part');

        $this->post(route('projects.parts.store', $project), [
            'inventory_part_id' => $part->id,
            'quantity' => 2,
            'price_source' => 'selling_price',
            'notes' => 'Use the laptop-compatible memory kit.',
            'url_sources' => [
                'https://example.test/memory-specification',
                'https://example.test/compatible-kit',
            ],
        ])->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('project_inventory_part', [
            'project_id' => $project->id,
            'inventory_part_id' => $part->id,
            'quantity' => 2,
            'unit_price' => 12.50,
            'price_source' => 'selling_price',
            'notes' => 'Use the laptop-compatible memory kit.',
            'added_by' => $user->id,
        ]);
        $this->assertSame(5, $part->fresh()->stock_quantity);
        $this->assertSame(
            ['https://example.test/memory-specification', 'https://example.test/compatible-kit'],
            $project->fresh()->inventoryParts->first()->pivot->url_sources,
        );
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('16GB DDR4 memory')
            ->assertSee('table table-bordered align-middle mb-0', false)
            ->assertSee('2 needed')
            ->assertSee('Use the laptop-compatible memory kit.')
            ->assertSee('https://example.test/memory-specification')
            ->assertSee('https://example.test/compatible-kit')
            ->assertSee('RM 12.50')
            ->assertSee('RM 25.00');

        $this->post(route('projects.estimate-items.store', $project), [
            'category' => 'software',
            'description' => 'IDE licenses',
            'quantity' => 2,
            'unit_price' => 15.50,
        ])->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('project_estimate_items', [
            'project_id' => $project->id,
            'category' => 'software',
            'description' => 'IDE licenses',
            'quantity' => 2,
            'unit_price' => 15.50,
        ]);
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Software &other project costs', false)
            ->assertSee('IDE licenses')
            ->assertSee('RM 31.00')
            ->assertSee('RM 56.00');

        $this->put(route('projects.parts.update', [$project, $part]), [
            'price_source' => 'cost_price',
        ])->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('project_inventory_part', [
            'project_id' => $project->id,
            'inventory_part_id' => $part->id,
            'unit_price' => 9.50,
            'price_source' => 'cost_price',
        ]);
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('RM 50.00');
        $this->put(route('projects.parts.update', [$project, $part]), [
            'price_source' => 'custom',
            'unit_price' => 18.25,
        ])->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseHas('project_inventory_part', [
            'project_id' => $project->id,
            'inventory_part_id' => $part->id,
            'unit_price' => 18.25,
            'price_source' => 'custom',
        ]);
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('RM 67.50');

        $this->delete(route('projects.parts.destroy', [$project, $part]))
            ->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseMissing('project_inventory_part', [
            'project_id' => $project->id,
            'inventory_part_id' => $part->id,
        ]);

        $item = $project->estimateItems()->firstOrFail();
        $this->delete(route('projects.estimate-items.destroy', [$project, $item]))
            ->assertRedirect(route('projects.show', $project));
        $this->assertDatabaseMissing('project_estimate_items', ['id' => $item->id]);
    }

    public function test_part_recommendations_reject_duplicates_and_invalid_quantities(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Workstation setup']);
        $part = InventoryPart::query()->create(['name' => 'USB-C dock', 'stock_quantity' => 2]);
        $project->inventoryParts()->attach($part->id, ['quantity' => 1]);

        $this->actingAs($user)->from(route('projects.show', $project))
            ->post(route('projects.parts.store', $project), [
                'inventory_part_id' => $part->id,
                'quantity' => 0,
            ])
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHasErrors(['inventory_part_id', 'quantity']);
    }

    public function test_web_and_database_structure_examples_are_idempotently_seeded(): void
    {
        $seeder = new SoftwareStructureSopSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertDatabaseHas('flow_charts', [
            'category' => 'website',
            'title' => 'Website Structure & Build SOP',
            'is_example' => true,
        ]);
        $this->assertDatabaseHas('flow_charts', [
            'category' => 'website',
            'title' => 'Website Sitemap Example',
            'subject_name' => 'OMG Computer website',
            'is_example' => true,
        ]);
        $sitemap = FlowChart::query()->where('title', 'Website Sitemap Example')->firstOrFail();
        $this->assertSame([
            'Home',
            'Services',
            'Service detail',
            'Book an appointment',
            'About',
            'Frequently asked questions',
            'Contact',
            'Repair tracking',
        ], array_column($sitemap->steps, 'label'));
        $this->assertDatabaseHas('flow_charts', [
            'category' => 'process',
            'title' => 'Database Design & Migration SOP',
            'is_example' => true,
        ]);
        $this->assertSame(3, FlowChart::query()->whereIn('category', ['website', 'process'])->count());
    }

}
