<?php

namespace Tests\Feature;

use App\Models\Customer;
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
        $linkedProject = Project::create(['name' => 'Directly linked project', 'description' => 'Customer-facing summary']);
        $ticketProject = Project::create(['name' => 'Ticket-linked project']);
        $hiddenProject = Project::create(['name' => 'Another customer project']);

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
        $this->get(route('dashboard'))->assertRedirect(route('projects.index'));

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

        $this->get(route('projects.show', $linkedProject))
            ->assertOk()
            ->assertSee('Customer-facing summary')
            ->assertDontSee('Estimated total')
            ->assertDontSee('Edit project');
        $this->get(route('projects.show', $ticketProject))
            ->assertOk()
            ->assertSee('RT-2026-000001')
            ->assertSee('In repair')
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
}
