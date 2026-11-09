<?php

namespace Database\Seeders;

use App\Models\WebsitePreview;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WebsitePreviewSeeder extends Seeder
{
    public function run(): void
    {
        $concepts = [
            [
                'title' => 'Demo concept: Cafe website',
                'filename' => 'cafe-website-concept.jpg',
                'description' => 'Illustrative cafe website concept using Unsplash stock photography. This is a sample design, not a client project.',
                'sort_order' => 1,
            ],
            [
                'title' => 'Demo concept: Business services website',
                'filename' => 'business-website-concept.jpg',
                'description' => 'Illustrative business services website concept using Unsplash stock photography. This is a sample design, not a client project.',
                'sort_order' => 2,
            ],
            [
                'title' => 'Demo concept: Online store',
                'filename' => 'store-website-concept.jpg',
                'description' => 'Illustrative online store website concept using Unsplash stock photography. This is a sample design, not a client project.',
                'sort_order' => 3,
            ],
        ];

        foreach ($concepts as $concept) {
            $sourcePath = public_path('assets/img/website-preview-concepts/'.$concept['filename']);
            if (! is_file($sourcePath)) {
                throw new RuntimeException("Website preview concept image is missing: {$sourcePath}");
            }

            $image = file_get_contents($sourcePath);
            if ($image === false) {
                throw new RuntimeException("Website preview concept image could not be read: {$sourcePath}");
            }

            $imagePath = 'website-previews/demo/'.$concept['filename'];
            if (! Storage::disk('public')->put($imagePath, $image)) {
                throw new RuntimeException("Website preview concept image could not be stored: {$imagePath}");
            }

            WebsitePreview::query()->updateOrCreate(
                ['title' => $concept['title']],
                [
                    'category' => 'Concept demo',
                    'description' => $concept['description'],
                    'preview_url' => null,
                    'image_path' => $imagePath,
                    'is_published' => true,
                    'sort_order' => $concept['sort_order'],
                ],
            );
        }
    }
}
