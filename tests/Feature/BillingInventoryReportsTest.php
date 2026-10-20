<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingInventoryReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_issuing_an_invoice_deducts_linked_stock_and_records_the_movement(): void
    {
        $user = $this->adminUser();
        $part = $this->createPart($user, 5);

        $response = $this->actingAs($user)->post(route('billing.store'), $this->documentData('invoice', $part, 2));

        $document = BillingDocument::query()->firstOrFail();
        $response->assertRedirect(route('billing.documents.show', $document));
        $this->assertSame(3, $part->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_part_id' => $part->id,
            'type' => 'used',
            'quantity_change' => -2,
            'quantity_after' => 3,
            'reference' => $document->document_number,
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

        $response = $this->actingAs($user)
            ->from(route('billing.create', ['type' => 'invoice']))
            ->post(route('billing.store'), $this->documentData('invoice', $part, 2));

        $response->assertRedirect(route('billing.create', ['type' => 'invoice']))
            ->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('billing_documents', 0);
        $this->assertSame(1, $part->fresh()->stock_quantity);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_quote_conversion_deducts_stock_and_payments_update_invoice_balance(): void
    {
        $user = $this->adminUser();
        $part = $this->createPart($user, 5);
        $this->actingAs($user)->post(route('billing.store'), $this->documentData('quote', $part, 2));
        $quote = BillingDocument::query()->firstOrFail();

        $this->actingAs($user)->post(route('billing.documents.convert', $quote))
            ->assertRedirect(route('billing.documents.show', $quote));
        $quote->refresh();
        $this->assertSame('invoice', $quote->type);
        $this->assertSame('issued', $quote->status);
        $this->assertSame(3, $part->fresh()->stock_quantity);

        $paymentData = [
            'amount' => 10,
            'method' => 'cash',
            'received_at' => now()->toDateString(),
        ];
        $this->actingAs($user)->post(route('billing.documents.payments.store', $quote), $paymentData)
            ->assertRedirect(route('billing.documents.show', $quote));
        $this->assertSame('partial', $quote->fresh()->status);
        $this->assertSame(10.0, $quote->fresh()->balance_due);

        $paymentData['amount'] = 10;
        $this->actingAs($user)->post(route('billing.documents.payments.store', $quote), $paymentData);
        $this->assertSame('paid', $quote->fresh()->status);
        $this->assertSame(0.0, $quote->fresh()->balance_due);
        $this->assertSame(1, InventoryMovement::query()->where('type', 'used')->count());
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
            ->assertSee('Create invoice')
            ->assertSee('Take photo');

        foreach (['quotation', 'invoice'] as $type) {
            $this->get(route('billing.create', ['type' => $type, 'project_id' => $project->id]))
                ->assertOk()
                ->assertSee('Website redesign')
                ->assertSee('Project customer');
            $this->post(route('billing.store'), [
                'type' => $type,
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
            ])->assertRedirect();
        }

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
            ->assertRedirect();
        $invoice = BillingDocument::query()->where('type', 'invoice')->firstOrFail();
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

    public function test_invoice_pdf_and_date_filtered_reports_are_available(): void
    {
        $user = $this->adminUser();
        $data = $this->documentData('invoice', null, 1);
        $data['customer_name'] = '=HYPERLINK("https://example.test","open")';
        $this->actingAs($user)->post(route('billing.store'), $data);
        $document = BillingDocument::query()->firstOrFail();
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
            ->assertOk()->assertSee('Spare parts')->assertDontSee('Recent stock movements');
        $this->actingAs($user)->get(route('inventory.movements'))
            ->assertOk()->assertSee('Stock movements')->assertSee('data-datatable', false);
        $this->actingAs($user)->getJson(route('inventory.movements.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()->assertJsonPath('recordsTotal', 0);
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
