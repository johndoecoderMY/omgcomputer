<?php

namespace Tests\Feature;

use App\Models\BillingDocument;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\CustomerMessageTemplate;
use App\Models\CustomPcComponent;
use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\MarketingCampaign;
use App\Models\RepairTicket;
use App\Models\ShopOrder;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\User;
use App\Models\WebsitePreview;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeder_keeps_operational_records_empty_and_can_run_repeatedly(): void
    {
        config(['auth.seed_admin_password' => 'SeedAdmin!Test-Password-2026']);
        $this->seed();

        $this->assertSame(10, WebsitePreview::query()->count());
        $websitePreviewsPage = $this->get(route('website-previews.index'))
            ->assertOk();
        foreach (config('website-demos') as $demo) {
            $websitePreviewsPage->assertSee($demo['title']);
        }
        foreach (config('website-preview-concepts') as $concept) {
            $this->assertDatabaseMissing('website_previews', ['title' => $concept['title']]);
            $this->assertDatabaseMissing('website_previews', ['title' => $concept['legacy_title']]);
            $websitePreviewsPage->assertDontSee($concept['title']);
        }

        $counts = [
            'users' => User::query()->count(),
            'customers' => Customer::query()->count(),
            'inventory_parts' => InventoryPart::query()->count(),
            'custom_pc_components' => CustomPcComponent::query()->count(),
            'suppliers' => Supplier::query()->count(),
            'repair_tickets' => RepairTicket::query()->count(),
            'billing_documents' => BillingDocument::query()->count(),
            'marketing_campaigns' => MarketingCampaign::query()->count(),
            'bookings' => Booking::query()->count(),
            'shop_orders' => ShopOrder::query()->count(),
            'tasks' => Task::query()->count(),
            'customer_message_templates' => CustomerMessageTemplate::query()->count(),
        ];

        $admin = User::query()->firstOrFail();
        $this->assertSame('support@omgcomputer.my', $admin->email);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->force_password_change);
        $this->assertTrue(Hash::check('SeedAdmin!Test-Password-2026', $admin->password));
        $this->assertSame(1, $counts['users']);
        $this->assertGreaterThan(0, $counts['customers']);
        $this->assertGreaterThan(0, $counts['inventory_parts']);
        $this->assertGreaterThan(0, InventoryPart::query()->where('type', 'ready_built_pc')->count());
        $this->assertSame(66778, CustomPcComponent::query()->where('sku', 'like', 'CSV-%')->count());
        $this->assertSame(0, InventoryPart::query()->where('sku', 'like', 'CSV-%')->count());
        $cpuFromCsv = CustomPcComponent::query()->where('sku', 'CSV-CPU-000001')->firstOrFail();
        $this->assertSame('AMD Ryzen 7 9800X3D', $cpuFromCsv->name);
        $this->assertSame('cpu', $cpuFromCsv->type);
        $this->assertSame(451.5, (float) $cpuFromCsv->catalog_price);
        $this->assertSame('USD', $cpuFromCsv->catalog_currency);
        $this->assertStringContainsString('Price (USD): 451.5', $cpuFromCsv->description);
        $this->assertSame(0, InventoryPart::query()->where('sku', $cpuFromCsv->sku)->count());
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
        $this->assertContains('ready_built_pc', $seededPartTypes);
        $this->assertNotContains('cpu', $seededPartTypes);
        $this->assertSame(0, $counts['repair_tickets']);
        $this->assertSame(0, $counts['billing_documents']);
        $this->assertGreaterThan(0, $counts['marketing_campaigns']);
        $this->assertSame(0, $counts['bookings']);
        $this->assertSame(0, $counts['shop_orders']);
        $this->assertGreaterThan(0, $counts['tasks']);
        $this->assertGreaterThan(0, $counts['customer_message_templates']);

        $admin->forceFill(['force_password_change' => false])->save();
        $admin->forceFill(['password' => Hash::make('NewAdmin!Password-2026')])->save();
        $this->seed();
        $this->assertFalse($admin->fresh()->force_password_change);
        $this->assertTrue(Hash::check('NewAdmin!Password-2026', $admin->fresh()->password));
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
            'users' => User::query()->count(),
            'customers' => Customer::query()->count(),
            'inventory_parts' => InventoryPart::query()->count(),
            'custom_pc_components' => CustomPcComponent::query()->count(),
            'suppliers' => Supplier::query()->count(),
            'repair_tickets' => RepairTicket::query()->count(),
            'billing_documents' => BillingDocument::query()->count(),
            'marketing_campaigns' => MarketingCampaign::query()->count(),
            'bookings' => Booking::query()->count(),
            'shop_orders' => ShopOrder::query()->count(),
            'tasks' => Task::query()->count(),
            'customer_message_templates' => CustomerMessageTemplate::query()->count(),
        ]);
    }
}
