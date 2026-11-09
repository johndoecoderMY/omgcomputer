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

        foreach ($previews as $preview) {
            $this->assertTrue($preview->is_published);
            $this->assertNull($preview->preview_url);
            $this->assertSame('Concept demo', $preview->category);
            $this->assertStringContainsString('not a client project', $preview->description);
            Storage::disk('public')->assertExists($preview->image_path);
        }

        $this->seed(WebsitePreviewSeeder::class);
        $this->assertDatabaseCount('website_previews', 3);

        $this->get(route('website-previews.index'))
            ->assertOk()
            ->assertSee('Demo concept: Cafe website')
            ->assertSee('Demo concept: Business services website')
            ->assertSee('Demo concept: Online store')
            ->assertSee('not completed client projects');
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
        ])->assertSessionHasNoErrors()
            ->assertRedirect(route('development.website-previews.manage'));

        $preview = WebsitePreview::query()->firstOrFail();
        Storage::disk('public')->assertExists($preview->image_path);
        $this->assertTrue($preview->is_published);

        $this->get(route('website-previews.index'))
            ->assertOk()
            ->assertSee('Website previews')
            ->assertSee('Local cafe website')
            ->assertSee('A responsive website concept')
            ->assertSee('https://cafe.example.test')
            ->assertSee('Discuss a website');

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
