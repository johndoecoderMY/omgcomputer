<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class RepairTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_repair_support_desk(): void
    {
        $this->get(route('tickets.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_can_add_pickup_and_dropoff_addresses_to_a_repair_ticket(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Pickup Customer',
            'email' => 'pickup-ticket@example.test',
        ]);

        $this->actingAs($staff)->get(route('tickets.create', ['customer_id' => $customer->id]))
            ->assertOk()
            ->assertSee('data-ticket-create-ajax', false)
            ->assertSee('data-ticket-create-error', false)
            ->assertSee('id="pickup_address"', false)
            ->assertSee('id="dropoff_address"', false)
            ->assertSee('data-ticket-location-open="pickup"', false)
            ->assertSee('data-ticket-location-open="dropoff"', false)
            ->assertSee('id="ticket-location-picker-modal"', false)
            ->assertSee('name="pickup_latitude"', false)
            ->assertSee('name="dropoff_longitude"', false);

        $this->post(route('tickets.store'), [
            'customer_id' => $customer->id,
            'device_type' => 'Laptop',
            'issue_description' => 'The laptop needs repair.',
            'priority' => 'normal',
            'pickup_address' => '12 Jalan Repair, Kampar',
            'dropoff_address' => '18 Jalan Repair, Kampar',
            'pickup_latitude' => '4.326',
            'pickup_longitude' => '101.150',
            'dropoff_latitude' => '4.327',
            'dropoff_longitude' => '101.151',
        ])->assertSessionHasNoErrors();

        $ticket = RepairTicket::query()->firstOrFail();
        $this->assertSame('12 Jalan Repair, Kampar', $ticket->pickup_address);
        $this->assertSame('18 Jalan Repair, Kampar', $ticket->dropoff_address);
        $this->assertSame(4.326, (float) $ticket->pickup_latitude);
        $this->assertSame(101.151, (float) $ticket->dropoff_longitude);
        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Pickup and drop-off')
            ->assertSee('12 Jalan Repair, Kampar')
            ->assertSee('18 Jalan Repair, Kampar')
            ->assertSee('View pickup pin')
            ->assertSee('View drop-off pin');
    }

    public function test_staff_can_create_a_face_to_face_repair_ticket_without_a_customer_account(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');

        $this->actingAs($staff)
            ->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('Face-to-face / walk-in customer')
            ->assertSee('name="face_to_face"', false)
            ->assertSee('data-ticket-face-to-face-message', false)
            ->assertSee('data-ticket-face-to-face-address', false);

        $this->actingAs($staff)->post(route('tickets.store'), [
            'face_to_face' => '1',
            'device_type' => 'Desktop PC',
            'issue_description' => 'Walk-in customer needs a desktop check.',
            'priority' => 'normal',
            'face_to_face_name' => 'Walk-in Customer',
            'face_to_face_nickname' => 'Walkie',
            'face_to_face_phone' => '+60123456789',
            'pickup_address' => 'Should not be stored',
            'dropoff_address' => 'Should not be stored',
        ])->assertSessionHasNoErrors();

        $ticket = RepairTicket::query()->firstOrFail();
        $this->assertNull($ticket->customer_id);
        $this->assertSame('Walk-in Customer', $ticket->face_to_face_name);
        $this->assertSame('Walkie', $ticket->face_to_face_nickname);
        $this->assertSame('+60123456789', $ticket->face_to_face_phone);
        $this->assertNull($ticket->pickup_address);
        $this->assertNull($ticket->dropoff_address);
        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Walk-in Customer')
            ->assertSee('+60123456789');

        $this->actingAs($staff)->post(route('tickets.customer.store', $ticket), [
            'name' => 'Walk-in Customer',
            'nickname' => 'Walkie',
            'phone' => '+60123456789',
            'email' => 'walkin@example.test',
        ])->assertRedirect(route('tickets.show', $ticket));

        $ticket->refresh();
        $this->assertNotNull($ticket->customer_id);
        $this->assertNull($ticket->face_to_face_phone);
        $this->assertNull($ticket->face_to_face_email);
        $this->assertDatabaseHas('customers', [
            'id' => $ticket->customer_id,
            'name' => 'Walk-in Customer',
            'nickname' => 'Walkie',
            'email' => 'walkin@example.test',
        ]);
    }

    public function test_face_to_face_ticket_requires_a_phone_or_email(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');

        $this->actingAs($staff)->post(route('tickets.store'), [
            'face_to_face' => '1',
            'device_type' => 'Laptop',
            'issue_description' => 'Walk-in customer did not provide contact details.',
            'priority' => 'normal',
        ])
            ->assertSessionHasErrors(['face_to_face_contact']);

        $this->actingAs($staff)->post(route('tickets.store'), [
            'face_to_face' => '1',
            'face_to_face_email' => 'walkin@example.test',
            'device_type' => 'Laptop',
            'issue_description' => 'Walk-in customer provided an email.',
            'priority' => 'normal',
        ])
            ->assertSessionHasNoErrors();

        $ticket = RepairTicket::query()->latest('id')->firstOrFail();
        $this->assertSame('walkin@example.test', $ticket->face_to_face_email);
    }

    public function test_staff_can_create_a_repair_ticket_with_ajax(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'AJAX Ticket Customer',
            'email' => 'ajax-ticket@example.test',
        ]);

        $response = $this->actingAs($staff)->postJson(route('tickets.store'), [
            'customer_id' => $customer->id,
            'device_type' => 'Laptop',
            'issue_description' => 'The laptop will not charge.',
            'priority' => 'normal',
        ])->assertCreated()
            ->assertJsonPath('message', 'Repair ticket created.');

        $ticket = RepairTicket::query()->firstOrFail();
        $response->assertJsonPath('redirect_url', route('tickets.show', $ticket));
        $this->assertSame($customer->id, $ticket->customer_id);
        $this->assertSame('The laptop will not charge.', $ticket->issue_description);
    }

    public function test_ajax_ticket_creation_returns_validation_errors_as_json(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');

        $this->actingAs($staff)->postJson(route('tickets.store'), [
            'issue_description' => 'A reported issue.',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['customer_id', 'priority']);
    }

    public function test_user_can_create_and_manage_a_repair_ticket(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Jamie Customer',
            'email' => 'jamie@example.test',
            'phone' => '0123456789',
        ]);

        $this->actingAs($user)->post(route('tickets.store'), [
            'customer_id' => $customer->id,
            'device_type' => 'Laptop',
            'device_model' => 'ThinkPad T14',
            'serial_number' => 'SERIAL-123',
            'issue_description' => 'Laptop overheats and shuts down after ten minutes.',
            'priority' => 'high',
            'assigned_to' => $user->id,
            'due_at' => now()->addDays(3)->toDateString(),
        ])->assertSessionHasNoErrors();

        $ticket = RepairTicket::query()->firstOrFail();
        $this->assertSame('RT-'.now()->format('Y').'-'.str_pad((string) $ticket->id, 6, '0', STR_PAD_LEFT), $ticket->ticket_number);
        $this->assertSame('new', $ticket->status);
        $this->assertSame($customer->id, $ticket->customer_id);

        $this->actingAs($user)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Jamie Customer')
            ->assertSee('ThinkPad T14')
            ->assertSee('Laptop overheats and shuts down');

        $this->actingAs($user)->put(route('tickets.update', $ticket), [
            'status' => 'waiting_parts',
            'priority' => 'urgent',
            'assigned_to' => $user->id,
            'diagnosis' => 'Needs a replacement cooling fan.',
            'due_at' => now()->addDays(5)->toDateString(),
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertSame('waiting_parts', $ticket->fresh()->status);
        $this->assertSame('urgent', $ticket->fresh()->priority);
        $this->assertSame('Needs a replacement cooling fan.', $ticket->fresh()->diagnosis);
        $this->assertSame(now()->addDays(5)->toDateString(), $ticket->fresh()->due_at->toDateString());
    }

    public function test_new_ticket_reported_issue_accepts_safe_rich_text(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Rich text customer',
            'email' => 'rich-text@example.test',
        ]);

        $this->actingAs($user)->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('contenteditable="true"', false)
            ->assertSee('data-issue-command="insertUnorderedList"', false);

        $this->actingAs($user)->post(route('tickets.store'), [
            'customer_id' => $customer->id,
            'issue_description' => '<p>The screen flickers. <strong onclick="alert(1)">After update</strong>.</p><script>alert(1)</script><img src="x" onerror="alert(1)">',
            'priority' => 'normal',
        ])->assertSessionHasNoErrors();

        $ticket = RepairTicket::query()->firstOrFail();
        $this->assertSame(
            '<p>The screen flickers. <strong>After update</strong>.</p>',
            $ticket->issue_description,
        );
        $this->assertNull($ticket->updates()->firstOrFail()->notes);

        $this->actingAs($user)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('<strong>After update</strong>', false)
            ->assertDontSee('onclick', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('onerror="alert(1)"', false);

        $this->actingAs($user)->get(route('billing.create', ['repair_ticket_id' => $ticket->id]))
            ->assertOk()
            ->assertSee('Repair service: The screen flickers. After update.')
            ->assertDontSee('<strong>', false);

        $this->actingAs($user)->post(route('tickets.store'), [
            'customer_id' => $customer->id,
            'issue_description' => '<p>'.str_repeat('a', 5001).'</p>',
            'priority' => 'normal',
        ])->assertSessionHasErrors('issue_description');

        $this->assertSame(1, RepairTicket::query()->count());
    }

    public function test_ticket_list_filters_by_status_assignment_and_priority(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Ticket filter customer',
            'email' => 'ticket-filter@example.test',
        ]);
        $assigned = RepairTicket::query()->create($this->ticketAttributes($user, [
            'ticket_number' => 'OMG-2026-000001',
            'customer_id' => $customer->id,
            'status' => 'waiting_parts',
            'priority' => 'urgent',
            'assigned_to' => $user->id,
        ]));
        RepairTicket::query()->create($this->ticketAttributes($user, [
            'ticket_number' => 'OMG-2026-000002',
            'customer_id' => $customer->id,
            'status' => 'closed',
            'priority' => 'low',
        ]));

        $filtered = $this->actingAs($user)->getJson(route('tickets.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'filter' => [
                'status' => 'waiting_parts',
                'priority' => 'urgent',
            ],
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.ticket', fn (string $ticket): bool => str_contains($ticket, $assigned->ticket_number));
        $this->assertStringNotContainsString('OMG-2026-000002', $filtered->getContent());

        $unassigned = $this->actingAs($user)->getJson(route('tickets.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'assigned' => 'unassigned',
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.ticket', fn (string $ticket): bool => str_contains($ticket, 'OMG-2026-000002'));
        $this->assertStringNotContainsString($assigned->ticket_number, $unassigned->getContent());
    }

    public function test_repair_ticket_details_only_show_a_project_when_one_is_linked(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Project link customer',
            'email' => 'project-link@example.test',
        ]);
        $unlinkedTicket = RepairTicket::query()->create($this->ticketAttributes($user, [
            'ticket_number' => 'OMG-2026-000003',
            'customer_id' => $customer->id,
        ]));

        $this->actingAs($user)->get(route('tickets.show', $unlinkedTicket))
            ->assertOk()
            ->assertDontSee('<div class="small text-body-secondary">Project</div>', false);

        $project = Project::query()->create(['name' => 'Hardware refresh']);
        $linkedTicket = RepairTicket::query()->create($this->ticketAttributes($user, [
            'ticket_number' => 'OMG-2026-000004',
            'customer_id' => $customer->id,
            'project_id' => $project->id,
        ]));

        $this->get(route('tickets.show', $linkedTicket))
            ->assertOk()
            ->assertSee('<div class="small text-body-secondary">Project</div>', false)
            ->assertSee('Hardware refresh');
    }

    public function test_repair_tickets_can_be_assigned_to_multiple_people(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $owner = User::factory()->create();
        $owner->assignRole('admin');
        $assignees = User::factory()->count(2)->create();
        $assignees->each(fn (User $assignee) => $assignee->assignRole('member'));
        $customer = Customer::query()->create([
            'name' => 'Multiple-assignee customer',
            'email' => 'multi@example.test',
        ]);

        $this->actingAs($owner)->post(route('tickets.store'), [
            'customer_id' => $customer->id,
            'issue_description' => 'The device will not start.',
            'priority' => 'normal',
            'assignees_present' => '1',
            'assignees' => $assignees->pluck('id')->all(),
        ])->assertSessionHasNoErrors();

        $ticket = RepairTicket::query()->firstOrFail();
        $this->assertEqualsCanonicalizing(
            $assignees->pluck('id')->all(),
            $ticket->assignees()->pluck('users.id')->all(),
        );
        $this->assertSame($assignees->first()->id, $ticket->assigned_to);

        $replacement = User::factory()->create();
        $replacement->assignRole('member');
        $this->actingAs($owner)->put(route('tickets.update', $ticket), [
            'status' => 'diagnosing',
            'priority' => 'high',
            'assignees_present' => '1',
            'assignees' => [$replacement->id],
        ])->assertSessionHasNoErrors();

        $this->assertEquals([$replacement->id], $ticket->fresh()->assignees()->pluck('users.id')->all());
    }

    public function test_ticket_photos_and_customer_history_are_saved_and_accessible(): void
    {
        Storage::fake('local');
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Taylor Customer',
            'email' => 'taylor@example.test',
            'phone' => '0123456789',
        ]);
        $payload = [
            'customer_id' => $customer->id,
            'device_type' => 'Laptop',
            'issue_description' => 'The screen flickers on startup.',
            'priority' => 'normal',
        ];

        $this->actingAs($user)->post(route('tickets.store'), $payload)->assertSessionHasNoErrors();
        $firstTicket = RepairTicket::query()->firstOrFail();
        $stepResponse = $this->actingAs($user)->postJson(route('tickets.steps.store', $firstTicket), [
            'title' => 'Inspect device condition',
            'status' => 'diagnosing',
            'customer_details' => 'We are checking the screen issue.',
        ])->assertOk();
        $this->assertStringContainsString('Inspect device condition', $stepResponse->json('updates_html'));
        $step = $firstTicket->updates()->latest('id')->firstOrFail();

        $photoResponse = $this->postJson(route('tickets.steps.attachments.store', [$firstTicket, $step]), [
            'photos' => [UploadedFile::fake()->image('device.png')],
            'captions' => ['Screen inspection'],
        ])->assertOk()
            ->assertJsonPath('message', 'Photo added to repair step.');
        $this->assertStringContainsString('Screen inspection', $photoResponse->json('updates_html'));
        $photo = $step->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($photo->path);
        $this->get(route('tickets.show', $firstTicket))
            ->assertOk()
            ->assertSee('Screen inspection');
        $this->get(URL::temporarySignedRoute(
            'repairs.track.photo',
            now()->addMinutes(10),
            ['attachment' => $photo],
        ))->assertOk();

        $this->actingAs($user)->post(route('tickets.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, $customer->repairTickets()->count());
        $this->actingAs($user)->get(route('customers.index'))
            ->assertOk()
            ->assertSee('data-datatable', false);
        $this->actingAs($user)->getJson(route('customers.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'Taylor Customer'],
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.customer', fn (string $customer): bool => str_contains($customer, 'Taylor Customer'));
        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Repair history')
            ->assertSee($firstTicket->ticket_number);
    }

    private function ticketAttributes(User $user, array $overrides = []): array
    {
        return array_merge([
            'created_by_user_id' => $user->id,
            'device_type' => 'Desktop PC',
            'issue_description' => 'The PC turns on but has no display.',
            'status' => 'received',
            'priority' => 'normal',
            'received_at' => now(),
        ], $overrides);
    }
}
