<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Event;
use App\Models\FlowChart;
use App\Models\InventoryPart;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProjectAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_only_sees_projects_linked_to_their_profile_or_their_repair_tickets(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create(['email' => 'client@example.test']);
        $user->assignRole('customer');
        $linkedProject = Project::create([
            'name' => 'Directly linked project',
            'description' => 'Customer-facing summary',
            'requirements_notes' => 'Customer-approved project scope.',
        ]);
        $ticketProject = Project::create(['name' => 'Ticket-linked project']);
        $hiddenProject = Project::create(['name' => 'Another customer project']);

        $linkedProject->requirements()->create([
            'requirement' => 'Complete the customer portal',
            'is_completed' => true,
        ]);
        Event::create([
            'project_id' => $linkedProject->id,
            'title' => 'Customer design review',
            'event_type' => 'progress_review',
            'starts_at' => now()->addDays(5),
            'location' => 'Online meeting',
        ]);
        $flowChart = FlowChart::create([
            'title' => 'Customer onboarding',
            'category' => 'process',
            'description' => 'Steps for customer onboarding.',
            'steps' => [
                ['id' => 'start', 'label' => 'Create account', 'kind' => 'start', 'connections' => ['finish']],
                ['id' => 'finish', 'label' => 'Review dashboard', 'kind' => 'end', 'connections' => []],
            ],
        ]);
        $linkedProject->flowCharts()->attach($flowChart);
        $linkedProject->technologyStack()->create([
            'name' => 'Laravel',
            'version' => '12',
            'notes' => 'Application framework',
        ]);
        $part = InventoryPart::create([
            'name' => 'Customer SSD',
            'sku' => 'INTERNAL-SSD-001',
            'description' => '1 TB solid-state drive',
            'cost_price' => 21.50,
            'selling_price' => 35.00,
            'stock_quantity' => 88,
        ]);
        $linkedProject->inventoryParts()->attach($part->id, [
            'quantity' => 1,
            'unit_price' => 21.50,
            'price_source' => 'cost_price',
        ]);
        $linkedProject->estimateItems()->create([
            'category' => 'software',
            'description' => 'Design software license',
            'quantity' => 2,
            'unit_price' => 25,
        ]);
        $assignedUser = User::factory()->create(['name' => 'Project specialist']);
        $linkedProject->assignees()->attach($assignedUser->id);
        $task = $linkedProject->tasks()->create([
            'title' => 'Build customer portal',
            'description' => 'Implement the customer-facing project area.',
            'status' => 'in_progress',
            'priority' => 'high',
            'start_date' => today()->addDay(),
            'due_date' => today()->addDays(7),
        ]);
        $task->assignees()->attach($assignedUser->id);
        $task->subtasks()->create([
            'project_id' => $linkedProject->id,
            'title' => 'Review dashboard layout',
            'status' => 'backlog',
            'priority' => 'medium',
            'start_date' => today()->addDays(2),
            'due_date' => today()->addDays(4),
        ]);

        Customer::create([
            'project_id' => $linkedProject->id,
            'name' => 'Client',
            'email' => 'CLIENT@example.test',
        ]);
        $ticketCustomer = Customer::create([
            'name' => 'Client',
            'email' => 'client@example.test',
        ]);
        RepairTicket::create([
            'ticket_number' => 'RT-2026-000001',
            'customer_id' => $ticketCustomer->id,
            'project_id' => $ticketProject->id,
            'device_type' => 'Laptop',
            'device_model' => 'Model X',
            'issue_description' => 'Customer issue details',
            'status' => 'in_repair',
            'priority' => 'normal',
            'received_at' => now(),
        ]);
        Booking::create([
            'reference' => 'BK-CUSTOMER-DASH',
            'customer_name' => 'Client',
            'customer_email' => 'CLIENT@example.test',
            'customer_phone' => '0123456789',
            'service_id' => 'repair',
            'service_name' => 'Laptop diagnosis',
            'starts_at' => now()->addDays(2),
            'status' => 'confirmed',
        ]);
        Booking::create([
            'reference' => 'BK-OTHER-DASH',
            'customer_name' => 'Other Client',
            'customer_email' => 'other@example.test',
            'customer_phone' => '0198765432',
            'service_id' => 'repair',
            'service_name' => 'Private appointment',
            'starts_at' => now()->addDays(3),
            'status' => 'confirmed',
        ]);
        Customer::create([
            'project_id' => $hiddenProject->id,
            'name' => 'Other Client',
            'email' => 'other@example.test',
        ]);

        $this->actingAs($user)
            ->get(route('projects.index'))
            ->assertOk()
            ->assertSee('My projects')
            ->assertDontSee('New project')
            ->assertDontSee('Repair Tickets');
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome, '.e($user->name), false)
            ->assertSee('Directly linked project')
            ->assertSee('Ticket-linked project')
            ->assertDontSee('Another customer project')
            ->assertSee('RT-2026-000001')
            ->assertSee('Laptop diagnosis')
            ->assertDontSee('Private appointment')
            ->assertSee('Contact us')
            ->assertSee(route('contact'), false)
            ->assertSee('Upcoming appointments');

        $response = $this->getJson(route('projects.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk();
        $response->assertSee('Directly linked project')
            ->assertSee('Ticket-linked project')
            ->assertDontSee('Another customer project')
            ->assertDontSee('Edit');
        foreach ($response->json('data') as $projectRow) {
            $this->assertArrayNotHasKey('tasks_count', $projectRow);
            $this->assertArrayNotHasKey('progress', $projectRow);
        }

        $projectResponse = $this->get(route('projects.show', $linkedProject))
            ->assertOk()
            ->assertSee('Customer-facing summary')
            ->assertDontSee('Estimated total')
            ->assertDontSee('Edit project')
            ->assertSee('Customer-approved project scope.')
            ->assertSee('Complete the customer portal')
            ->assertSee('Customer design review')
            ->assertSee('Online meeting')
            ->assertSee('Customer onboarding')
            ->assertSee('View flow chart')
            ->assertSee('data-customer-flowchart-modal', false)
            ->assertSee('data-node-id="start"', false)
            ->assertSee('customer-flowchart-arrow-'.$flowChart->id)
            ->assertSee('Create account')
            ->assertSee('Review dashboard')
            ->assertSee('Laravel')
            ->assertSee('Application framework')
            ->assertSee('Customer SSD')
            ->assertSee('1 unit recommended')
            ->assertSee('Design software license')
            ->assertSee('RM 50.00')
            ->assertSee('Project specialist')
            ->assertSee('Task Gantt chart')
            ->assertSee('Build customer portal')
            ->assertSee('Review dashboard layout')
            ->assertDontSee('INTERNAL-SSD-001')
            ->assertDontSee('88 in stock')
            ->assertDontSee('RM 21.50');

        $this->get(route('projects.show', $ticketProject))
            ->assertOk()
            ->assertDontSee('Your repair tickets for this project')
            ->assertDontSee('RT-2026-000001')
            ->assertDontSee('Customer issue details');
        $this->get(route('projects.show', $hiddenProject))->assertNotFound();
    }

    public function test_customer_cannot_open_project_management_pages_or_modify_projects(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create(['email' => 'client@example.test']);
        $user->assignRole('customer');
        $project = Project::create(['name' => 'Customer project']);
        Customer::create([
            'project_id' => $project->id,
            'name' => 'Client',
            'email' => 'client@example.test',
        ]);

        $this->actingAs($user)
            ->get(route('projects.create'))
            ->assertForbidden();
        $this->actingAs($user)
            ->get(route('projects.edit', $project))
            ->assertForbidden();
        $this->actingAs($user)
            ->post(route('projects.events.store', $project), [
                'title' => 'Unauthorized event',
                'event_type' => 'other',
                'starts_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertForbidden();
    }

    public function test_project_can_link_a_customer_account_without_duplicating_matching_customer_records(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $account = User::factory()->create([
            'name' => 'Account Customer',
            'email' => 'account-customer@example.test',
        ]);
        $account->assignRole('customer');
        $project = Project::create(['name' => 'Account-linked project']);
        $firstRecord = Customer::create([
            'project_id' => $project->id,
            'name' => 'Existing customer profile',
            'email' => 'ACCOUNT-CUSTOMER@example.test',
        ]);
        $duplicateRecord = Customer::create([
            'project_id' => $project->id,
            'name' => 'Duplicate customer profile',
            'email' => 'account-customer@example.test',
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('projects.edit', $project))
            ->assertOk()
            ->assertSee('value="account:'.$account->id.'"', false)
            ->assertDontSee('value="record:'.$firstRecord->id.'"', false)
            ->assertDontSee('value="record:'.$duplicateRecord->id.'"', false);

        $this->put(route('projects.update', $project), [
            'name' => $project->name,
            'customer_identity' => 'account:'.$account->id,
        ])->assertRedirect(route('projects.show', $project));

        $this->assertSame($account->id, $project->fresh()->customer_user_id);
        $this->assertSame(0, Customer::query()
            ->where('project_id', $project->id)
            ->whereRaw('LOWER(TRIM(email)) = ?', ['account-customer@example.test'])
            ->count());
        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('account-customer@example.test')
            ->assertSee('class="badge text-bg-light border mt-1">Customer account</span>', false);

        $accountOnlyProject = Project::create([
            'name' => 'Account-only project',
            'customer_user_id' => $account->id,
        ]);
        $this->get(route('projects.show', $accountOnlyProject))
            ->assertOk()
            ->assertSee('Account Customer')
            ->assertSee('class="badge text-bg-light border mt-1">Customer account</span>', false);

        $this->actingAs($account)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Account-linked project')
            ->assertSee('Account-only project');
    }

    public function test_staff_can_show_or_hide_project_schedule_events_from_customers(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $customer = User::factory()->create(['email' => 'schedule-customer@example.test']);
        $customer->assignRole('customer');
        $project = Project::create([
            'name' => 'Customer schedule project',
            'customer_user_id' => $customer->id,
        ]);
        $event = Event::create([
            'project_id' => $project->id,
            'title' => 'Customer-visible review',
            'event_type' => 'progress_review',
            'starts_at' => now()->addDays(5),
            'visible_to_customer' => true,
        ]);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($customer)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Customer-visible review');
        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Hide from customer');
        $this->patch(route('projects.events.visibility', [$project, $event]), [
            'visible_to_customer' => false,
        ])->assertRedirect(route('projects.show', $project));

        $this->actingAs($customer)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertDontSee('Customer-visible review');
        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Show to customer');
        $this->patch(route('projects.events.visibility', [$project, $event]), [
            'visible_to_customer' => true,
        ])->assertRedirect(route('projects.show', $project));
        $this->actingAs($customer)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Customer-visible review');

        $this->actingAs($customer)
            ->patch(route('projects.events.visibility', [$project, $event]), [
                'visible_to_customer' => false,
            ])
            ->assertForbidden();
    }
}
