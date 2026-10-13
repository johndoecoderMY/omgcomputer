<?php

namespace Tests\Feature;

use App\Models\RepairTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_repair_support_desk(): void
    {
        $this->get(route('tickets.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_create_and_manage_a_repair_ticket(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('tickets.store'), [
            'customer_name' => 'Jamie Customer',
            'customer_email' => 'jamie@example.test',
            'customer_phone' => '0123456789',
            'device_type' => 'Laptop',
            'device_model' => 'ThinkPad T14',
            'serial_number' => 'SERIAL-123',
            'subject' => 'Laptop overheats and shuts down',
            'issue_description' => 'The laptop powers off after ten minutes.',
            'priority' => 'high',
            'assigned_to' => $user->id,
            'due_date' => now()->addDays(3)->toDateString(),
        ])->assertSessionHasNoErrors();

        $ticket = RepairTicket::query()->firstOrFail();
        $this->assertSame('OMG-'.now()->format('Y').'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT), $ticket->ticket_number);
        $this->assertSame('received', $ticket->status);

        $this->actingAs($user)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Jamie Customer')
            ->assertSee('ThinkPad T14')
            ->assertSee('Laptop overheats and shuts down');

        $this->actingAs($user)->patch(route('tickets.update', $ticket), [
            'status' => 'waiting_parts',
            'priority' => 'urgent',
            'assigned_to' => $user->id,
            'diagnostic_notes' => 'Needs a replacement cooling fan.',
            'quoted_amount' => 120,
            'due_date' => now()->addDays(5)->toDateString(),
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame('waiting_parts', $ticket->fresh()->status);
        $this->assertSame('urgent', $ticket->fresh()->priority);
        $this->assertSame('Needs a replacement cooling fan.', $ticket->fresh()->diagnostic_notes);
        $this->assertSame(120.0, (float) $ticket->fresh()->quoted_amount);
    }

    public function test_ticket_list_filters_by_status_assignment_and_priority(): void
    {
        $user = User::factory()->create();
        $assigned = RepairTicket::query()->create($this->ticketAttributes($user, [
            'ticket_number' => 'OMG-2026-000001',
            'status' => 'waiting_parts',
            'priority' => 'urgent',
            'assigned_to' => $user->id,
        ]));
        RepairTicket::query()->create($this->ticketAttributes($user, [
            'ticket_number' => 'OMG-2026-000002',
            'status' => 'closed',
            'priority' => 'low',
        ]));

        $this->actingAs($user)->get(route('tickets.index', ['status' => 'waiting_parts', 'priority' => 'urgent']))
            ->assertOk()
            ->assertSee($assigned->ticket_number)
            ->assertDontSee('OMG-2026-000002');

        $this->actingAs($user)->get(route('tickets.index', ['assigned' => 'unassigned']))
            ->assertOk()
            ->assertDontSee($assigned->ticket_number)
            ->assertSee('OMG-2026-000002');
    }

    private function ticketAttributes(User $user, array $overrides = []): array
    {
        return array_merge([
            'created_by' => $user->id,
            'customer_name' => 'Test Customer',
            'device_type' => 'Desktop PC',
            'subject' => 'No display',
            'issue_description' => 'The PC turns on but has no display.',
            'status' => 'received',
            'priority' => 'normal',
        ], $overrides);
    }
}
