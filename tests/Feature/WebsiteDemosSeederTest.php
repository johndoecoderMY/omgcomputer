<?php

namespace Tests\Feature;

use App\Models\WebsitePreview;
use Database\Seeders\WebsiteDemosSeeder;
use Database\Seeders\WebsitePreviewSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteDemosSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_demos_are_published_with_screenshots_and_static_demo_links(): void
    {
        Storage::fake('public');

        $this->seed(WebsitePreviewSeeder::class);
        $this->seed(WebsiteDemosSeeder::class);

        $demos = WebsitePreview::query()->where('category', 'Website demo')->orderBy('sort_order')->get();
        $this->assertCount(10, $demos);
        $this->assertSame(10, WebsitePreview::query()->count());
        foreach (config('website-preview-concepts') as $concept) {
            $this->assertDatabaseMissing('website_previews', ['title' => $concept['title']]);
            $this->assertDatabaseMissing('website_previews', ['title' => $concept['legacy_title']]);
        }

        foreach (config('website-demos') as $slug => $demo) {
            $preview = $demos->firstWhere('title', $demo['title']);
            $this->assertNotNull($preview);
            $this->assertTrue($preview->is_published);
            $this->assertSame(url('website-demos/'.$slug.'.html'), $preview->preview_url);
            $this->assertStringContainsString('not a client project', $preview->description);
            $this->assertSame('website-previews/demos/'.$demo['filename'], $preview->image_path);
            Storage::disk('public')->assertExists($preview->image_path);

            $html = file_get_contents(public_path('website-demos/'.$slug.'.html'));
            $this->assertNotFalse($html);
            preg_match_all('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $html, $emails);
            $this->assertSame([], array_diff(array_unique($emails[0]), ['support@omgcomputer.my']));
        }

        $firstDemo = $demos->firstOrFail();
        $this->get(route('website-previews.index'))
            ->assertOk()
            ->assertSee($firstDemo->title)
            ->assertSee('Interactive demo')
            ->assertSee('View demo')
            ->assertSee('support@omgcomputer.my')
            ->assertSee('data-portfolio-lightbox', false)
            ->assertSee(str_replace('/', '\\/', Storage::disk('public')->url($firstDemo->image_path)), false)
            ->assertSee($firstDemo->preview_url, false);

        $this->seed(WebsiteDemosSeeder::class);
        $this->assertSame(10, WebsitePreview::query()->where('category', 'Website demo')->count());
    }
}