<?php

namespace Database\Seeders;

use App\Models\WebsitePreview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WebsiteDemosSeeder extends Seeder
{
    public function run(): void
    {
        $obsoleteTitles = collect(config('website-preview-concepts', []))
            ->flatMap(fn (array $concept): array => [$concept['title'], $concept['legacy_title']])
            ->all();
        WebsitePreview::query()->whereIn('title', $obsoleteTitles)->delete();

        foreach (config('website-demos', []) as $slug => $demo) {
            $sourcePath = public_path('website-demos/assets/img/screenshots/website-demos/'.$demo['filename']);
            if (! is_file($sourcePath)) {
                throw new RuntimeException("Website demo screenshot is missing: {$sourcePath}");
            }

            $image = file_get_contents($sourcePath);
            if ($image === false) {
                throw new RuntimeException("Website demo screenshot could not be read: {$sourcePath}");
            }

            $imagePath = 'website-previews/demos/'.$demo['filename'];
            if (! Storage::disk('public')->put($imagePath, $image)) {
                throw new RuntimeException("Website demo screenshot could not be stored: {$imagePath}");
            }

            WebsitePreview::query()->updateOrCreate(
                ['title' => $demo['title']],
                [
                    'category' => 'Website demo',
                    'description' => $demo['description'],
                    'preview_url' => url('website-demos/'.$slug.'.html'),
                    'image_path' => $imagePath,
                    'gallery_images' => [],
                    'is_published' => true,
                    'sort_order' => $demo['sort_order'],
                ],
            );
        }
    }
}