<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\ProductService;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceAndInventoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_view_edit_and_delete_a_product_or_service(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->post(route('products.store'), [
            'name' => 'Custom PC setup',
            'category' => 'repair',
            'scope' => 'Assembly and setup',
            'audience' => 'PC builders',
            'price' => 'RM100–200',
            'billing_price' => 100,
        ])->assertRedirect();

        $product = ProductService::query()->where('name', 'Custom PC setup')->firstOrFail();
        $this->get(route('products.show', $product))->assertOk()->assertSee('Assembly and setup');
        $this->get(route('products.edit', $product))->assertOk();
        $this->put(route('products.update', $product), [
            'name' => 'Custom PC assembly',
            'category' => 'repair',
            'scope' => 'Assembly, setup and testing',
            'audience' => 'PC builders',
            'price' => 'RM120–220',
            'billing_price' => 120,
            'is_active' => '0',
        ])->assertRedirect(route('products.show', $product));
        $this->assertFalse($product->fresh()->is_active);
        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Custom PC assembly')
            ->assertSee('Disabled');
        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Disabled — hidden from public booking');

        $this->put(route('products.update', $product), [
            'name' => 'Custom PC assembly',
            'category' => 'repair',
            'scope' => 'Assembly, setup and testing',
            'audience' => 'PC builders',
            'price' => 'RM120–220',
            'billing_price' => 120,
            'is_active' => '1',
        ])->assertRedirect(route('products.show', $product));
        $this->assertTrue($product->fresh()->is_active);

        $this->get(route('products.delete', $product))->assertOk();
        $this->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));
        $this->assertSoftDeleted('product_services', ['id' => $product->id]);
    }

    public function test_disabled_services_are_hidden_from_public_bookings_and_rejected_if_submitted(): void
    {
        $service = ProductService::query()->where('is_active', true)->firstOrFail();
        $service->update(['is_active' => false]);

        $this->get(route('bookings.create'))
            ->assertOk()
            ->assertDontSee('value="'.$service->id.'"', false)
            ->assertDontSee($service->name);

        $this->from(route('bookings.create'))
            ->post(route('bookings.store'), [
                'customer_name' => 'Booking Customer',
                'customer_email' => 'booking-customer@example.test',
                'customer_phone' => '+60123456789',
                'service_id' => $service->id,
                'booking_date' => now()->addDays(2)->toDateString(),
                'start_time' => '11:00',
            ])
            ->assertRedirect(route('bookings.create'))
            ->assertSessionHasErrors('service_id');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_inventory_crud_preserves_opening_movement_history_when_archiving_an_item(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);

        $this->post(route('inventory.parts.store'), [
            'name' => 'USB-C adapter',
            'sku' => 'ADAPTER-USB-C',
            'type' => 'gpu',
            'description' => 'Multiport adapter',
            'cost_price' => 20,
            'selling_price' => 35,
            'stock_quantity' => 4,
            'reorder_level' => 1,
        ])->assertRedirect(route('inventory.parts'));

        $part = InventoryPart::query()->where('sku', 'ADAPTER-USB-C')->firstOrFail();
        $this->assertSame('gpu', $part->type);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_part_id' => $part->id,
            'quantity_change' => 4,
            'quantity_after' => 4,
        ]);
        $this->get(route('inventory.parts.show', $part))->assertOk()->assertSee('Multiport adapter');
        $this->get(route('inventory.parts.edit', $part))->assertOk();
        $this->put(route('inventory.parts.update', $part), [
            'name' => 'USB-C hub',
            'sku' => 'ADAPTER-USB-C',
            'type' => 'cpu',
            'description' => 'Updated description',
            'cost_price' => 22,
            'selling_price' => 40,
            'reorder_level' => 2,
        ])->assertRedirect(route('inventory.parts.show', $part));
        $this->assertSame('cpu', $part->fresh()->type);
        $this->assertSame(4, $part->fresh()->stock_quantity);

        $this->get(route('inventory.parts.delete', $part))->assertOk();
        $this->delete(route('inventory.parts.destroy', $part))->assertRedirect(route('inventory.parts'));
        $this->assertSoftDeleted('inventory_parts', ['id' => $part->id]);
        $this->assertDatabaseHas('inventory_movements', ['inventory_part_id' => $part->id]);
        $this->assertSame(1, InventoryMovement::query()->where('inventory_part_id', $part->id)->count());
    }

    public function test_parts_page_filters_by_name_sku_and_component_type(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('admin');
        InventoryPart::query()->create([
            'name' => 'Gaming graphics card',
            'sku' => 'GPU-RTX-01',
            'type' => 'gpu',
        ]);
        InventoryPart::query()->create([
            'name' => 'Desktop memory kit',
            'sku' => 'RAM-DDR5-01',
            'type' => 'ram',
        ]);

        $this->actingAs($user)->get(route('inventory.parts'))
            ->assertOk()
            ->assertSee('Parts')
            ->assertSee('id="parts-filters-modal"', false)
            ->assertSee('name="filter[name]"', false)
            ->assertSee('name="filter[sku]"', false)
            ->assertSee('GPU')
            ->assertSee('RAM');

        $this->getJson(route('inventory.parts.datatable', [
            'filter' => ['name' => 'graphics', 'type' => 'gpu'],
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Gaming graphics card')
            ->assertJsonPath('data.0.type_label', 'GPU');

        $this->getJson(route('inventory.parts.datatable', [
            'filter' => ['sku' => 'DDR5', 'type' => 'ram'],
            'draw' => 1,
            'start' => 0,
            'length' => 25,
        ]))->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.sku', 'RAM-DDR5-01');
    }

    public function test_public_sitemap_contains_only_public_site_pages(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee(route('bookings.create'))
            ->assertSee(route('privacy'))
            ->assertDontSee(route('products.index'));
    }
}
