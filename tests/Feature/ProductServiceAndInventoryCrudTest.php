<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\ProductService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductServiceAndInventoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_create_view_edit_and_delete_a_product_or_service(): void
    {
        $this->actingAs(User::factory()->create());

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
        ])->assertRedirect(route('products.show', $product));

        $this->get(route('products.delete', $product))->assertOk();
        $this->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));
        $this->assertSoftDeleted('product_services', ['id' => $product->id]);
    }

    public function test_inventory_crud_preserves_opening_movement_history_when_archiving_an_item(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('inventory.parts.store'), [
            'name' => 'USB-C adapter',
            'sku' => 'ADAPTER-USB-C',
            'description' => 'Multiport adapter',
            'cost_price' => 20,
            'selling_price' => 35,
            'stock_quantity' => 4,
            'reorder_level' => 1,
        ])->assertRedirect(route('inventory.parts'));

        $part = InventoryPart::query()->where('sku', 'ADAPTER-USB-C')->firstOrFail();
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
            'description' => 'Updated description',
            'cost_price' => 22,
            'selling_price' => 40,
            'reorder_level' => 2,
        ])->assertRedirect(route('inventory.parts.show', $part));
        $this->assertSame(4, $part->fresh()->stock_quantity);

        $this->get(route('inventory.parts.delete', $part))->assertOk();
        $this->delete(route('inventory.parts.destroy', $part))->assertRedirect(route('inventory.parts'));
        $this->assertSoftDeleted('inventory_parts', ['id' => $part->id]);
        $this->assertDatabaseHas('inventory_movements', ['inventory_part_id' => $part->id]);
        $this->assertSame(1, InventoryMovement::query()->where('inventory_part_id', $part->id)->count());
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
