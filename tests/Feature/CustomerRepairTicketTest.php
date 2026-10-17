<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\Customer;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRepairTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_profile_links_to_a_project_and_manages_repair_tickets(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Student support project']);
        $this->actingAs($user);

        $this->post(route('customers.store'), [
            'name' => 'Avery Tan',
            'email' => 'avery@example.test',
            'phone' => '+60123456789',
            'company' => 'Kampar Campus',
            'project_id' => $project->id,
        ])->assertRedirect();

        $customer = Customer::query()->firstOrFail();
        $this->assertSame($project->id, $customer->project_id);
        $this->getJson(route('customers.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'Avery Tan'],
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertSee('Avery Tan');
        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Avery Tan')
            ->assertSee('Student support project')
            ->assertSee('New repair ticket');
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Avery Tan');

        $this->get(route('tickets.create', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertSee('New repair ticket');
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
        $this->assertSame($project->id, $ticket->project_id);
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
            ->assertSee('Charging port requires replacement.')
            ->assertSee('Waiting for parts');
        $this->get(route('tickets.index', ['status' => 'waiting_parts']))
            ->assertOk()
            ->assertSee('data-datatable', false);
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
            ->assertJsonPath('data.0.priority_label', 'Urgent');

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
            ->assertSee($ticket->ticket_number);
    }
}
