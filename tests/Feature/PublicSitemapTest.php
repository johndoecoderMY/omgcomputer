<?php

namespace Tests\Feature;

use App\Models\InventoryPart;
use App\Models\WebsitePreview;
use Database\Seeders\WebsitePreviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicSitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_public_pages_and_only_available_products(): void
    {
        Storage::fake('public');
        $this->seed(WebsitePreviewSeeder::class);

        $availableProduct = InventoryPart::query()->create([
            'name' => 'Available product',
            'sku' => 'PUBLIC-SEO-01',
            'type' => 'other',
            'selling_price' => 10,
            'stock_quantity' => 1,
            'is_for_sale' => true,
        ]);
        $hiddenProduct = InventoryPart::query()->create([
            'name' => 'Hidden product',
            'sku' => 'HIDDEN-SEO-01',
            'type' => 'other',
            'selling_price' => 10,
            'stock_quantity' => 1,
            'is_for_sale' => false,
        ]);
        $outOfStockProduct = InventoryPart::query()->create([
            'name' => 'Out of stock product',
            'sku' => 'OUT-SEO-01',
            'type' => 'other',
            'selling_price' => 10,
            'stock_quantity' => 0,
            'is_for_sale' => true,
        ]);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false)
            ->assertSee('<loc>'.url('/').'</loc>', false)
            ->assertSee('<loc>'.route('shop.public').'</loc>', false)
            ->assertSee('<loc>'.route('website-previews.index').'</loc>', false)
            ->assertSee(route('website-previews.concepts.show', 'landing-page'), false)
            ->assertSee(route('shop.public.products.show', $availableProduct), false)
            ->assertDontSee(route('shop.public.products.show', $hiddenProduct), false)
            ->assertDontSee(route('shop.public.products.show', $outOfStockProduct), false);
    }

    public function test_sitemap_excludes_unpublished_concept_pages(): void
    {
        Storage::fake('public');
        $this->seed(WebsitePreviewSeeder::class);
        $dashboard = WebsitePreview::query()->where('title', 'Demo concept: Analytics dashboard')->firstOrFail();
        $dashboard->update(['is_published' => false]);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('website-previews.concepts.show', 'landing-page'), false)
            ->assertDontSee(route('website-previews.concepts.show', 'dashboard'), false);
    }
}
