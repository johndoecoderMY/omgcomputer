<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsitePreview;
use Database\Seeders\PlannerRoleSeeder;
use Database\Seeders\WebsitePreviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsitePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_concept_seeder_publishes_demo_previews_and_copies_images_idempotently(): void
    {
        Storage::fake('public');

        $this->seed(WebsitePreviewSeeder::class);

        $previews = WebsitePreview::query()->orderBy('sort_order')->get();
        $this->assertCount(3, $previews);
        $this->assertSame([
            'Demo concept: Landing page',
            'Demo concept: Analytics dashboard',
            'Demo concept: POS & store catalogue',
        ], $previews->pluck('title')->all());

        foreach ($previews as $preview) {
            $this->assertTrue($preview->is_published);
            $this->assertNotNull($preview->preview_url);
            $this->assertStringContainsString('/website-previews/concepts/', $preview->preview_url);
            $this->assertSame('Concept demo', $preview->category);
            $this->assertStringContainsString('not a client project', $preview->description);
            Storage::disk('public')->assertExists($preview->image_path);
            $this->assertCount(2, $preview->gallery_images);
            foreach ($preview->gallery_images as $galleryImage) {
                Storage::disk('public')->assertExists($galleryImage);
            }
        }

        $this->seed(WebsitePreviewSeeder::class);
        $this->assertDatabaseCount('website_previews', 3);

        $this->get(route('website-previews.index'))
            ->assertOk()
            ->assertSee('Demo concept: Landing page')
            ->assertSee('Demo concept: Analytics dashboard')
            ->assertSee('Demo concept: POS &amp; store catalogue', false)
            ->assertSee('See what your website could')
            ->assertSee('portfolio-mosaic')
            ->assertSee('#0b1727', false)
            ->assertSee('#ff7849', false)
            ->assertSee('Explore website previews')
            ->assertSee('View concept')
            ->assertSee('data-lightbox-preview', false)
            ->assertSee('data-preview-url="'.route('website-previews.concepts.show', 'landing-page').'"', false)
            ->assertSee('Make one for me')
            ->assertSee('Ready for the next step?')
            ->assertSee('Need a hand?')
            ->assertSee('Go home')
            ->assertSee('Message us on WhatsApp')
            ->assertSee('not completed client projects')
            ->assertSee('<meta name="description" content="Explore responsive website demos for cafes, shops, events, and more.', false)
            ->assertSee('<link rel="canonical" href="'.route('website-previews.index').'">', false);
    }

    public function test_each_seeded_concept_renders_a_bootstrap_page_with_three_images(): void
    {
        Storage::fake('public');
        $this->seed(WebsitePreviewSeeder::class);

        foreach (config('website-preview-concepts') as $slug => $concept) {
            $preview = WebsitePreview::query()->where('title', $concept['title'])->firstOrFail();
            $response = $this->get(route('website-previews.concepts.show', $slug))
                ->assertOk()
                ->assertSee($concept['brand'])
                ->assertSee('Bootstrap-based')
                ->assertSee('class="container', false)
                ->assertSee('class="navbar', false)
                ->assertSee('class="row', false)
                ->assertSee(' card h-100', false);

            if ($slug === 'dashboard') {
                $response->assertSee('Revenue overview')
                    ->assertSee('aria-label="Revenue trend line chart"', false)
                    ->assertSee('Sales by channel');
            } elseif ($slug === 'pos-catalogue') {
                $response->assertSee('Current sale')
                    ->assertSee('All products')
                    ->assertSee('No payment is processed');
            } else {
                $response->assertSee('Turn good ideas into great work.');
            }

            foreach ([$preview->image_path, ...$preview->gallery_images] as $image) {
                $response->assertSee(Storage::disk('public')->url($image), false);
            }
        }
    }

    public function test_seeder_replaces_the_three_legacy_concepts_without_creating_duplicates(): void
    {
        Storage::fake('public');
        foreach (['Cafe website', 'Business services website', 'Online store'] as $index => $legacyTitle) {
            WebsitePreview::query()->create([
                'title' => 'Demo concept: '.$legacyTitle,
                'image_path' => "website-previews/demo/legacy-{$index}.jpg",
                'is_published' => true,
                'sort_order' => $index + 1,
            ]);
        }

        $this->seed(WebsitePreviewSeeder::class);

        $this->assertDatabaseCount('website_previews', 3);
        $this->assertDatabaseHas('website_previews', ['title' => 'Demo concept: Landing page']);
        $this->assertDatabaseHas('website_previews', ['title' => 'Demo concept: Analytics dashboard']);
        $this->assertDatabaseHas('website_previews', ['title' => 'Demo concept: POS & store catalogue']);
        $this->assertDatabaseMissing('website_previews', ['title' => 'Demo concept: Cafe website']);
    }

    public function test_admin_can_publish_a_website_preview_to_the_public_portfolio(): void
    {
        Storage::fake('public');
        $admin = $this->adminUser();

        $this->actingAs($admin)->post(route('development.website-previews.store'), [
            'title' => 'Local cafe website',
            'category' => 'Hospitality',
            'description' => 'A responsive website concept for a neighborhood cafe.',
            'preview_url' => 'https://cafe.example.test',
            'sort_order' => 1,
            'is_published' => '1',
            'image' => UploadedFile::fake()->image('cafe.png', 1200, 750),
            'gallery_images' => [
                UploadedFile::fake()->image('cafe-detail-1.png', 900, 600),
                UploadedFile::fake()->image('cafe-detail-2.png', 900, 600),
            ],
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('development.website-previews.manage'));

        $preview = WebsitePreview::query()->firstOrFail();
        Storage::disk('public')->assertExists($preview->image_path);
        $this->assertCount(2, $preview->gallery_images);
        foreach ($preview->gallery_images as $galleryImage) {
            Storage::disk('public')->assertExists($galleryImage);
        }
        $this->assertTrue($preview->is_published);

        $this->get(route('website-previews.index'))
            ->assertOk()
            ->assertSee('Website previews')
            ->assertSee('Local cafe website')
            ->assertSee('A responsive website concept')
            ->assertSee('https://cafe.example.test')
            ->assertSee('data-portfolio-lightbox', false)
            ->assertSee('data-preview-url="https://cafe.example.test"', false)
            ->assertSee(str_replace('/', '\\/', Storage::disk('public')->url($preview->gallery_images[0])), false)
            ->assertSee('Request a website');

        $this->get(route('development.website-previews.manage'))
            ->assertOk()
            ->assertSee('Local cafe website')
            ->assertSee('Published');
    }

    public function test_public_page_hides_drafts_and_admin_can_edit_and_remove_previews(): void
    {
        Storage::fake('public');
        $image = UploadedFile::fake()->image('preview.png');
        $imagePath = $image->store('website-previews', 'public');
        $preview = WebsitePreview::query()->create([
            'title' => 'Unpublished mockup',
            'image_path' => $imagePath,
            'is_published' => false,
            'sort_order' => 0,
        ]);
        $this->get(route('website-previews.index'))
            ->assertOk()
            ->assertDontSee('Unpublished mockup');

        $admin = $this->adminUser();
        $this->actingAs($admin)->put(route('development.website-previews.update', $preview), [
            'title' => 'Published mockup',
            'category' => 'Landing page',
            'description' => 'Updated portfolio item.',
            'preview_url' => '',
            'sort_order' => 2,
            'is_published' => '1',
        ])->assertSessionHasNoErrors();

        $this->get(route('website-previews.index'))
            ->assertOk()
            ->assertSee('Published mockup')
            ->assertSee('Updated portfolio item.');

        $this->delete(route('development.website-previews.destroy', $preview))
            ->assertRedirect(route('development.website-previews.manage'));
        $this->assertDatabaseMissing('website_previews', ['id' => $preview->id]);
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_gallery_images_can_be_added_removed_and_deleted_with_the_preview(): void
    {
        Storage::fake('public');
        $mainImage = UploadedFile::fake()->image('main.png');
        $firstGalleryImage = UploadedFile::fake()->image('first.png');
        $secondGalleryImage = UploadedFile::fake()->image('second.png');
        $mainPath = $mainImage->store('website-previews', 'public');
        $firstPath = $firstGalleryImage->store('website-previews', 'public');
        $preview = WebsitePreview::query()->create([
            'title' => 'Gallery preview',
            'image_path' => $mainPath,
            'gallery_images' => [$firstPath],
            'is_published' => true,
            'sort_order' => 1,
        ]);
        $admin = $this->adminUser();

        $this->actingAs($admin)->put(route('development.website-previews.update', $preview), [
            'title' => $preview->title,
            'sort_order' => $preview->sort_order,
            'remove_gallery_images' => [$firstPath],
            'gallery_images' => [$secondGalleryImage],
        ])->assertSessionHasNoErrors();

        $preview->refresh();
        $this->assertCount(1, $preview->gallery_images);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($preview->gallery_images[0]);
        Storage::disk('public')->assertExists($mainPath);

        $this->delete(route('development.website-previews.destroy', $preview))
            ->assertRedirect(route('development.website-previews.manage'));
        Storage::disk('public')->assertMissing($mainPath);
        Storage::disk('public')->assertMissing($preview->gallery_images[0]);
    }

    public function test_only_admins_can_manage_website_previews(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->get(route('development.website-previews.manage'))
            ->assertForbidden();
    }

    private function adminUser(): User
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
