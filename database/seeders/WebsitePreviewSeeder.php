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
        foreach (config('website-preview-concepts') as $slug => $concept) {
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

            $galleryPaths = [];
            foreach ($concept['gallery'] as $galleryFilename) {
                $gallerySourcePath = public_path('assets/img/website-preview-concepts/'.$galleryFilename);
                if (! is_file($gallerySourcePath)) {
                    throw new RuntimeException("Website preview gallery image is missing: {$gallerySourcePath}");
                }

                $galleryImage = file_get_contents($gallerySourcePath);
                if ($galleryImage === false) {
                    throw new RuntimeException("Website preview gallery image could not be read: {$gallerySourcePath}");
                }

                $galleryPath = 'website-previews/demo/'.$galleryFilename;
                if (! Storage::disk('public')->put($galleryPath, $galleryImage)) {
                    throw new RuntimeException("Website preview gallery image could not be stored: {$galleryPath}");
                }
                $galleryPaths[] = $galleryPath;
            }

            $preview = WebsitePreview::query()->where('title', $concept['title'])->first()
                ?? WebsitePreview::query()->where('title', $concept['legacy_title'])->first()
                ?? new WebsitePreview;

            $preview->fill([
                'title' => $concept['title'],
                'category' => 'Concept demo',
                'description' => $concept['description'],
                'preview_url' => route('website-previews.concepts.show', $slug),
                'image_path' => $imagePath,
                'gallery_images' => $galleryPaths,
                'is_published' => true,
                'sort_order' => $concept['sort_order'],
            ])->save();
        }
    }
}
