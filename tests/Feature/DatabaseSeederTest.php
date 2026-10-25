<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\CustomerMessageTemplate;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\MarketingCampaign;
use App\Models\RepairTicket;
use App\Models\Supplier;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeders_populate_all_business_areas_and_can_run_repeatedly(): void
    {
        $this->seed();

        $counts = [
            'customers' => Customer::query()->count(),
            'inventory_parts' => InventoryPart::query()->count(),
            'suppliers' => Supplier::query()->count(),
            'repair_tickets' => RepairTicket::query()->count(),
            'billing_documents' => BillingDocument::query()->count(),
            'marketing_campaigns' => MarketingCampaign::query()->count(),
            'bookings' => Booking::query()->count(),
            'tasks' => Task::query()->count(),
            'customer_message_templates' => CustomerMessageTemplate::query()->count(),
        ];

        $this->assertGreaterThan(0, $counts['customers']);
        $this->assertGreaterThan(0, $counts['inventory_parts']);
        $this->assertGreaterThan(0, InventoryPart::query()->where('type', 'ready_built_pc')->count());
        $this->assertDatabaseHas('inventory_parts', [
            'sku' => 'DEMO-PC-OFFICE-001',
            'type' => 'ready_built_pc',
            'is_for_sale' => true,
            'stock_quantity' => 2,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'reference' => 'SEED-OPENING-DEMO-PC-OFFICE-001',
            'type' => 'received',
            'quantity_change' => 2,
        ]);
        $this->get(route('shop.public', ['type' => 'ready_built_pc']))
            ->assertOk()
            ->assertSee('Ready-built Office PC')
            ->assertSee('Ready-built 1080p Gaming PC')
            ->assertSee('Ready-built Performance Gaming PC');
        $this->assertGreaterThan(0, $counts['suppliers']);
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Demo PC Components Supply',
            'website' => 'https://example.com/pc-components',
        ]);
        $seededPartTypes = InventoryPart::query()->distinct()->pluck('type')->all();
        sort($seededPartTypes);
        $expectedPartTypes = array_keys(InventoryPart::TYPES);
        sort($expectedPartTypes);
        $this->assertSame($expectedPartTypes, $seededPartTypes);
        $this->assertGreaterThan(0, $counts['repair_tickets']);
        $this->assertGreaterThan(0, $counts['billing_documents']);
        $this->assertGreaterThan(0, $counts['marketing_campaigns']);
        $this->assertGreaterThan(0, $counts['bookings']);
        $this->assertGreaterThan(0, $counts['tasks']);
        $this->assertGreaterThan(0, $counts['customer_message_templates']);

        $this->seed();
        $this->assertSame(
            3,
            InventoryPart::query()->whereIn('sku', [
                'DEMO-PC-OFFICE-001',
                'DEMO-PC-GAMING-001',
                'DEMO-PC-GAMING-002',
            ])->count(),
        );
        $this->assertSame(1, InventoryMovement::query()->where('reference', 'SEED-OPENING-DEMO-PC-OFFICE-001')->count());
        $this->assertSame($counts, [
            'customers' => Customer::query()->count(),
            'inventory_parts' => InventoryPart::query()->count(),
            'suppliers' => Supplier::query()->count(),
            'repair_tickets' => RepairTicket::query()->count(),
            'billing_documents' => BillingDocument::query()->count(),
            'marketing_campaigns' => MarketingCampaign::query()->count(),
            'bookings' => Booking::query()->count(),
            'tasks' => Task::query()->count(),
            'customer_message_templates' => CustomerMessageTemplate::query()->count(),
        ]);
    }
}
