<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryPart;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use App\Services\ChipCollect;
use Database\Seeders\PcPartsSeeder;
use Database\Seeders\PlannerRoleSeeder;
use Database\Seeders\ShopDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_pages_are_hidden_from_guests_and_customer_accounts(): void
    {
        $this->get(route('shop.products.index'))->assertRedirect(route('login'));
        $this->get(route('shop.cart'))->assertRedirect(route('login'));

        $this->seed(PlannerRoleSeeder::class);
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)->get(route('shop.products.index'))->assertForbidden();
    }

    public function test_guests_can_browse_read_only_shop_preview_pages(): void
    {
        $availablePart = InventoryPart::query()->create([
            'name' => 'Public preview graphics card',
            'sku' => 'PRIVATE-SKU-01',
            'type' => 'gpu',
            'description' => 'A graphics card for the preview catalogue.',
            'cost_price' => 400,
            'selling_price' => 650,
            'stock_quantity' => 3,
            'reorder_level' => 1,
            'is_for_sale' => true,
        ]);
        $readyBuiltPc = InventoryPart::query()->create([
            'name' => 'Ready-built gaming PC',
            'sku' => 'PC-READY-001',
            'type' => 'ready_built_pc',
            'description' => 'A ready-to-use gaming desktop PC.',
            'cost_price' => 2200,
            'selling_price' => 2899,
            'stock_quantity' => 2,
            'reorder_level' => 1,
            'is_for_sale' => true,
        ]);
        $hiddenPart = InventoryPart::query()->create([
            'name' => 'Internal hidden part',
            'sku' => 'INTERNAL-01',
            'type' => 'other',
            'selling_price' => 10,
            'cost_price' => 5,
            'stock_quantity' => 2,
            'reorder_level' => 1,
            'is_for_sale' => false,
        ]);
        $outOfStockPart = InventoryPart::query()->create([
            'name' => 'Unavailable preview part',
            'sku' => 'OUT-OF-STOCK-01',
            'type' => 'other',
            'selling_price' => 10,
            'cost_price' => 5,
            'stock_quantity' => 0,
            'reorder_level' => 1,
            'is_for_sale' => true,
        ]);

        $this->get(route('shop.preview.products.index'))
            ->assertOk()
            ->assertSee('OMG Computer shop')
            ->assertSee('Parts and PCs for your setup.')
            ->assertSee('Public preview graphics card')
            ->assertSee('Ready-built gaming PC')
            ->assertSee('Ready-built PC')
            ->assertSee('Get a custom PC quote')
            ->assertSee('https://wa.me/601123881290')
            ->assertDontSee('PRIVATE-SKU-01')
            ->assertDontSee('Internal hidden part')
            ->assertDontSee('Unavailable preview part')
            ->assertDontSee('Add to cart');
        $this->get(route('shop.preview.products.grid'))
            ->assertOk()
            ->assertSee('OMG Computer shop')
            ->assertSee('Public preview graphics card')
            ->assertSee('Ready-built gaming PC')
            ->assertSee('Get a custom PC quote')
            ->assertDontSee('Add to cart');
        $this->get(route('shop.preview.products.grid', ['type' => 'ready_built_pc']))
            ->assertOk()
            ->assertSee('Ready-built gaming PC')
            ->assertDontSee('Public preview graphics card');
        $this->get(route('shop.preview.products.show', $availablePart))
            ->assertOk()
            ->assertSee('Product information')
            ->assertSee('A graphics card for the preview catalogue.')
            ->assertSee('RM 650.00')
            ->assertDontSee('400.00')
            ->assertDontSee('PRIVATE-SKU-01')
            ->assertSee('Sign in to buy')
            ->assertSee(route('shop.products.purchase', $availablePart), false)
            ->assertSee('Get a custom PC quote')
            ->assertDontSee('Add to cart');
        $this->get(route('shop.preview.products.show', $readyBuiltPc))
            ->assertOk()
            ->assertSee('Ready-built gaming PC')
            ->assertSee('Ready-built PC')
            ->assertSee('RM 2,899.00')
            ->assertSee('Get a custom PC quote');
        $this->get(route('shop.preview.products.show', $hiddenPart))->assertNotFound();
        $this->get(route('shop.preview.products.show', $outOfStockPart))->assertNotFound();
    }

    public function test_public_site_links_to_the_guest_shop(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('shop.preview.products.grid'), false);

        $this->get(route('shop.preview.products.index'))
            ->assertOk()
            ->assertSee('Shop computer parts')
            ->assertSee(route('shop.preview.products.grid'), false);
    }

    public function test_product_list_and_grid_pagination_uses_consistent_navigation_for_guests_and_staff(): void
    {
        foreach (range(1, 15) as $index) {
            InventoryPart::query()->create([
                'name' => "Pagination SSD {$index}",
                'sku' => "PAGE-SSD-{$index}",
                'type' => 'storage',
                'description' => 'Product pagination test item.',
                'selling_price' => 100 + $index,
                'cost_price' => 50,
                'stock_quantity' => 5,
                'reorder_level' => 1,
                'is_for_sale' => true,
            ]);
        }

        $filters = ['search' => 'Pagination SSD', 'type' => 'storage', 'sort' => 'name'];
        foreach (['shop.preview.products.index', 'shop.preview.products.grid'] as $routeName) {
            $this->get(route($routeName, $filters))
                ->assertOk()
                ->assertSee('aria-label="Product pagination"', false)
                ->assertSeeText('Showing 1 to 12 of 15 results')
                ->assertSee('rel="next"', false)
                ->assertSee('page=2', false)
                ->assertSee('type=storage', false)
                ->assertDontSee('sm:hidden')
                ->assertDontSee('<svg', false);

            $this->get(route($routeName, [...$filters, 'page' => 2]))
                ->assertOk()
                ->assertSeeText('Showing 13 to 15 of 15 results')
                ->assertSee('rel="prev"', false)
                ->assertSee('Pagination SSD');
        }

        $staff = $this->staffUser();
        $this->actingAs($staff)
            ->get(route('shop.products.grid', $filters))
            ->assertOk()
            ->assertSee('aria-label="Product pagination"', false)
            ->assertSeeText('Showing 1 to 12 of 15 results')
            ->assertSee('page=2', false)
            ->assertSee('type=storage', false)
            ->assertDontSee('<svg', false);
    }

    public function test_staff_can_list_a_ready_built_pc_for_sale(): void
    {
        $staff = $this->staffUser();

        $this->actingAs($staff)->post(route('shop.products.store'), [
            'name' => 'Office PC package',
            'sku' => 'PC-OFFICE-001',
            'type' => 'ready_built_pc',
            'description' => 'A ready-built desktop for office work.',
            'cost_price' => 1400,
            'selling_price' => 1799,
            'stock_quantity' => 3,
            'reorder_level' => 1,
            'is_for_sale' => '1',
        ])->assertRedirect(route('shop.products.index'));

        $this->assertDatabaseHas('inventory_parts', [
            'sku' => 'PC-OFFICE-001',
            'type' => 'ready_built_pc',
            'is_for_sale' => true,
            'stock_quantity' => 3,
        ]);

        $this->get(route('shop.preview.products.grid', ['type' => 'ready_built_pc']))
            ->assertOk()
            ->assertSee('Office PC package')
            ->assertSee('RM 1,799.00')
            ->assertSee('Get a custom PC quote');
    }

    public function test_sign_in_to_buy_returns_to_the_shop_product(): void
    {
        $staff = $this->staffUser();
        $product = InventoryPart::query()->create([
            'name' => 'Sign-in purchase product',
            'sku' => 'SHOP-SIGNIN-01',
            'type' => 'other',
            'selling_price' => 100,
            'cost_price' => 50,
            'stock_quantity' => 2,
            'reorder_level' => 1,
            'is_for_sale' => true,
        ]);

        $this->get(route('shop.products.purchase', $product))
            ->assertRedirect(route('login'));
        $this->post(route('login'), [
            'email' => $staff->email,
            'password' => 'password',
        ])->assertRedirect(route('shop.products.purchase', $product));
        $this->get(route('shop.products.purchase', $product))
            ->assertRedirect(route('shop.products.show', $product));
    }

    public function test_staff_can_manage_products_browse_paginated_list_and_grid(): void
    {
        $user = $this->staffUser();
        $this->actingAs($user)->get(route('shop.products.create'))
            ->assertOk()
            ->assertSee('Opening stock')
            ->assertSee('Ready-built PC');
        $this->actingAs($user)->post(route('shop.products.store'), [
            'name' => 'NVMe SSD',
            'sku' => 'SHOP-SSD-01',
            'type' => 'storage',
            'description' => 'Fast storage.',
            'cost_price' => 80,
            'selling_price' => 120,
            'stock_quantity' => 5,
            'reorder_level' => 1,
            'is_for_sale' => '1',
        ])->assertRedirect(route('shop.products.index'));

        $product = InventoryPart::query()->firstOrFail();
        $this->assertTrue($product->is_for_sale);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_part_id' => $product->id,
            'quantity_change' => 5,
            'quantity_after' => 5,
        ]);
        $this->actingAs($user)->get(route('shop.products.edit', $product))
            ->assertOk()
            ->assertSee('Current stock');
        $this->actingAs($user)->put(route('shop.products.update', $product), [
            'name' => $product->name,
            'sku' => $product->sku,
            'type' => $product->type,
            'description' => 'Fast storage with warranty.',
            'cost_price' => 80,
            'selling_price' => 120,
            'reorder_level' => 1,
            'is_for_sale' => '1',
        ])->assertRedirect(route('shop.products.show', $product));

        $this->actingAs($user)->get(route('shop.products.index'))
            ->assertOk()
            ->assertSee('id="shop-products-table"', false)
            ->assertSee('data-datatable', false)
            ->assertSee(route('shop.products.datatable'), false)
            ->assertSee('shop-stock-modal', false)
            ->assertSee(route('shop.products.grid'), false);
        $this->actingAs($user)->getJson(route('shop.products.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.sku', 'SHOP-SSD-01')
            ->assertJsonPath('data.0.actions', fn (string $actions): bool => str_contains($actions, 'data-bs-target="#shop-stock-modal"'));
        $this->actingAs($user)->get(route('shop.products.grid'))
            ->assertOk()
            ->assertDontSee('id="shop-products-grid-table"', false)
            ->assertSee('NVMe SSD')
            ->assertSee('Add to cart');
        $this->actingAs($user)->get(route('shop.products.grid', ['type' => 'storage']))
            ->assertOk()
            ->assertSee('NVMe SSD');
        $this->actingAs($user)->get(route('shop.products.show', $product))
            ->assertOk()
            ->assertSee('Fast storage with warranty.');
    }

    public function test_staff_can_create_chip_sandbox_order_from_cart_and_orders_use_datatables(): void
    {
        Notification::fake();
        $user = $this->staffUser();
        $notifiedAdmin = User::factory()->create();
        $notifiedAdmin->assignRole('admin');
        $customer = Customer::query()->create([
            'name' => 'Shop customer',
            'email' => 'shop@example.test',
            'phone' => '0123456789',
        ]);
        $product = InventoryPart::query()->create([
            'name' => 'USB keyboard',
            'sku' => 'SHOP-KEY-01',
            'type' => 'peripheral',
            'selling_price' => 45,
            'cost_price' => 20,
            'stock_quantity' => 4,
            'reorder_level' => 1,
            'is_for_sale' => true,
        ]);
        config([
            'services.chip.enabled' => true,
            'services.chip.secret_key' => 'test-secret',
            'services.chip.brand_id' => 'test-brand',
            'services.chip.api_url' => 'https://gate.chip-in.asia/api/v1',
            'services.chip.currency' => 'MYR',
        ]);
        Http::fake([
            'gate.chip-in.asia/api/v1/purchases/' => Http::response([
                'id' => 'purchase-shop-1',
                'checkout_url' => 'https://gate.chip-in.asia/checkout/shop-1',
            ]),
            'gate.chip-in.asia/api/v1/purchases/purchase-shop-1/' => Http::response([
                'id' => 'purchase-shop-1',
                'status' => 'failed',
            ]),
        ]);
        $this->actingAs($user);

        $this->post(route('shop.cart.add', $product), ['quantity' => 2])
            ->assertRedirect(route('shop.cart'));
        $this->get(route('shop.cart'))->assertOk()->assertSee('USB keyboard');
        $this->get(route('shop.checkout'))->assertOk()->assertSee('Order summary');
        $this->post(route('shop.checkout.place'), [
            'customer_id' => $customer->id,
            'customer_address' => '1 Main Street',
        ])->assertRedirect('https://gate.chip-in.asia/checkout/shop-1');
        Notification::assertSentTo(
            $notifiedAdmin,
            WorkspaceNotification::class,
            fn (WorkspaceNotification $notification): bool => $notification->title === 'New shop order',
        );

        $order = ShopOrder::query()->with('items')->firstOrFail();
        $this->assertSame('SHOP-'.now()->format('Y').'-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT), $order->order_number);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(90.0, (float) $order->total);
        $this->assertSame(2, $order->items->firstOrFail()->quantity);
        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_part_id' => $product->id,
            'reference' => $order->order_number,
            'quantity_change' => -2,
        ]);

        $this->get(route('shop.orders.index'))->assertOk()->assertSee('data-datatable', false);
        $this->getJson(route('shop.orders.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.order', fn (string $orderLink): bool => str_contains($orderLink, $order->order_number));
        $this->get(route('shop.orders.show', $order))->assertOk()->assertSee('Shop customer');
        $this->get(route('shop.orders.status-board'))->assertOk()->assertSee('data-datatable', false);
        $this->getJson(route('shop.orders.status-board.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()->assertJsonPath('recordsTotal', 1);
        $this->get(route('shop.orders.customer-preview'))
            ->assertOk()
            ->assertSee('This staff-only preview')
            ->assertSee('SHOP-DEMO-ORDER-003');
        $this->get(route('shop.chip.return', $order->uuid))
            ->assertRedirect(route('shop.orders.show', $order))
            ->assertSessionHas('info');
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame(4, $product->fresh()->stock_quantity);
        $this->assertFalse($order->fresh()->stock_reserved);
        $this->get(route('shop.chip.return', $order->uuid));
        $this->assertSame(4, $product->fresh()->stock_quantity);
    }

    public function test_chip_callback_marks_shop_order_paid_idempotently(): void
    {
        Notification::fake();
        $actor = $this->staffUser();
        $notifiedAdmin = User::factory()->create();
        $notifiedAdmin->assignRole('admin');
        $product = InventoryPart::query()->create([
            'name' => 'Reserved mouse',
            'sku' => 'SHOP-MOUSE-01',
            'type' => 'peripheral',
            'selling_price' => 35,
            'cost_price' => 15,
            'stock_quantity' => 2,
            'reorder_level' => 1,
            'is_for_sale' => true,
        ]);
        $order = ShopOrder::query()->create([
            'uuid' => (string) Str::uuid(),
            'order_number' => 'SHOP-'.now()->format('Y').'-000001',
            'customer_name' => 'Callback customer',
            'customer_email' => 'callback@example.test',
            'user_id' => $actor->id,
            'status' => 'pending_payment',
            'payment_status' => 'pending',
            'stock_reserved' => true,
            'provider_purchase_id' => 'chip-shop-callback-1',
            'subtotal' => 35,
            'total' => 35,
        ]);
        $order->items()->create([
            'inventory_part_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 1,
            'unit_price' => 35,
            'line_total' => 35,
        ]);
        $body = json_encode(['id' => 'chip-shop-callback-1'], JSON_THROW_ON_ERROR);
        $this->mock(ChipCollect::class, function ($mock) use ($body): void {
            $mock->shouldReceive('verifyCallback')->twice()->with($body, 'valid-signature')->andReturnTrue();
            $mock->shouldReceive('purchase')->twice()->with('chip-shop-callback-1')->andReturn([
                'id' => 'chip-shop-callback-1',
                'status' => 'paid',
            ]);
        });
        $callback = fn () => $this->call(
            'POST',
            route('webhooks.chip.shop', $order->uuid),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => 'valid-signature'],
            $body,
        );

        $callback()->assertOk();
        $callback()->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertFalse($order->fresh()->stock_reserved);
        Notification::assertSentTo(
            $notifiedAdmin,
            WorkspaceNotification::class,
            fn (WorkspaceNotification $notification): bool => $notification->title === 'Shop order paid',
        );
    }

    public function test_paid_order_status_updates_notify_other_admins(): void
    {
        Notification::fake();
        $actor = $this->staffUser();
        $recipient = User::factory()->create();
        $recipient->assignRole('admin');
        $order = ShopOrder::query()->create([
            'uuid' => (string) Str::uuid(),
            'order_number' => 'SHOP-'.now()->format('Y').'-000123',
            'customer_name' => 'Status customer',
            'customer_email' => 'status@example.test',
            'user_id' => $actor->id,
            'status' => 'paid',
            'payment_status' => 'paid',
            'stock_reserved' => false,
            'subtotal' => 20,
            'total' => 20,
        ]);

        $this->actingAs($actor)->patch(route('shop.orders.status', $order), ['status' => 'processing'])
            ->assertRedirect(route('shop.orders.status-board'));

        $this->assertSame('processing', $order->fresh()->status);
        Notification::assertSentTo(
            $recipient,
            WorkspaceNotification::class,
            fn (WorkspaceNotification $notification): bool => $notification->title === 'Shop order status updated',
        );
        Notification::assertNotSentTo($actor, WorkspaceNotification::class);
    }

    public function test_shop_demo_seeder_populates_sample_orders_idempotently(): void
    {
        $staff = User::factory()->create(['email' => 'test@example.com']);
        foreach ([
            ['name' => 'Avery Tan', 'email' => 'avery.tan@example.test'],
            ['name' => 'Daniel Lim', 'email' => 'daniel.lim@example.test'],
        ] as $customer) {
            Customer::query()->create($customer);
        }
        foreach ([
            ['sku' => 'DEMO-CPU-001', 'name' => 'Desktop processor'],
            ['sku' => 'DEMO-RAM-001', 'name' => 'DDR5 memory kit'],
            ['sku' => 'DEMO-NVME-001', 'name' => '512GB NVMe SSD'],
            ['sku' => 'DEMO-KB-001', 'name' => 'USB keyboard'],
            ['sku' => 'DEMO-MOUSE-001', 'name' => 'USB optical mouse'],
        ] as $part) {
            InventoryPart::query()->create([
                ...$part,
                'type' => 'other',
                'selling_price' => 50,
                'cost_price' => 25,
                'stock_quantity' => 5,
                'reorder_level' => 1,
                'is_for_sale' => true,
            ]);
        }

        $this->seed(ShopDemoSeeder::class);
        $this->seed(ShopDemoSeeder::class);

        $this->assertSame(5, ShopOrder::query()->where('order_number', 'like', 'SHOP-DEMO-ORDER-%')->count());
        $this->assertDatabaseHas('shop_orders', [
            'order_number' => 'SHOP-DEMO-ORDER-003',
            'status' => 'processing',
            'payment_status' => 'paid',
        ]);
        $this->assertSame(5, ShopOrderItem::query()->count());
        $this->assertSame(25, InventoryPart::query()->sum('stock_quantity'));
        $this->assertNotNull($staff->fresh());
    }

    public function test_pc_parts_seeder_lists_existing_sample_inventory_for_sale(): void
    {
        $part = InventoryPart::query()->create([
            'sku' => 'DEMO-CPU-001',
            'name' => 'Existing processor stock',
            'type' => 'cpu',
            'selling_price' => 799,
            'cost_price' => 650,
            'stock_quantity' => 5,
            'reorder_level' => 2,
            'is_for_sale' => false,
        ]);

        $this->seed(PcPartsSeeder::class);

        $this->assertTrue($part->fresh()->is_for_sale);
        $this->assertSame(5, $part->fresh()->stock_quantity);
    }

    public function test_staff_can_add_and_remove_part_quantity_from_the_product_list_modal(): void
    {
        $user = $this->staffUser();
        $part = InventoryPart::query()->create([
            'name' => 'Modal stock part',
            'sku' => 'SHOP-MODAL-001',
            'type' => 'other',
            'selling_price' => 15,
            'cost_price' => 8,
            'stock_quantity' => 4,
            'reorder_level' => 1,
            'is_for_sale' => true,
        ]);
        $this->actingAs($user)
            ->get(route('shop.products.index'))
            ->assertOk()
            ->assertSee('id="shop-stock-modal"', false);
        $this->actingAs($user)->getJson(route('shop.products.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonPath('data.0.actions', fn (string $actions): bool => str_contains($actions, 'Adjust stock'));

        $this->post(route('shop.products.stock', $part), [
            'direction' => 'receive',
            'quantity' => 3,
            'note' => 'Restocked',
            'view' => 'list',
            'search' => 'Modal',
            'type' => 'other',
            'sale' => 'yes',
            'sort' => 'name',
        ])->assertRedirect(route('shop.products.index', [
            'search' => 'Modal',
            'type' => 'other',
            'sale' => 'yes',
            'sort' => 'name',
        ]));

        $this->assertSame(7, $part->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_part_id' => $part->id,
            'type' => 'received',
            'quantity_change' => 3,
            'quantity_after' => 7,
            'note' => 'Restocked',
        ]);

        $this->post(route('shop.products.stock', $part), [
            'direction' => 'use',
            'quantity' => 2,
            'note' => 'Damaged',
            'view' => 'grid',
        ])->assertRedirect(route('shop.products.grid'));

        $this->assertSame(5, $part->fresh()->stock_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_part_id' => $part->id,
            'type' => 'used',
            'quantity_change' => -2,
            'quantity_after' => 5,
            'note' => 'Damaged',
        ]);

        $this->from(route('shop.products.index'))->post(route('shop.products.stock', $part), [
            'direction' => 'use',
            'quantity' => 6,
            'view' => 'list',
        ])->assertRedirect(route('shop.products.index'))->assertSessionHasErrors('quantity');
        $this->assertSame(5, $part->fresh()->stock_quantity);
    }

    public function test_staff_navbar_always_shows_the_shopping_cart_item_count(): void
    {
        $user = $this->staffUser();
        $product = InventoryPart::query()->create([
            'name' => 'USB mouse',
            'sku' => 'SHOP-MOUSE-01',
            'type' => 'peripheral',
            'selling_price' => 25,
            'cost_price' => 10,
            'stock_quantity' => 4,
            'reorder_level' => 1,
            'is_for_sale' => true,
        ]);

        $this->actingAs($user)->get(route('shop.cart'))
            ->assertOk()
            ->assertSee('Shopping cart, 0 items')
            ->assertSee('class="bi bi-cart3"', false)
            ->assertDontSee('data-fa-transform', false)
            ->assertSee('notification-indicator-number', false);

        $this->post(route('shop.cart.add', $product), ['quantity' => 2])
            ->assertRedirect(route('shop.cart'));

        $response = $this->get(route('shop.cart'));
        $response->assertOk()
            ->assertSee('Shopping cart, 2 items');
        $this->assertMatchesRegularExpression(
            '/class="notification-indicator-number"[^>]*>\s*2\s*</',
            $response->getContent(),
        );
    }

    public function test_adding_a_product_asynchronously_returns_the_updated_cart_count(): void
    {
        $user = $this->staffUser();
        $product = InventoryPart::query()->create([
            'name' => 'USB webcam',
            'sku' => 'SHOP-CAM-01',
            'type' => 'peripheral',
            'selling_price' => 75,
            'cost_price' => 35,
            'stock_quantity' => 5,
            'reorder_level' => 1,
            'is_for_sale' => true,
        ]);

        $this->actingAs($user)->postJson(route('shop.cart.add', $product), ['quantity' => 2])
            ->assertOk()
            ->assertJsonPath('message', 'Product added to cart.')
            ->assertJsonPath('cartCount', 2);
    }

    private function staffUser(): User
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }
}
