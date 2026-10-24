<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\Customer;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRepairTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_track_a_repair_with_only_the_service_number(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Repair Tracking Customer',
            'email' => 'tracking@example.test',
        ]);
        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-2026-000123',
            'customer_id' => $customer->id,
            'device_type' => 'Laptop',
            'issue_description' => 'Private intake details must not appear in public tracking.',
            'status' => 'waiting_parts',
            'priority' => 'normal',
            'received_at' => now(),
        ]);
        $ticket->updates()->create([
            'title' => 'Device received',
            'status' => 'new',
            'notes' => 'Private intake details must not appear in public tracking.',
            'created_at' => now()->subHours(2),
        ]);
        $ticket->updates()->create([
            'title' => 'Parts ordered',
            'status' => 'waiting_parts',
            'notes' => 'Internal supplier details must not appear in public tracking.',
            'created_at' => now()->subHour(),
        ]);

        $this->get(route('repairs.track.form'))
            ->assertOk()
            ->assertSee('<title>Track a Repair - OMG Computer</title>', false)
            ->assertSee('name="ticket_number"', false)
            ->assertDontSee('name="email"', false);

        $this->post(route('repairs.track'), [
            'ticket_number' => 'rt-2026-000123',
        ])->assertOk()
            ->assertSee('RT-2026-000123')
            ->assertSee('Waiting for parts')
            ->assertSee('Repair timeline')
            ->assertSee('New')
            ->assertSee('Waiting for parts')
            ->assertDontSee('Private intake details')
            ->assertDontSee('Internal supplier details')
            ->assertDontSee('tracking@example.test');

        $this->from(route('repairs.track.form'))
            ->post(route('repairs.track'), [
                'ticket_number' => 'RT-2026-999999',
            ])
            ->assertRedirect(route('repairs.track.form'))
            ->assertSessionHasErrors('ticket_number');

        $this->get('/')
            ->assertOk()
            ->assertSee('Check the latest progress using the service number provided by our shop.')
            ->assertSee(route('repairs.track.form'), false)
            ->assertDontSee('tracking-email');
    }

    public function test_public_repair_tracking_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('repairs.track'), ['ticket_number' => 'RT-UNKNOWN'])
                ->assertRedirect();
        }

        $this->post(route('repairs.track'), ['ticket_number' => 'RT-UNKNOWN'])
            ->assertTooManyRequests();
    }

    public function test_customer_profile_links_to_a_project_and_manages_repair_tickets(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $project = Project::query()->create(['name' => 'Student support project']);
        $this->actingAs($user);

        $this->post(route('customers.store'), [
            'name' => 'Avery Tan',
            'email' => 'avery@example.test',
            'phone' => '+60123456789',
            'company' => 'Kampar Campus',
            'project_id' => $project->id,
            'notes' => 'Prefers contact after 2 PM.',
        ])->assertRedirect();

        $customer = Customer::query()->firstOrFail();
        $this->assertSame($project->id, $customer->project_id);
        $this->getJson(route('customers.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'contact after 2 PM'],
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.notes', fn (string $notes): bool => str_contains($notes, 'Prefers contact after 2 PM.'))
            ->assertSee('Avery Tan');
        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Avery Tan')
            ->assertSee('Prefers contact after 2 PM.')
            ->assertSee('Student support project')
            ->assertSee('New repair ticket');
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Avery Tan');

        $this->get(route('tickets.create', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertSee('New repair ticket')
            ->assertSee('Add new customer…')
            ->assertSee('name="customer_id"', false)
            ->assertSee('required', false)
            ->assertDontSee('id="new_customer_name" maxlength="255" required', false)
            ->assertDontSee('Related project')
            ->assertDontSee('<li class="breadcrumb-item">Work</li>', false);
        $this->post(route('tickets.store'), [
            'customer_id' => $customer->id,
            'assigned_to' => $user->id,
            'device_type' => 'Laptop',
            'device_model' => 'ThinkPad T14',
            'serial_number' => 'SN-1001',
            'issue_description' => 'Does not charge when plugged in.',
            'priority' => 'high',
        ])->assertRedirect();

        $ticket = RepairTicket::query()->firstOrFail();
        $this->assertNull($ticket->project_id);
        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertSame($user->id, $ticket->assigned_to);
        $this->assertDatabaseHas('repair_tickets', ['ticket_number' => $ticket->ticket_number]);

        $this->put(route('tickets.update', $ticket), [
            'status' => 'waiting_parts',
            'priority' => 'urgent',
            'assigned_to' => $user->id,
            'diagnosis' => 'Charging port requires replacement.',
            'due_at' => now()->addDays(3)->toDateString(),
        ])->assertRedirect(route('tickets.show', $ticket));

        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Tickets')
            ->assertSee(route('tickets.index'), false)
            ->assertSee($ticket->ticket_number)
            ->assertSee('Charging port requires replacement.')
            ->assertSee('Waiting for parts')
            ->assertSee('<span class="badge text-bg-warning">Waiting for parts</span>', false);
        $this->get(route('tickets.index', ['status' => 'waiting_parts']))
            ->assertOk()
            ->assertSee('data-datatable', false)
            ->assertSee('ticket-filters-modal')
            ->assertSee('Filter tickets');
        $this->getJson(route('tickets.datatable', [
            'status' => 'waiting_parts',
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => $ticket->ticket_number],
        ]))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.customer_name', 'Avery Tan')
            ->assertJsonPath('data.0.priority_label', 'Urgent')
            ->assertJsonPath('data.0.status_label', '<span class="badge text-bg-warning">Waiting for parts</span>');

        $this->get(route('billing.create', ['type' => 'quotation', 'repair_ticket_id' => $ticket->id]))
            ->assertOk()
            ->assertSee($ticket->ticket_number)
            ->assertSee('Repair service: Does not charge when plugged in.');
        $this->post(route('billing.store'), [
            'type' => 'quotation',
            'repair_ticket_id' => $ticket->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [[
                'product_id' => 'basic-troubleshooting',
                'description' => 'Basic troubleshooting',
                'quantity' => 1,
                'unit_price' => 30,
            ]],
        ])->assertRedirect();
        $quotation = BillingDocument::query()->firstOrFail();
        $this->assertSame($ticket->id, $quotation->repair_ticket_id);
        $this->assertDatabaseHas('billing_documents', [
            'id' => $quotation->id,
            'repair_ticket_id' => $ticket->id,
        ]);

        $this->get(route('tickets.show', $ticket))
            ->assertSee($quotation->number)
            ->assertSee('Billing documents');
        $this->get(route('customers.show', $customer))
            ->assertSee($ticket->ticket_number)
            ->assertSee('<span class="badge text-bg-warning">Waiting for parts</span>', false);
    }

    public function test_customer_records_show_unique_project_count_beside_repair_ticket_count(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $directProject = Project::query()->create(['name' => 'Direct project']);
        $ticketProject = Project::query()->create(['name' => 'Ticket project']);
        $customer = Customer::query()->create([
            'name' => 'Project Count Customer',
            'email' => 'project-count@example.test',
            'project_id' => $directProject->id,
        ]);
        RepairTicket::query()->create([
            'ticket_number' => 'RT-2026-000456',
            'customer_id' => $customer->id,
            'project_id' => $ticketProject->id,
            'device_type' => 'Laptop',
            'issue_description' => 'Project count test.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
        ]);
        RepairTicket::query()->create([
            'ticket_number' => 'RT-2026-000457',
            'customer_id' => $customer->id,
            'project_id' => $ticketProject->id,
            'device_type' => 'Desktop',
            'issue_description' => 'Second ticket for the same project.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now()->subMinute(),
        ]);
        RepairTicket::query()->create([
            'ticket_number' => 'RT-2026-000459',
            'customer_id' => $customer->id,
            'project_id' => $directProject->id,
            'device_type' => 'Tablet',
            'issue_description' => 'Ticket linked to the profile project.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now()->subMinutes(2),
        ]);
        $unrelatedProject = Project::query()->create(['name' => 'Unrelated project']);
        $otherCustomer = Customer::query()->create(['name' => 'Different customer']);
        RepairTicket::query()->create([
            'ticket_number' => 'RT-2026-000458',
            'customer_id' => $otherCustomer->id,
            'project_id' => $unrelatedProject->id,
            'device_type' => 'Laptop',
            'issue_description' => 'Must not appear on the first customer.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('<th>Repair tickets</th><th>Projects</th>', false)
            ->assertDontSee('<th>Project</th>', false);

        $this->getJson(route('customers.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'project-count@example.test'],
        ]))->assertOk()
            ->assertJsonPath('data.0.repair_tickets_count', 3)
            ->assertJsonPath('data.0.projects_count', 2)
            ->assertJsonMissingPath('data.0.project_name');

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Projects')
            ->assertSee('Direct project')
            ->assertSee('Ticket project')
            ->assertSee('RT-2026-000456')
            ->assertSee('RT-2026-000457')
            ->assertSee('RT-2026-000459')
            ->assertDontSee('Unrelated project')
            ->assertDontSee('RT-2026-000458');
    }

    public function test_staff_can_create_a_repair_ticket_with_a_new_customer_without_a_project(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $this->actingAs($staff);

        $this->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('Add new customer…')
            ->assertDontSee('Related project');

        $customerResponse = $this->postJson(route('tickets.customers.store'), [
            'name' => 'Morgan New Customer',
            'email' => 'morgan@example.test',
            'phone' => '+60123456789',
            'company' => 'New Company',
        ])->assertCreated()
            ->assertJsonPath('text', 'Morgan New Customer · New Company');

        $customerId = $customerResponse->json('id');
        $this->get(route('tickets.create', ['customer_id' => $customerId]))
            ->assertOk()
            ->assertSee('value="'.$customerId.'" selected', false);

        $this->post(route('tickets.store'), [
            'customer_id' => $customerId,
            'device_type' => 'Laptop',
            'issue_description' => 'The laptop does not start.',
            'priority' => 'normal',
        ])->assertSessionHasNoErrors();

        $ticket = RepairTicket::query()->with('customer')->firstOrFail();
        $this->assertSame((int) $customerId, $ticket->customer_id);
        $this->assertSame('Morgan New Customer', $ticket->customer->name);
        $this->assertSame('morgan@example.test', $ticket->customer->email);
        $this->assertNull($ticket->project_id);
    }

    public function test_customer_profile_shows_matching_customer_login_account(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('member');
        $this->actingAs($staff);
        $customer = Customer::query()->create([
            'name' => 'Portal Customer',
            'email' => 'Portal.Customer@example.test',
        ]);
        $account = User::factory()->create([
            'name' => 'Portal Customer Account',
            'email' => 'portal.customer@example.test',
            'email_verified_at' => now(),
        ]);
        $account->assignRole('customer');

        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Customer login account')
            ->assertSee('Portal Customer Account')
            ->assertSee('portal.customer@example.test')
            ->assertSee('Verified')
            ->assertDontSee('Manage account');

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Manage account')
            ->assertSee(route('admin.users.edit', $account), false);
    }
}
