<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\BillingPaymentAttempt;
use App\Models\ChipSetting;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\User;
use App\Services\ChipCollect;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BillingInventoryReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_filters_are_available_from_desktop_button_and_mobile_floating_button(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->get(route('billing.index'))
            ->assertOk()
            ->assertSee('d-none d-lg-inline-flex')
            ->assertSee('billing-filter-fab')
            ->assertSee('data-bs-target="#billing-filters-modal"', false)
            ->assertSee('id="billing-filters-modal"', false)
            ->assertSee('name="type"', false)
            ->assertSee('name="status"', false);
    }

    public function test_payment_receipts_are_listed_and_only_visible_to_the_invoice_customer(): void
    {
        $this->get(route('billing.receipts.index'))->assertRedirect(route('login'));

        $admin = $this->adminUser();
        $customer = User::factory()->create(['email' => 'receipt-customer@example.test']);
        $customer->assignRole('customer');
        $otherCustomer = User::factory()->create(['email' => 'other-customer@example.test']);
        $otherCustomer->assignRole('customer');

        $document = BillingDocument::query()->create([
            'number' => 'INV-RECEIPT-001',
            'type' => 'invoice',
            'status' => 'paid',
            'customer_name' => 'Receipt customer',
            'customer_email' => $customer->email,
            'issue_date' => now()->toDateString(),
            'subtotal' => 80,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 80,
            'paid_amount' => 80,
        ]);
        $payment = $document->payments()->create([
            'user_id' => $admin->id,
            'amount' => 80,
            'method' => 'cash',
            'reference' => 'COUNTER-001',
            'paid_at' => now(),
        ]);
        $otherDocument = BillingDocument::query()->create([
            'number' => 'INV-RECEIPT-002',
            'type' => 'invoice',
            'status' => 'paid',
            'customer_name' => 'Other customer',
            'customer_email' => $otherCustomer->email,
            'issue_date' => now()->toDateString(),
            'subtotal' => 40,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 40,
            'paid_amount' => 40,
        ]);
        $otherPayment = $otherDocument->payments()->create([
            'user_id' => $admin->id,
            'amount' => 40,
            'method' => 'bank_transfer',
            'reference' => 'BANK-002',
            'paid_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('billing.receipts.index'))
            ->assertOk()
            ->assertSee('Payment receipts')
            ->assertSee($payment->receipt_number)
            ->assertSee('Receipt customer')
            ->assertSee(route('billing.receipts.pdf', $payment), false);
        $this->actingAs($admin)
            ->get(route('billing.receipts.pdf', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($customer)
            ->get(route('billing.receipts.index'))
            ->assertOk()
            ->assertSee($payment->receipt_number)
            ->assertDontSee($otherPayment->receipt_number)
            ->assertDontSee('Other customer');
        $this->actingAs($customer)
            ->get(route('billing.receipts.pdf', $payment))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->actingAs($otherCustomer)
            ->get(route('billing.receipts.pdf', $payment))
            ->assertNotFound();
    }

    public function test_project_inventory_search_returns_a_limited_result_set(): void
    {
        $admin = $this->adminUser();
        $project = Project::query()->create(['name' => 'Inventory search project']);

        $firstPart = null;
        foreach (range(1, 25) as $index) {
            $part = $this->createPart($admin, 3);
            $part->update(['name' => 'Searchable part '.str_pad((string) $index, 2, '0', STR_PAD_LEFT)]);
            $firstPart ??= $part;
        }

        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee(route('projects.parts.search', $project), false)
            ->assertSee('Type at least 2 characters to search inventory')
            ->assertDontSee('Searchable part 25');

        $this->actingAs($admin)
            ->get(route('billing.create', ['type' => 'quotation', 'project_id' => $project->id]))
            ->assertOk()
            ->assertSee(route('billing.parts.search'), false)
            ->assertDontSee('Searchable part 25');

        $this->actingAs($admin)
            ->get(route('billing.create', ['type' => 'invoice', 'project_id' => $project->id]))
            ->assertRedirect(route('billing.create', ['project_id' => $project->id, 'type' => 'quotation']));

        $this->actingAs($admin)
            ->getJson(route('projects.parts.search', $project).'?q=Searchable')
            ->assertOk()
            ->assertJsonCount(20, 'results')
            ->assertJsonPath('results.0.text', 'Searchable part 01 · '.$firstPart->sku.' · stock 3')
            ->assertJsonStructure(['results' => [['id', 'text', 'selling_price', 'cost_price']]]);

        $this->actingAs($admin)
            ->getJson(route('billing.parts.search').'?q=Searchable')
            ->assertOk()
            ->assertJsonCount(20, 'results')
            ->assertJsonStructure(['results' => [['id', 'text', 'name', 'price']]]);
    }

    public function test_new_billing_documents_use_the_configured_tax_rate(): void
    {
        config(['billing.tax_rate' => 6]);
        $user = $this->adminUser();

        $this->actingAs($user)
            ->get(route('billing.create', ['type' => 'invoice']))
            ->assertRedirect(route('billing.create', ['type' => 'quotation']));
        $this->actingAs($user)
            ->get(route('billing.create', ['type' => 'quotation']))
            ->assertOk()
            ->assertSee('value="6.00"', false);

        $data = $this->documentData('quotation', null, 1);
        $data['tax_rate'] = config('billing.tax_rate');
        $this->actingAs($user)->post(route('billing.store'), $data);

        $document = BillingDocument::query()->firstOrFail();
        $this->assertSame(6.0, (float) $document->tax_rate);
        $this->assertSame(0.6, (float) $document->tax_amount);
        $this->assertSame(10.6, (float) $document->total);
    }

    public function test_issuing_an_invoice_deducts_linked_stock_and_records_the_movement(): void
    {
        $user = $this->adminUser();
        $part = $this->createPart($user, 5);

        $this->actingAs($user)->post(route('billing.store'), $this->documentData('quotation', $part, 2));
        $document = BillingDocument::query()->firstOrFail();
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $document), ['status' => 'sent']);
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $document), ['status' => 'accepted']);
        $response = $this->actingAs($user)->post(route('billing.documents.convert', $document));
        $document->refresh();

        $response->assertRedirect(route('billing.documents.show', $document));
        $this->assertSame(3, $part->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_part_id' => $part->id,
            'type' => 'used',
            'quantity_change' => -2,
            'quantity_after' => 3,
            'reference' => $document->number,
        ]);
        $this->assertSame(20.0, (float) $document->total);
        $this->actingAs($user)->get(route('billing.index'))
            ->assertOk()
            ->assertSee('data-datatable', false);
        $this->actingAs($user)->getJson(route('billing.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
            'search' => ['value' => $document->number],
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.status_label', '<span class="badge text-bg-info">Issued</span>')
            ->assertSee($document->number)
            ->assertSee('RM 20.00');
    }

    public function test_failed_invoice_stock_deduction_rolls_back_the_document_and_stock_changes(): void
    {
        $user = $this->adminUser();
        $part = $this->createPart($user, 1);
        $this->actingAs($user)->post(route('billing.store'), $this->documentData('quotation', $part, 2));
        $document = BillingDocument::query()->firstOrFail();
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $document), ['status' => 'sent']);
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $document), ['status' => 'accepted']);

        $response = $this->actingAs($user)
            ->from(route('billing.documents.show', $document))
            ->post(route('billing.documents.convert', $document));

        $response->assertRedirect(route('billing.documents.show', $document))
            ->assertSessionHasErrors('quantity');
        $this->assertSame('quotation', $document->fresh()->type);
        $this->assertSame('accepted', $document->fresh()->status);
        $this->assertSame(1, $part->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_quote_conversion_deducts_stock_and_payments_update_invoice_balance(): void
    {
        $user = $this->adminUser();
        $part = $this->createPart($user, 5);
        $this->actingAs($user)->post(route('billing.store'), $this->documentData('quote', $part, 2));
        $quote = BillingDocument::query()->firstOrFail();
        $this->actingAs($user)->get(route('billing.documents.show', $quote))
            ->assertOk()
            ->assertSee('Mark as sent')
            ->assertDontSee('Convert to invoice');

        $this->actingAs($user)->post(route('billing.documents.convert', $quote))
            ->assertUnprocessable();
        $this->assertSame('draft', $quote->fresh()->status);

        $this->actingAs($user)->post(route('billing.documents.quotation-status', $quote), ['status' => 'sent'])
            ->assertRedirect(route('billing.documents.show', $quote));
        $this->assertSame('sent', $quote->fresh()->status);
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $quote), ['status' => 'rejected'])
            ->assertRedirect(route('billing.documents.show', $quote));
        $this->assertSame('rejected', $quote->fresh()->status);
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $quote), ['status' => 'accepted'])
            ->assertUnprocessable();
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $quote), ['status' => 'sent'])
            ->assertRedirect(route('billing.documents.show', $quote));
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $quote), ['status' => 'accepted'])
            ->assertRedirect(route('billing.documents.show', $quote));
        $this->assertSame('accepted', $quote->fresh()->status);

        $this->actingAs($user)->post(route('billing.documents.convert', $quote))
            ->assertRedirect(route('billing.documents.show', $quote));
        $quote->refresh();
        $this->assertSame('invoice', $quote->type);
        $this->assertSame('issued', $quote->status);
        $this->assertSame(3, $part->fresh()->stock_quantity);
        $this->actingAs($user)->get(route('billing.documents.show', $quote))
            ->assertOk()
            ->assertSee('Record full payment')
            ->assertSee('Partial payments are not currently supported.');

        $paymentData = [
            'amount' => 10,
            'method' => 'cash',
            'received_at' => now()->toDateString(),
        ];
        $this->actingAs($user)->post(route('billing.documents.payments.store', $quote), $paymentData)
            ->assertSessionHasErrors('amount');
        $this->assertSame('issued', $quote->fresh()->status);
        $this->assertSame(20.0, $quote->fresh()->balance_due);

        $paymentData['amount'] = 20;
        $this->actingAs($user)->post(route('billing.documents.payments.store', $quote), $paymentData)
            ->assertRedirect(route('billing.documents.show', $quote));
        $this->assertSame('paid', $quote->fresh()->status);
        $this->assertSame(0.0, $quote->fresh()->balance_due);
        $this->assertSame(1, InventoryMovement::query()->where('type', 'used')->count());
        $payment = $quote->payments()->firstOrFail();
        $this->actingAs($user)->get(route('billing.documents.show', $quote))
            ->assertOk()
            ->assertSee($payment->receipt_number)
            ->assertSee(route('billing.receipts.pdf', $payment), false);
        $this->actingAs($user)->get(route('billing.receipts.index'))
            ->assertOk()
            ->assertSee($payment->receipt_number);
    }

    public function test_staff_can_record_external_acceptance_of_draft_quotation_without_sending_it(): void
    {
        $staff = $this->adminUser();
        $this->actingAs($staff)->post(route('billing.store'), $this->documentData('quotation', null, 1));
        $quotation = BillingDocument::query()->firstOrFail();

        $this->actingAs($staff)->get(route('billing.documents.show', $quotation))
            ->assertOk()
            ->assertSee('Sending this quotation through the website is optional.')
            ->assertSee('Record customer accepted');

        $this->actingAs($staff)->post(route('billing.documents.quotation-status', $quotation), [
            'status' => 'accepted',
        ])->assertRedirect(route('billing.documents.show', $quotation))
            ->assertSessionHas('success', 'Customer acceptance recorded. You can now convert this quotation to an invoice.');

        $this->assertSame('accepted', $quotation->fresh()->status);
        $this->actingAs($staff)->post(route('billing.documents.convert', $quotation))
            ->assertRedirect(route('billing.documents.show', $quotation));
        $this->assertSame('invoice', $quotation->fresh()->type);
        $this->assertSame('issued', $quotation->fresh()->status);
    }

    public function test_customer_can_start_chip_checkout_for_their_invoice(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        config([
            'services.chip.secret_key' => 'test-secret',
            'services.chip.brand_id' => 'test-brand',
            'services.chip.enabled' => true,
        ]);
        Http::fake([
            'gate.chip-in.asia/api/v1/purchases/' => Http::response([
                'id' => 'chip-purchase-123',
                'checkout_url' => 'https://gate.chip-in.asia/checkout/123',
            ], 201),
        ]);

        $customer = User::factory()->create(['email' => 'invoice-customer@example.test']);
        $customer->assignRole('customer');
        $project = Project::query()->create(['name' => 'Customer project']);
        $firstCustomer = Customer::query()->create([
            'project_id' => $project->id,
            'name' => 'Invoice customer',
            'email' => 'invoice-customer@example.test',
        ]);
        $document = $this->createIssuedInvoice($project, 125.50);

        $this->actingAs($customer)
            ->post(route('billing.chip.checkout', $document))
            ->assertRedirect('https://gate.chip-in.asia/checkout/123');
        $this->actingAs($customer)
            ->post(route('billing.documents.payments.store', $document), [
                'amount' => 125.50,
                'method' => 'cash',
                'paid_at' => now()->toDateString(),
            ])
            ->assertForbidden();

        $attempt = BillingPaymentAttempt::query()->firstOrFail();
        $this->assertSame('pending', $attempt->status);
        $this->assertSame('chip-purchase-123', $attempt->provider_purchase_id);
        $this->assertFalse($attempt->chip_test_mode);
        $this->assertSame(125.50, (float) $attempt->amount);
        $this->assertSame(0.0, (float) $document->fresh()->paid_amount);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://gate.chip-in.asia/api/v1/purchases/'
            && $request['purchase']['currency'] === 'MYR'
            && $request['brand_id'] === 'test-brand'
            && $request['purchase']['products'][0]['price'] === 12550
            && str_starts_with($request['reference'], $document->number.'-')
            && $request['success_callback'] === route('webhooks.chip.collect', $attempt->uuid));
    }

    public function test_customer_account_can_view_billing_documents_for_a_directly_linked_project(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $customer = User::factory()->create(['email' => 'invoice-customer@example.test']);
        $customer->assignRole('customer');
        $project = Project::query()->create([
            'name' => 'Directly linked project',
            'customer_user_id' => $customer->id,
        ]);
        $document = $this->createIssuedInvoice($project, 75);
        $quotation = $this->createIssuedInvoice($project, 120, 'quotation');
        $draftQuotation = $this->createDraftQuotation($project, $customer->email);
        $secondCustomer = User::factory()->create(['email' => 'second-customer@example.test']);
        $secondCustomer->assignRole('customer');
        $secondCustomer = Customer::query()->create([
            'project_id' => $project->id,
            'name' => 'Second customer',
            'email' => $secondCustomer->email,
        ]);
        $secondCustomerDocument = $this->createIssuedInvoice(
            $project,
            90,
            'invoice',
            $secondCustomer->email,
            'Second customer',
        );
        $otherCustomer = User::factory()->create(['email' => 'other-invoice-customer@example.test']);
        $otherCustomer->assignRole('customer');
        $otherProject = Project::query()->create([
            'name' => 'Another customer project',
            'customer_user_id' => $otherCustomer->id,
        ]);
        $otherDocument = $this->createIssuedInvoice($otherProject, 200);
        $unlinkedProject = Project::query()->create(['name' => 'Unlinked project']);
        $unlinkedDocument = $this->createIssuedInvoice($unlinkedProject, 300);
        $customerTicketDocument = $this->createTicketInvoice($customer, 'RT-CUSTOMER-001', 50);
        $otherTicketDocument = $this->createTicketInvoice($otherCustomer, 'RT-OTHER-001', 60);

        $this->actingAs($customer)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('data-datatable', false)
            ->assertSee('My bills & payments')
            ->assertDontSee('All document types');
        $this->actingAs($customer)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertDontSee($draftQuotation->number)
            ->assertDontSee($secondCustomerDocument->number);
        $this->actingAs($customer)
            ->getJson(route('billing.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 3)
            ->assertJsonPath('recordsFiltered', 3)
            ->assertSee($document->number)
            ->assertSee($quotation->number)
            ->assertSee($customerTicketDocument->number)
            ->assertDontSee($draftQuotation->number)
            ->assertDontSee($secondCustomerDocument->number)
            ->assertDontSee($otherDocument->number)
            ->assertDontSee($unlinkedDocument->number)
            ->assertDontSee($otherTicketDocument->number);

        $this->get(route('billing.documents.show', $document))
            ->assertOk()
            ->assertSee($document->number);
        $this->get(route('billing.documents.show', $quotation))
            ->assertOk()
            ->assertSee($quotation->number);
        $this->get(route('billing.documents.show', $draftQuotation))->assertNotFound();
        $this->get(route('billing.documents.pdf', $draftQuotation))->assertNotFound();
        $this->get(route('billing.documents.show', $customerTicketDocument))
            ->assertOk()
            ->assertSee($customerTicketDocument->number);
        $this->get(route('billing.documents.show', $secondCustomerDocument))->assertNotFound();
        $this->get(route('billing.documents.pdf', $secondCustomerDocument))->assertNotFound();
        $this->get(route('billing.documents.show', $otherDocument))->assertNotFound();
        $this->get(route('billing.documents.show', $unlinkedDocument))->assertNotFound();
        $this->get(route('billing.documents.show', $otherTicketDocument))->assertNotFound();

        $this->actingAs($secondCustomer)
            ->getJson(route('billing.datatable', [
                'draw' => 1,
                'start' => 0,
                'length' => 25,
            ]))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertSee($secondCustomerDocument->number)
            ->assertDontSee($document->number)
            ->assertDontSee($quotation->number);
        $this->assertSame('sent', $quotation->status);
        $this->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee($secondCustomerDocument->number)
            ->assertDontSee($document->number)
            ->assertDontSee($quotation->number);
    }

    public function test_customer_project_billing_lists_sent_quotations_and_invoices_but_not_drafts(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $customer = User::factory()->create(['email' => 'billing-portal@example.test']);
        $customer->assignRole('customer');
        $project = Project::query()->create([
            'name' => 'Customer billing portal project',
            'customer_user_id' => $customer->id,
        ]);
        $invoice = $this->createIssuedInvoice($project, 90, 'invoice', $customer->email);
        $quotation = $this->createIssuedInvoice($project, 110, 'quotation', $customer->email);
        $draft = $this->createDraftQuotation($project, $customer->email);

        $this->actingAs($customer)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee($invoice->number)
            ->assertSee($quotation->number)
            ->assertDontSee($draft->number);
        $this->post(route('billing.documents.quotation-status', $quotation), ['status' => 'accepted'])
            ->assertForbidden();
    }

    public function test_chip_callback_records_payment_idempotently_after_signature_and_status_verification(): void
    {
        ChipSetting::setTestMode(true);
        $project = Project::query()->create(['name' => 'Callback project']);
        $document = $this->createIssuedInvoice($project, 40);
        $attempt = BillingPaymentAttempt::query()->create([
            'uuid' => (string) Str::uuid(),
            'billing_document_id' => $document->id,
            'amount' => 40,
            'provider_purchase_id' => 'chip-purchase-456',
            'chip_test_mode' => true,
            'status' => 'pending',
        ]);
        $body = json_encode(['id' => 'chip-purchase-456'], JSON_THROW_ON_ERROR);
        $this->mock(ChipCollect::class, function ($mock) use ($body): void {
            $mock->shouldReceive('verifyCallback')->twice()->with($body, 'valid-signature', true)->andReturnTrue();
            $mock->shouldReceive('purchase')->twice()->with('chip-purchase-456', true)->andReturn([
                'id' => 'chip-purchase-456',
                'status' => 'paid',
            ]);
        });
        ChipSetting::setTestMode(false);

        $callback = fn () => $this->call(
            'POST',
            route('webhooks.chip.collect', $attempt->uuid),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => 'valid-signature'],
            $body,
        );

        $callback()->assertOk();
        $callback()->assertOk();

        $this->assertSame('paid', $attempt->fresh()->status);
        $this->assertSame('paid', $document->fresh()->status);
        $this->assertSame(40.0, (float) $document->fresh()->paid_amount);
        $this->assertDatabaseCount('billing_payments', 1);
    }

    public function test_chip_callback_rejects_invalid_signatures(): void
    {
        $attempt = BillingPaymentAttempt::query()->create([
            'uuid' => (string) Str::uuid(),
            'billing_document_id' => $this->createIssuedInvoice(Project::query()->create(['name' => 'Invalid callback']), 20)->id,
            'amount' => 20,
            'provider_purchase_id' => 'chip-purchase-invalid',
            'status' => 'pending',
        ]);
        $this->mock(ChipCollect::class, function ($mock): void {
            $mock->shouldReceive('verifyCallback')->once()->andReturnFalse();
            $mock->shouldNotReceive('purchase');
        });

        $this->postJson(route('webhooks.chip.collect', $attempt->uuid), ['id' => 'chip-purchase-invalid'])
            ->assertUnauthorized();
    }

    public function test_project_can_have_customer_visible_invoices_and_quotations(): void
    {
        $admin = $this->adminUser();
        $project = Project::query()->create(['name' => 'Website redesign']);
        Customer::query()->create([
            'project_id' => $project->id,
            'name' => 'Project customer',
            'email' => 'project-customer@example.test',
        ]);

        $this->actingAs($admin)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Project billing and quotations')
            ->assertSee('Create quotation')
            ->assertDontSee('Create invoice');

        $this->get(route('billing.create', ['type' => 'invoice', 'project_id' => $project->id]))
            ->assertRedirect(route('billing.create', ['project_id' => $project->id, 'type' => 'quotation']));
        $this->get(route('billing.create', ['type' => 'quotation', 'project_id' => $project->id]))
            ->assertOk()
            ->assertSee('Website redesign')
            ->assertSee('Project customer')
            ->assertSee('Project scope and terms')
            ->assertSee('name="included_revision_rounds"', false)
            ->assertSee('name="project_due_date"', false)
            ->assertSee('name="payment_terms"', false);

        foreach (range(1, 2) as $quoteNumber) {
            $documentData = [
                'type' => 'quotation',
                'project_id' => $project->id,
                'customer_name' => 'Project customer',
                'customer_email' => 'project-customer@example.test',
                'issue_date' => now()->toDateString(),
                'tax_rate' => 0,
                'items' => [[
                    'description' => 'Project work',
                    'quantity' => 1,
                    'unit_price' => 250,
                ]],
                'project_scope' => 'Five responsive pages with a contact form.',
                'included_revision_rounds' => 3,
                'project_due_date' => now()->addMonth()->toDateString(),
                'payment_terms' => 'Full payment before work begins.',
            ];
            $this->post(route('billing.store'), $documentData)->assertRedirect();
        }
        $quotation = BillingDocument::query()->where('type', 'quotation')->orderBy('id')->firstOrFail();
        $acceptedQuote = BillingDocument::query()->where('type', 'quotation')->orderByDesc('id')->firstOrFail();
        $this->actingAs($admin)->post(route('billing.documents.quotation-status', $acceptedQuote), ['status' => 'sent']);
        $this->actingAs($admin)->post(route('billing.documents.quotation-status', $acceptedQuote), ['status' => 'accepted']);
        $this->actingAs($admin)->post(route('billing.documents.convert', $acceptedQuote));
        $this->actingAs($admin)->post(route('billing.documents.quotation-status', $quotation), ['status' => 'sent']);
        $this->assertSame('Five responsive pages with a contact form.', $quotation->project_scope);
        $this->assertSame(3, $quotation->included_revision_rounds);
        $this->assertSame('Full payment before work begins.', $quotation->payment_terms);
        $this->actingAs($admin)->get(route('billing.documents.show', $quotation))
            ->assertOk()
            ->assertSee('href="'.route('customers.show', Customer::query()->where('email', 'project-customer@example.test')->firstOrFail()).'"', false)
            ->assertSee('Five responsive pages with a contact form.')
            ->assertSee('Up to 3 revision rounds are included.')
            ->assertSee('Full payment before work begins.')
            ->assertSee('Hosting, domain registration, third-party services, and ongoing maintenance are excluded');

        $customer = User::factory()->create(['email' => 'PROJECT-CUSTOMER@example.test']);
        $customer->assignRole('customer');
        $this->actingAs($customer)
            ->get(route('projects.show', $project))
            ->assertOk()
            ->assertSee('Project billing and quotations')
            ->assertSee('QUO-')
            ->assertSee('INV-')
            ->assertDontSee('Create invoice')
            ->assertDontSee('Create quotation');

        $documents = BillingDocument::query()->where('project_id', $project->id)->get();
        $this->assertCount(2, $documents);
        $this->assertTrue($documents->every(fn (BillingDocument $document): bool => $document->project_id === $project->id));
        foreach ($documents as $document) {
            $this->get(route('billing.documents.show', $document))->assertOk();
            $this->get(route('billing.documents.pdf', $document))->assertOk();
        }

        $otherCustomer = User::factory()->create(['email' => 'other-customer@example.test']);
        $otherCustomer->assignRole('customer');
        foreach ($documents as $document) {
            $this->actingAs($otherCustomer)->get(route('billing.documents.show', $document))->assertNotFound();
        }
    }

    public function test_project_quotation_terms_are_saved_and_included_in_customer_view_and_pdf(): void
    {
        $admin = $this->adminUser();
        $project = Project::query()->create(['name' => 'Website quotation terms']);

        $this->actingAs($admin)
            ->get(route('billing.create', ['type' => 'quotation', 'project_id' => $project->id]))
            ->assertOk()
            ->assertSee('Project scope and terms')
            ->assertSee('Revision rounds included (maximum 3)')
            ->assertSee('Target completion date')
            ->assertSee('Payment schedule');

        $this->post(route('billing.store'), [
            'type' => 'quotation',
            'project_id' => $project->id,
            'customer_name' => 'Website customer',
            'customer_email' => 'website-customer@example.test',
            'issue_date' => now()->toDateString(),
            'project_scope' => 'Five responsive pages and a contact form.',
            'included_revision_rounds' => 3,
            'project_due_date' => now()->addMonth()->toDateString(),
            'payment_terms' => '40% deposit and the balance before launch.',
            'tax_rate' => 0,
            'items' => [[
                'description' => 'Website design and development',
                'quantity' => 1,
                'unit_price' => 2500,
            ]],
        ])->assertRedirect();

        $quotation = BillingDocument::query()->where('type', 'quotation')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('billing.documents.show', $quotation))
            ->assertOk()
            ->assertSee('Five responsive pages and a contact form.')
            ->assertSee('Up to 3 revision rounds are included.')
            ->assertSee('40% deposit and the balance before launch.')
            ->assertSee('Hosting, domain registration, third-party services, and ongoing maintenance are excluded');

        $this->get(route('billing.documents.pdf', $quotation))->assertOk();

        $this->put(route('billing.documents.update', $quotation), [
            'type' => 'quotation',
            'customer_name' => 'Website customer',
            'customer_email' => 'website-customer@example.test',
            'issue_date' => now()->toDateString(),
            'project_scope' => 'Five responsive pages, a contact form, and basic SEO.',
            'included_revision_rounds' => 2,
            'project_due_date' => now()->addMonths(2)->toDateString(),
            'payment_terms' => '50% deposit and 50% before launch.',
            'tax_rate' => 0,
            'items' => [[
                'description' => 'Website design and development',
                'quantity' => 1,
                'unit_price' => 2500,
            ]],
        ])->assertSessionHasNoErrors();

        $quotation->refresh();
        $this->assertSame(2, $quotation->included_revision_rounds);
        $this->assertSame('Five responsive pages, a contact form, and basic SEO.', $quotation->project_scope);
        $this->assertSame('50% deposit and 50% before launch.', $quotation->payment_terms);
    }

    public function test_project_quotation_rejects_more_than_three_revision_rounds(): void
    {
        $admin = $this->adminUser();
        $project = Project::query()->create(['name' => 'Revision limit project']);

        $this->actingAs($admin)->from(route('billing.create', [
            'type' => 'quotation',
            'project_id' => $project->id,
        ]))->post(route('billing.store'), [
            'type' => 'quotation',
            'project_id' => $project->id,
            'customer_name' => 'Project customer',
            'issue_date' => now()->toDateString(),
            'included_revision_rounds' => 4,
            'tax_rate' => 0,
            'items' => [[
                'description' => 'Website project',
                'quantity' => 1,
                'unit_price' => 100,
            ]],
        ])->assertSessionHasErrors('included_revision_rounds');

        $this->assertDatabaseCount('billing_documents', 0);
    }

    public function test_project_billing_form_can_choose_which_linked_customer_receives_the_quotation(): void
    {
        $admin = $this->adminUser();
        $project = Project::query()->create(['name' => 'Multi-customer project']);
        $firstCustomer = Customer::query()->create([
            'project_id' => $project->id,
            'name' => 'First project customer',
            'email' => 'first-project-customer@example.test',
            'phone' => '0123456789',
            'address' => 'First customer address',
        ]);
        $secondCustomer = Customer::query()->create([
            'project_id' => $project->id,
            'name' => 'Second project customer',
            'email' => 'second-project-customer@example.test',
            'phone' => '0198765432',
            'address' => 'Second customer address',
        ]);

        $this->actingAs($admin)
            ->get(route('billing.create', ['type' => 'quotation', 'project_id' => $project->id]))
            ->assertOk()
            ->assertSee('Customer recipient from this project')
            ->assertSee('value="customer:'.$firstCustomer->id.'"', false)
            ->assertSee('value="customer:'.$secondCustomer->id.'"', false)
            ->assertSee('data-email="second-project-customer@example.test"', false)
            ->assertSee('data-phone="0198765432"', false)
            ->assertSee('data-address="Second customer address"', false)
            ->assertSee('document.getElementById(\'customer_email\').value = option.dataset.email', false);
    }

    public function test_business_plan_services_can_be_quoted_and_billed_without_stock_deduction(): void
    {
        $user = $this->adminUser();
        $item = [
            'product_id' => 'laptop-cleaning',
            'quantity' => 1,
            'unit_price' => null,
        ];
        $data = [
            'type' => 'quotation',
            'customer_name' => 'Customer Example',
            'tax_rate' => 0,
            'items' => [$item],
        ];

        $this->actingAs($user)->post(route('billing.store'), $data)
            ->assertRedirect();
        $quotation = BillingDocument::query()->firstOrFail();
        $this->assertSame('quotation', $quotation->type);
        $this->assertSame('draft', $quotation->status);
        $this->assertSame(60.0, (float) $quotation->total);
        $this->assertSame('Laptop cleaning', $quotation->items()->firstOrFail()->description);

        $data['type'] = 'invoice';
        $this->actingAs($user)->post(route('billing.store'), $data)
            ->assertSessionHasErrors('type');
        $this->assertSame(1, BillingDocument::query()->count());
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $quotation), ['status' => 'sent']);
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $quotation), ['status' => 'accepted']);
        $this->actingAs($user)->post(route('billing.documents.convert', $quotation));
        $invoice = $quotation->fresh();
        $this->assertSame('invoice', $invoice->type);
        $this->assertSame('issued', $invoice->status);
        $this->assertSame(60.0, (float) $invoice->total);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_draft_quotation_can_be_edited_without_issuing_or_deducting_stock(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->post(route('billing.store'), $this->documentData('quotation', null, 1));
        $quotation = BillingDocument::query()->firstOrFail();

        $this->get(route('billing.documents.edit', $quotation))
            ->assertOk()
            ->assertSee('Edit draft')
            ->assertSee('Cleaning service');

        $this->put(route('billing.documents.update', $quotation), [
            'type' => 'quotation',
            'customer_name' => 'Updated Customer',
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [[
                'product_id' => 'laptop-cleaning',
                'description' => 'Laptop cleaning',
                'quantity' => 2,
                'unit_price' => 60,
            ]],
        ])->assertRedirect(route('billing.documents.show', $quotation));

        $quotation->refresh();
        $this->assertSame('draft', $quotation->status);
        $this->assertSame('Updated Customer', $quotation->customer_name);
        $this->assertSame(120.0, (float) $quotation->total);
        $this->assertSame('Laptop cleaning', $quotation->items()->firstOrFail()->description);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_rejected_quotation_can_be_edited_and_returns_to_draft_until_resent(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->post(route('billing.store'), $this->documentData('quotation', null, 1));
        $quotation = BillingDocument::query()->firstOrFail();
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $quotation), ['status' => 'sent']);
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $quotation), ['status' => 'rejected']);

        $this->get(route('billing.documents.show', $quotation))
            ->assertOk()
            ->assertSee('Edit rejected quotation');
        $this->get(route('billing.documents.edit', $quotation))
            ->assertOk()
            ->assertSee('Edit rejected quotation')
            ->assertSee('Save revised quotation as draft');

        $this->put(route('billing.documents.update', $quotation), [
            'type' => 'quotation',
            'customer_name' => 'Revised Customer',
            'issue_date' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [[
                'description' => 'Revised service',
                'quantity' => 2,
                'unit_price' => 75,
            ]],
        ])->assertRedirect(route('billing.documents.show', $quotation))
            ->assertSessionHas('success', 'Rejected quotation updated and returned to draft. Mark it as sent when ready.');

        $quotation->refresh();
        $this->assertSame('draft', $quotation->status);
        $this->assertSame('Revised Customer', $quotation->customer_name);
        $this->assertSame(150.0, (float) $quotation->total);
        $this->assertSame('Revised service', $quotation->items()->firstOrFail()->description);
        $this->actingAs($user)->get(route('billing.documents.show', $quotation))
            ->assertOk()
            ->assertSee('Mark as sent')
            ->assertDontSee('Convert to invoice');
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_invoice_pdf_and_date_filtered_reports_are_available(): void
    {
        $user = $this->adminUser();
        $data = $this->documentData('quotation', null, 1);
        $data['customer_name'] = '=HYPERLINK("https://example.test","open")';
        $this->actingAs($user)->post(route('billing.store'), $data);
        $document = BillingDocument::query()->firstOrFail();
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $document), ['status' => 'sent']);
        $this->actingAs($user)->post(route('billing.documents.quotation-status', $document), ['status' => 'accepted']);
        $this->actingAs($user)->post(route('billing.documents.convert', $document));
        $document->refresh();
        $period = [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->endOfMonth()->toDateString(),
        ];

        $this->actingAs($user)->get(route('billing.documents.pdf', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF', false);
        $this->actingAs($user)->get(route('reports.index', $period))
            ->assertOk()
            ->assertViewHas('invoiceCount', 1)
            ->assertViewHas('billed', 10.0);

        $export = $this->actingAs($user)->get(route('reports.export', $period));
        $export->assertOk()->assertDownload();
        $this->assertStringContainsString('Invoice,Customer,Issued,Status', $export->streamedContent());
        $this->assertStringContainsString("'=HYPERLINK", $export->streamedContent());
    }

    public function test_inventory_opening_and_manual_movements_are_logged_and_low_stock_is_filterable(): void
    {
        $user = $this->adminUser();

        $this->actingAs($user)->post(route('inventory.store'), [
            'sku' => 'SSD-001',
            'name' => 'Solid state drive',
            'opening_stock' => 2,
            'reorder_level' => 2,
            'unit_cost' => 80,
            'sale_price' => 100,
        ])->assertRedirect(route('inventory.parts'));

        $part = InventoryPart::query()->firstOrFail();
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_part_id' => $part->id,
            'type' => 'received',
            'quantity_change' => 2,
            'quantity_after' => 2,
        ]);

        $this->actingAs($user)->post(route('inventory.movements.store', $part), [
            'type' => 'used',
            'quantity' => 1,
            'note' => 'Test usage',
        ])->assertRedirect(route('inventory.movements'));

        $this->assertSame(1, $part->fresh()->stock_quantity);
        $this->actingAs($user)->get(route('inventory.parts', ['low_stock' => 1]))
            ->assertOk()
            ->assertSee('data-datatable', false);
        $this->actingAs($user)->getJson(route('inventory.parts.datatable', [
            'low_stock' => 1,
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()->assertJsonPath('data.0.name', 'Solid state drive');
    }

    public function test_inventory_overview_parts_and_movements_are_separate_pages(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->get(route('inventory.index'))
            ->assertOk()->assertSee('Inventory overview')->assertSee('Recent stock movements');
        $this->actingAs($user)->get(route('inventory.parts'))
            ->assertOk()->assertSee('Parts')->assertDontSee('Recent stock movements');
        $this->actingAs($user)->get(route('inventory.movements'))
            ->assertOk()->assertSee('Stock movements')->assertSee('data-datatable', false);
        $this->actingAs($user)->getJson(route('inventory.movements.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()->assertJsonPath('recordsTotal', 0);
    }

    private function createIssuedInvoice(
        Project $project,
        float $total,
        string $type = 'invoice',
        string $customerEmail = 'invoice-customer@example.test',
        string $customerName = 'Invoice customer',
        ?string $status = null,
    ): BillingDocument {
        return BillingDocument::query()->create([
            'number' => ($type === 'quotation' ? 'QUO-' : 'INV-').strtoupper(uniqid()),
            'type' => $type,
            'status' => $status ?? ($type === 'quotation' ? 'sent' : 'issued'),
            'project_id' => $project->id,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'issue_date' => now()->toDateString(),
            'subtotal' => $total,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => $total,
            'paid_amount' => 0,
        ]);
    }

    private function createDraftQuotation(Project $project, string $customerEmail): BillingDocument
    {
        return $this->createIssuedInvoice($project, 30, 'quotation', $customerEmail, 'Invoice customer', 'draft');
    }

    private function createTicketInvoice(User $customer, string $ticketNumber, float $total): BillingDocument
    {
        $customerRecord = Customer::query()->create([
            'name' => $customer->name,
            'email' => $customer->email,
        ]);
        $ticket = RepairTicket::query()->create([
            'ticket_number' => $ticketNumber,
            'customer_id' => $customerRecord->id,
            'issue_description' => 'Repair billing test.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
        ]);

        return BillingDocument::query()->create([
            'number' => 'INV-'.strtoupper(uniqid()),
            'type' => 'invoice',
            'status' => 'issued',
            'repair_ticket_id' => $ticket->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'issue_date' => now()->toDateString(),
            'subtotal' => $total,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => $total,
            'paid_amount' => 0,
        ]);
    }

    private function createPart(User $user, int $stock): InventoryPart
    {
        return InventoryPart::query()->create([
            'user_id' => $user->id,
            'sku' => 'PART-'.uniqid(),
            'name' => 'Test SSD',
            'stock_quantity' => $stock,
            'reorder_level' => 1,
            'unit_cost' => 5,
            'sale_price' => 10,
            'is_active' => true,
        ]);
    }

    private function adminUser(): User
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function documentData(string $type, ?InventoryPart $part, int $quantity): array
    {
        return [
            'type' => $type,
            'customer_name' => 'Customer Example',
            'issued_at' => now()->toDateString(),
            'tax_rate' => 0,
            'items' => [[
                'inventory_part_id' => $part?->id,
                'description' => $part?->name ?? 'Cleaning service',
                'quantity' => $quantity,
                'unit_price' => 10,
            ]],
        ];
    }
}
