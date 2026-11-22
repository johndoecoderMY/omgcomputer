<?php

namespace Tests\Feature;

use App\Mail\RepairBillingVerificationMail;
use App\Models\BillingDocument;
use App\Models\Customer;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerRepairTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_update_ticket_workflow_and_repair_steps_with_ajax(): void
    {
        Storage::fake('local');
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'AJAX Ticket Customer',
            'email' => 'ajax-ticket@example.test',
        ]);
        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-AJAX-001',
            'customer_id' => $customer->id,
            'issue_description' => 'The laptop does not charge.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
        ]);

        $ticketPage = $this->actingAs($staff)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('data-bs-target="#ticket-workflow-modal"', false)
            ->assertSee('data-bs-target="#ticket-step-modal"', false)
            ->assertSee('value="picking_up"', false)
            ->assertSee('>Picking up</option>', false)
            ->assertSee('value="dropping_off"', false)
            ->assertSee('>Dropping off</option>', false)
            ->assertSee('value="ready_to_drop_off"', false)
            ->assertSee('>Ready to drop off</option>', false)
            ->assertSee('id="ticket-workflow-modal"', false)
            ->assertSee('id="ticket-step-modal"', false)
            ->assertSee('id="workflow-device-model"', false)
            ->assertSee('id="workflow-serial-number"', false)
            ->assertSee('id="workflow-pickup-address"', false)
            ->assertSee('id="workflow-dropoff-address"', false)
            ->assertSee('data-ticket-location-open="pickup"', false)
            ->assertSee('data-ticket-location-open="dropoff"', false)
            ->assertSee('id="step-customer-details"', false)
            ->assertSee('Create quotation')
            ->assertSee('data-project-ajax-modal', false)
            ->assertSee('href="'.e(route('billing.create', [
                'type' => 'quotation',
                'repair_ticket_id' => $ticket->id,
                'ticket_modal' => 1,
            ])).'"', false);

        $previousLibxmlErrorSetting = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $loadedDocument = $document->loadHTML($ticketPage->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlErrorSetting);
        $this->assertTrue($loadedDocument);
        $xpath = new \DOMXPath($document);
        $this->assertSame(
            1,
            $xpath->query('//*[@data-page-header]//button[@aria-label="Page actions"]')->length,
        );
        $this->assertSame(1, $xpath->query('//button[@data-bs-target="#ticket-workflow-modal"]')->length);
        $this->assertSame(2, $xpath->query('//button[@data-bs-target="#ticket-step-modal"]')->length);
        $this->assertSame(
            1,
            $xpath->query('//*[@id="ticket-updates-display"]/preceding-sibling::div[contains(@class, "card-header")]//button[@data-bs-target="#ticket-step-modal"]')->length,
        );
        $this->assertSame(
            1,
            $xpath->query('//*[@data-page-header]//a[normalize-space(.)="All tickets"]')->length,
        );
        $this->assertSame(
            1,
            $xpath->query('//*[@data-ticket-page]//form[@id="ticket-step-form"]')->length,
        );
        $this->assertSame(
            1,
            $xpath->query(
                '//*[@id="ticket-step-modal"]/div[contains(concat(" ", normalize-space(@class), " "), " modal-dialog-scrollable ")]/form[@id="ticket-step-form" and contains(concat(" ", normalize-space(@class), " "), " modal-content ")]/div[contains(concat(" ", normalize-space(@class), " "), " modal-body ")]',
            )->length,
        );

        $workflowResponse = $this->actingAs($staff)->putJson(route('tickets.update', $ticket), [
            'status' => 'in_repair',
            'priority' => 'high',
            'assigned_to' => $staff->id,
            'diagnosis' => 'Charging port requires replacement.',
            'due_at' => now()->addDays(3)->toDateString(),
            'device_model' => 'ThinkPad T14',
            'serial_number' => 'SN-1001',
            'pickup_address' => '12 Jalan Repair, Kampar',
            'dropoff_address' => '18 Jalan Repair, Kampar',
            'pickup_latitude' => '4.326',
            'pickup_longitude' => '101.150',
            'dropoff_latitude' => '4.327',
            'dropoff_longitude' => '101.151',
        ])->assertOk()
            ->assertJsonPath('status', 'in_repair')
            ->assertJsonPath('priority', 'high')
            ->assertJsonPath('priority_label', 'High')
            ->assertJsonPath('assignees.0', $staff->name)
            ->assertJsonPath('diagnosis', 'Charging port requires replacement.')
            ->assertJsonPath('device_model', 'ThinkPad T14')
            ->assertJsonPath('serial_number', 'SN-1001')
            ->assertJsonPath('pickup_address', '12 Jalan Repair, Kampar')
            ->assertJsonPath('dropoff_address', '18 Jalan Repair, Kampar')
            ->assertJsonPath('pickup_latitude', '4.3260000')
            ->assertJsonPath('dropoff_longitude', '101.1510000');

        $this->assertStringContainsString('In repair', $workflowResponse->json('status_html'));
        $this->assertSame('ThinkPad T14', $ticket->fresh()->device_model);
        $this->assertSame('SN-1001', $ticket->fresh()->serial_number);
        $this->assertSame('12 Jalan Repair, Kampar', $ticket->fresh()->pickup_address);
        $this->assertSame('18 Jalan Repair, Kampar', $ticket->fresh()->dropoff_address);
        $this->assertSame(4.326, (float) $ticket->fresh()->pickup_latitude);
        $this->assertSame(101.151, (float) $ticket->fresh()->dropoff_longitude);

        $this->postJson(route('tickets.steps.store', $ticket), [
            'title' => 'A step with photos must be rejected',
            'status' => 'in_repair',
            'photos' => [UploadedFile::fake()->image('batch-photo.png')],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('photos');

        $stepResponse = $this->post(route('tickets.steps.store', $ticket), [
            'title' => 'Replace charging port',
            'status' => 'waiting_parts',
            'notes' => 'Replacement part ordered.',
            'customer_details' => 'The replacement part has been ordered.',
        ], [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->assertOk()
            ->assertJsonPath('status', 'waiting_parts')
            ->assertJsonPath('message', 'Ticket work step added.');

        $this->assertStringContainsString('Replace charging port', $stepResponse->json('updates_html'));
        $this->assertStringContainsString('The replacement part has been ordered.', $stepResponse->json('updates_html'));
        $update = $ticket->updates()->latest('id')->firstOrFail();
        $this->assertSame('The replacement part has been ordered.', $update->customer_details);
        $this->assertStringContainsString(
            route('tickets.steps.attachments.store', [$ticket, $update]),
            $stepResponse->json('updates_html'),
        );
        $this->assertStringContainsString(
            'data-bs-target="#ticket-step-photo-modal-'.$update->id.'"',
            $stepResponse->json('updates_html'),
        );
        $this->assertStringContainsString(
            'id="ticket-step-photo-form-'.$update->id.'"',
            $stepResponse->json('updates_html'),
        );
        $this->assertStringContainsString(
            'data-ticket-step-photo-paste',
            $stepResponse->json('updates_html'),
        );
        $this->assertStringContainsString(
            'or choose images, take a photo, or paste screenshots',
            $stepResponse->json('updates_html'),
        );
        $this->assertStringContainsString(
            'data-ticket-step-photo-preview',
            $stepResponse->json('updates_html'),
        );
        $this->assertStringContainsString('Click each photo to add its caption.', $stepResponse->json('updates_html'));
        $this->assertStringContainsString(
            'action="'.route('tickets.steps.attachments.store', [$ticket, $update]).'"',
            $stepResponse->json('updates_html'),
        );

        $photoRoute = route('tickets.steps.attachments.store', [$ticket, $update]);
        $this->postJson($photoRoute, [
            'photos' => array_map(
                fn (int $index): UploadedFile => UploadedFile::fake()->image("photo-{$index}.png"),
                range(1, 9),
            ),
            'captions' => array_fill(0, 9, 'Too many photos should be rejected.'),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('photos');
        $this->postJson($photoRoute, [
            'photos' => [UploadedFile::fake()->image('caption-required.png')],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('captions');
        $this->postJson($photoRoute, [
            'photos' => [
                UploadedFile::fake()->image('photo-without-caption.png'),
                UploadedFile::fake()->image('another-photo-without-caption.png'),
            ],
            'captions' => ['Only one caption'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('captions');

        $this->postJson($photoRoute, [
            'photos' => [
                UploadedFile::fake()->image('port-inspection.png'),
                UploadedFile::fake()->image('replacement-port.png'),
            ],
            'captions' => ['Port inspection', 'Replacement port'],
        ])->assertOk()
            ->assertJsonPath('message', 'Photo added to repair step.');

        $secondPhotoResponse = $this->postJson($photoRoute, [
            'photos' => [UploadedFile::fake()->image('bent-pin.png')],
            'captions' => ['Bent connector pin'],
        ])->assertOk()
            ->assertJsonPath('message', 'Photo added to repair step.');

        $this->assertStringContainsString('Port inspection', $secondPhotoResponse->json('updates_html'));
        $this->assertStringContainsString('Replacement port', $secondPhotoResponse->json('updates_html'));
        $this->assertStringContainsString('Bent connector pin', $secondPhotoResponse->json('updates_html'));
        $this->assertStringContainsString('data-ticket-step-carousel', $secondPhotoResponse->json('updates_html'));
        $this->assertStringContainsString('data-bs-slide="next"', $secondPhotoResponse->json('updates_html'));
        $this->assertSame(
            ['Port inspection', 'Replacement port', 'Bent connector pin'],
            $update->attachments()->reorder('id')->pluck('caption')->all(),
        );
        $this->assertSame('waiting_parts', $ticket->fresh()->status);

        foreach ([
            ['Picking up the laptop', 'picking_up'],
            ['Ready to return the laptop', 'ready_to_drop_off'],
            ['Dropping off the laptop', 'dropping_off'],
        ] as [$title, $status]) {
            $this->postJson(route('tickets.steps.store', $ticket), [
                'title' => $title,
                'status' => $status,
            ])->assertOk()
                ->assertJsonPath('status', $status);
        }
        $this->assertSame('dropping_off', $ticket->fresh()->status);
    }

    public function test_no_fault_found_can_be_recorded_without_a_quotation_or_parts(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'No-fault customer',
            'email' => 'no-fault@example.test',
        ]);
        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-NO-FAULT-001',
            'customer_id' => $customer->id,
            'device_type' => 'Laptop',
            'device_model' => 'ThinkPad T14',
            'issue_description' => 'Customer reports intermittent slow performance.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
        ]);

        $this->actingAs($staff)->putJson(route('tickets.update', $ticket), [
            'status' => 'diagnosing',
            'priority' => 'normal',
            'diagnosis' => 'Hardware diagnostics passed; no fault found.',
        ])->assertOk()
            ->assertJsonPath('status', 'diagnosing');

        $this->postJson(route('tickets.steps.store', $ticket), [
            'title' => 'Diagnosis complete - no fault found',
            'status' => 'ready_for_pickup',
            'notes' => 'No repair or replacement parts were required.',
            'customer_details' => 'Testing is complete. No fault was found, and your laptop is ready for collection.',
        ])->assertOk()
            ->assertJsonPath('status', 'ready_for_pickup');

        $this->assertSame('Hardware diagnostics passed; no fault found.', $ticket->fresh()->diagnosis);
        $this->assertDatabaseCount('billing_documents', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->get(route('repairs.track.show', $ticket->ticket_number))
            ->assertOk()
            ->assertSee('No fault was found, and your laptop is ready for collection.');
    }

    public function test_closed_ticket_only_allows_status_change_for_warranty_follow_up(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Warranty Customer',
            'email' => 'warranty@example.test',
        ]);
        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-WARRANTY-001',
            'customer_id' => $customer->id,
            'issue_description' => 'The laptop shuts down unexpectedly.',
            'diagnosis' => 'Replaced the cooling fan.',
            'status' => 'closed',
            'priority' => 'normal',
            'received_at' => now()->subWeeks(2),
        ]);

        $this->actingAs($staff)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Update closed ticket status')
            ->assertSee('If the same issue returns under warranty')
            ->assertDontSee('id="ticket-step-modal"', false)
            ->assertDontSee('id="workflow-diagnosis"', false);

        $this->actingAs($staff)->putJson(route('tickets.update', $ticket), [
            'status' => 'diagnosing',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingAs($staff)->putJson(route('tickets.update', $ticket), [
            'status' => 'in_repair',
            'diagnosis' => 'Changed through a closed ticket request.',
            'priority' => 'urgent',
        ])->assertOk()
            ->assertJsonPath('status', 'in_repair')
            ->assertJsonPath('message', 'Ticket reopened for warranty follow-up.');

        $this->assertSame('in_repair', $ticket->fresh()->status);
        $this->assertSame('Replaced the cooling fan.', $ticket->fresh()->diagnosis);
        $this->assertSame('normal', $ticket->fresh()->priority);
        $this->assertSame(
            'Warranty follow-up: status changed to In repair',
            $ticket->updates()->latest('id')->firstOrFail()->title,
        );

        $ticket->fresh()->update(['status' => 'closed']);
        $this->assertSame('closed', $ticket->fresh()->status);
        $this->postJson(route('tickets.steps.store', $ticket), [
            'title' => 'Attempted post-closure edit',
            'status' => 'in_repair',
        ])->assertStatus(409);
        $this->postJson(route('tickets.attachments.store', $ticket), [
            'photos' => [UploadedFile::fake()->image('after-close.png')],
        ])->assertStatus(409);
    }

    public function test_customer_ticket_page_keeps_the_app_footer_and_hides_staff_actions(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $customer = Customer::query()->create([
            'name' => 'Portal Ticket Customer',
            'email' => 'portal-ticket@example.test',
        ]);
        $account = User::factory()->create(['email' => $customer->email]);
        $account->assignRole('customer');
        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-PORTAL-001',
            'customer_id' => $customer->id,
            'issue_description' => 'The desktop will not start.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
        ]);

        $this->actingAs($account)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('<footer class="footer">', false)
            ->assertDontSee('Edit ticket details')
            ->assertDontSee('Add repair step')
            ->assertDontSee('Create quotation')
            ->assertDontSee('id="ticket-workflow-modal"', false)
            ->assertDontSee('id="ticket-step-modal"', false);
    }

    public function test_customer_ticket_page_shows_only_their_sent_pricing_documents(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $customer = Customer::query()->create([
            'name' => 'Ticket Billing Customer',
            'email' => 'ticket-billing@example.test',
        ]);
        $account = User::factory()->create(['email' => $customer->email]);
        $account->assignRole('customer');
        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-BILLING-PORTAL-001',
            'customer_id' => $customer->id,
            'issue_description' => 'The desktop will not start.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
        ]);
        $documentAttributes = [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'repair_ticket_id' => $ticket->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 80,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'paid_amount' => 20,
        ];
        BillingDocument::query()->create([
            ...$documentAttributes,
            'number' => 'INV-TICKET-PORTAL-001',
            'type' => 'invoice',
            'status' => 'issued',
            'total' => 80,
        ]);
        BillingDocument::query()->create([
            ...$documentAttributes,
            'number' => 'QUO-TICKET-PORTAL-001',
            'type' => 'quotation',
            'status' => 'sent',
            'total' => 100,
        ]);
        BillingDocument::query()->create([
            ...$documentAttributes,
            'number' => 'QUO-TICKET-PORTAL-DRAFT',
            'type' => 'quotation',
            'status' => 'draft',
            'total' => 120,
        ]);
        BillingDocument::query()->create([
            ...$documentAttributes,
            'number' => 'INV-TICKET-PORTAL-OTHER',
            'type' => 'invoice',
            'status' => 'issued',
            'customer_email' => 'someone-else@example.test',
            'total' => 140,
        ]);

        $this->actingAs($account)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Pricing and Billing documents')
            ->assertSee('INV-TICKET-PORTAL-001')
            ->assertSee('QUO-TICKET-PORTAL-001')
            ->assertSee('RM 80.00')
            ->assertSee('Balance RM 60.00')
            ->assertSee('RM 100.00')
            ->assertDontSee('QUO-TICKET-PORTAL-DRAFT')
            ->assertDontSee('INV-TICKET-PORTAL-OTHER');
    }

    public function test_staff_can_create_invoice_from_an_accepted_ticket_quotation(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Accepted Ticket Quotation Customer',
            'email' => 'accepted-ticket-quotation@example.test',
        ]);
        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-ACCEPTED-QUOTATION-001',
            'customer_id' => $customer->id,
            'issue_description' => 'The laptop does not charge.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
        ]);
        $documentAttributes = [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'repair_ticket_id' => $ticket->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 100,
        ];
        $draftQuotation = BillingDocument::query()->create([
            ...$documentAttributes,
            'number' => 'QUO-TICKET-DRAFT-001',
            'type' => 'quotation',
            'status' => 'draft',
        ]);
        $acceptedQuotation = BillingDocument::query()->create([
            ...$documentAttributes,
            'number' => 'QUO-TICKET-ACCEPTED-001',
            'type' => 'quotation',
            'status' => 'accepted',
        ]);

        $ticketPage = $this->actingAs($staff)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($draftQuotation->number)
            ->assertSee($acceptedQuotation->number)
            ->assertSee('Create invoice');

        $previousLibxmlErrorSetting = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $loadedDocument = $document->loadHTML($ticketPage->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlErrorSetting);
        $this->assertTrue($loadedDocument);
        $xpath = new \DOMXPath($document);
        $this->assertSame(
            1,
            $xpath->query('//form[@action="'.route('billing.documents.convert', $acceptedQuotation).'"]')->length,
        );
        $this->assertSame(
            0,
            $xpath->query('//form[@action="'.route('billing.documents.convert', $draftQuotation).'"]')->length,
        );

        $this->post(route('billing.documents.convert', $acceptedQuotation))
            ->assertRedirect(route('billing.documents.show', $acceptedQuotation));

        $acceptedQuotation->refresh();
        $this->assertSame('invoice', $acceptedQuotation->type);
        $this->assertSame('issued', $acceptedQuotation->status);
        $this->assertSame($ticket->id, $acceptedQuotation->repair_ticket_id);
    }

    public function test_ticket_quotation_modal_returns_to_the_ticket_after_saving(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Quotation Modal Customer',
            'email' => 'quotation-modal@example.test',
        ]);
        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-QUOTATION-MODAL',
            'customer_id' => $customer->id,
            'issue_description' => 'The laptop does not charge.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
        ]);
        $this->actingAs($staff);

        $this->get(route('billing.create', [
            'type' => 'quotation',
            'repair_ticket_id' => $ticket->id,
            'ticket_modal' => 1,
        ]))->assertOk()
            ->assertSee('data-project-ajax-root', false)
            ->assertSee('name="modal_return_to_ticket" value="'.$ticket->id.'"', false)
            ->assertSee('initializeBillingCreateForm');

        $this->post(route('billing.store'), [
            'type' => 'quotation',
            'repair_ticket_id' => $ticket->id,
            'modal_return_to_ticket' => $ticket->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [[
                'description' => 'Laptop charging port repair',
                'quantity' => 1,
                'unit_price' => 120,
            ]],
        ])->assertRedirect(route('tickets.show', $ticket))
            ->assertSessionHas('success', 'Quotation saved as a draft.');

        $quotation = BillingDocument::query()->where('repair_ticket_id', $ticket->id)->firstOrFail();
        $this->assertSame('draft', $quotation->status);
        $this->assertDatabaseHas('billing_documents', [
            'repair_ticket_id' => $ticket->id,
            'type' => 'quotation',
            'status' => 'draft',
            'total' => 120,
        ]);
        $this->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee($quotation->number)
            ->assertSee('Draft')
            ->assertSee('data-ticket-billing-documents', false);
        $this->get(route('billing.documents.show', $quotation))
            ->assertOk()
            ->assertSee('data-project-ajax-root', false)
            ->assertSee('data-quotation-send-ajax', false)
            ->assertSee('action="'.route('billing.documents.send', $quotation).'"', false);
    }

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
            'customer_details' => 'We are waiting for the replacement part before continuing the repair.',
            'created_at' => now()->subHour(),
        ]);
        $step = $ticket->updates()->where('title', 'Parts ordered')->firstOrFail();
        Storage::disk('local')->put('attachments/repair_ticket_updates/step-photo.jpg', 'fake image contents');
        $attachment = $step->attachments()->create([
            'path' => 'attachments/repair_ticket_updates/step-photo.jpg',
            'original_name' => 'internal-filename.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 19,
            'caption' => 'Replacement part inspection',
        ]);

        $this->get(route('repairs.track.form'))
            ->assertOk()
            ->assertSee('<title>Track a Repair | OMG Computer</title>', false)
            ->assertSee('name="ticket_number"', false)
            ->assertDontSee('name="email"', false);
        $this->get(route('repairs.track.form', ['ticket_number' => $ticket->ticket_number]))
            ->assertOk()
            ->assertSee('value="'.$ticket->ticket_number.'"', false);
        $this->get(route('repairs.track.show', $ticket->ticket_number))
            ->assertOk()
            ->assertSee('Repair timeline')
            ->assertSee($ticket->ticket_number)
            ->assertSee('We are waiting for the replacement part before continuing the repair.')
            ->assertDontSee('Private intake details')
            ->assertDontSee('Internal supplier details');

        $this->post(route('repairs.track'), [
            'ticket_number' => 'rt-2026-000123',
        ])->assertOk()
            ->assertSee('RT-2026-000123')
            ->assertSee('Waiting for parts')
            ->assertSee('Repair timeline')
            ->assertDontSee('<h3 class="h5 mb-3">Repair steps</h3>', false)
            ->assertSee('New')
            ->assertSee('Waiting for parts')
            ->assertSee('Parts ordered')
            ->assertSee('We are waiting for the replacement part before continuing the repair.')
            ->assertSee('Replacement part inspection')
            ->assertSee('track-repair/photos/'.$attachment->id, false)
            ->assertSee('class="attachment-lightbox public-repair-step-photo-link"', false)
            ->assertSee('data-gallery="public-repair-step-'.$step->id.'"', false)
            ->assertSee('vendors/glightbox/glightbox.min.css')
            ->assertSee('vendors/glightbox/glightbox.min.js')
            ->assertDontSee('Private intake details')
            ->assertDontSee('Internal supplier details')
            ->assertDontSee('internal-filename.jpg')
            ->assertDontSee('tracking@example.test');

        $signedPhotoUrl = URL::temporarySignedRoute(
            'repairs.track.photo',
            now()->addMinutes(10),
            ['attachment' => $attachment],
        );
        $this->get($signedPhotoUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('repairs.track.photo', $attachment))->assertForbidden();

        $this->from(route('repairs.track.form'))
            ->post(route('repairs.track'), [
                'ticket_number' => 'RT-2026-999999',
            ])
            ->assertRedirect(route('repairs.track.form'))
            ->assertSessionHasErrors('ticket_number');

    }

    public function test_guest_must_verify_customer_email_before_viewing_ticket_billing(): void
    {
        Mail::fake();
        $customer = Customer::query()->create([
            'name' => 'Billing Verification Customer',
            'email' => 'billing-verification@example.test',
        ]);
        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-BILLING-VERIFY-001',
            'customer_id' => $customer->id,
            'issue_description' => 'The laptop does not charge.',
            'status' => 'in_repair',
            'priority' => 'normal',
            'received_at' => now(),
        ]);
        $documentAttributes = [
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'repair_ticket_id' => $ticket->id,
            'issue_date' => now()->toDateString(),
            'subtotal' => 100,
            'tax_rate' => 0,
            'tax_amount' => 0,
        ];
        $invoice = BillingDocument::query()->create([
            ...$documentAttributes,
            'number' => 'INV-TRACK-VERIFY-001',
            'type' => 'invoice',
            'status' => 'partial',
            'paid_amount' => 40,
            'total' => 100,
        ]);
        $quotation = BillingDocument::query()->create([
            ...$documentAttributes,
            'number' => 'QUO-TRACK-VERIFY-001',
            'type' => 'quotation',
            'status' => 'accepted',
            'paid_amount' => 0,
            'total' => 120,
        ]);
        $trackingUrl = route('repairs.track.show', $ticket->ticket_number);

        $this->get($trackingUrl)
            ->assertOk()
            ->assertSee('Billing details')
            ->assertSee('Send verification code')
            ->assertDontSee($invoice->number)
            ->assertDontSee($quotation->number)
            ->assertDontSee('RM 100.00');

        $this->from($trackingUrl)->post(route('repairs.track.billing-code', $ticket->ticket_number))
            ->assertRedirect($trackingUrl)
            ->assertSessionHas('billing_code_sent', true);

        $verificationCode = null;
        Mail::assertSent(
            RepairBillingVerificationMail::class,
            function (RepairBillingVerificationMail $mail) use (&$verificationCode, $customer): bool {
                $verificationCode = $mail->code;

                return $mail->hasTo($customer->email)
                    && str_contains($mail->render(), $mail->code);
            },
        );
        $this->assertIsString($verificationCode);

        $wrongCode = $verificationCode === '000000' ? '000001' : '000000';
        $this->from($trackingUrl)->post(route('repairs.track.billing-code.verify', $ticket->ticket_number), [
            'code' => $wrongCode,
        ])->assertRedirect($trackingUrl)
            ->assertSessionHasErrors('billing_verification');

        $this->post(route('repairs.track.billing-code.verify', $ticket->ticket_number), [
            'code' => $verificationCode,
        ])->assertRedirect($trackingUrl)
            ->assertSessionHas('billing_verified', true);

        $this->from($trackingUrl)->post(route('repairs.track.billing-code.verify', $ticket->ticket_number), [
            'code' => $verificationCode,
        ])->assertRedirect($trackingUrl)
            ->assertSessionHasErrors('billing_verification');

        $this->get($trackingUrl)
            ->assertOk()
            ->assertSee($invoice->number)
            ->assertSee($quotation->number)
            ->assertSee('Total RM 100.00')
            ->assertSee('Balance RM 60.00')
            ->assertSee('Total RM 120.00')
            ->assertDontSee('Balance RM 120.00');
    }

    public function test_guest_repair_tracking_uses_a_repair_work_hero_image(): void
    {
        $this->get(route('repairs.track.form'))
            ->assertOk()
            ->assertSee(asset('assets/img/omg/repair-board-work.jpg'), false)
            ->assertSee('Technician repairing a computer motherboard');
    }

    public function test_homepage_links_to_public_repair_tracking(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Have your service number ready to check repair progress.')
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

    public function test_public_repair_tracking_links_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->get(route('repairs.track.show', 'RT-UNKNOWN'))
                ->assertRedirect(route('repairs.track.form'));
        }

        $this->get(route('repairs.track.show', 'RT-UNKNOWN'))
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
            'warning' => 'Initial customer warning.',
        ])->assertRedirect();

        $customer = Customer::query()->firstOrFail();
        $customerAccount = User::factory()->create(['email' => 'avery@example.test']);
        $customerAccount->assignRole('customer');
        $this->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertSee('id="warning" name="warning"', false);
        $this->put(route('customers.update', $customer), [
            'name' => 'Avery Tan',
            'email' => 'avery@example.test',
            'phone' => '+60123456789',
            'company' => 'Kampar Campus',
            'project_id' => $project->id,
            'notes' => 'Prefers contact after 2 PM.',
            'warning' => 'Call before pickup; use the side entrance.',
        ])->assertRedirect(route('customers.show', $customer));
        $customer->refresh();
        $this->assertSame($project->id, $customer->project_id);
        $this->getJson(route('customers.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => 'contact after 2 PM'],
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.notes', fn (string $notes): bool => str_contains($notes, 'Prefers contact after 2 PM.'))
            ->assertJsonPath('data.0.customer', fn (string $customerHtml): bool => str_contains($customerHtml, 'Call before pickup; use the side entrance.'))
            ->assertSee('Avery Tan');
        $this->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Avery Tan')
            ->assertSee('Prefers contact after 2 PM.')
            ->assertSee('Important customer warning')
            ->assertSee('Call before pickup; use the side entrance.')
            ->assertSee('Customer login account')
            ->assertSee('Student support project')
            ->assertSee('New repair ticket');
        $this->get(route('members.show', $customerAccount))
            ->assertOk()
            ->assertSee('Important customer warning')
            ->assertSee('Call before pickup; use the side entrance.')
            ->assertSee('Prefers contact after 2 PM.')
            ->assertSee(route('customers.show', $customer), false);
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
            ->assertSee('Important customer warning')
            ->assertSee('Call before pickup; use the side entrance.')
            ->assertSee('Internal customer notes')
            ->assertSee('<span class="badge text-bg-warning">Waiting for parts</span>', false);
        $this->actingAs($customerAccount)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('Important customer warning')
            ->assertDontSee('Call before pickup; use the side entrance.')
            ->assertDontSee('Prefers contact after 2 PM.');
        $this->actingAs($user);
        $this->get(route('tickets.index', ['status' => 'waiting_parts']))
            ->assertOk()
            ->assertSee('data-datatable', false)
            ->assertSee('ticket-filters-modal')
            ->assertSee('ticket-filter-fab')
            ->assertSee('d-none d-lg-inline-flex')
            ->assertSee('data-bs-target="#ticket-filters-modal"', false)
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
