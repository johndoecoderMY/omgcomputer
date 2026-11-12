<?php

namespace App\Http\Controllers;

use App\Models\WebsitePreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class WebsitePreviewController extends Controller
{
    public function index(): View
    {
        return view('website-previews.index', [
            'previews' => WebsitePreview::query()
                ->where('is_published', true)
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function concept(string $concept): View
    {
        $details = config("website-preview-concepts.{$concept}");
        abort_unless(is_array($details), 404);

        $preview = WebsitePreview::query()
            ->where('title', $details['title'])
            ->where('is_published', true)
            ->firstOrFail();

        return view('website-previews.concept', [
            'concept' => $details,
            'preview' => $preview,
            'images' => [$preview->image_path, ...($preview->gallery_images ?? [])],
        ]);
    }

    public function manage(): View
    {
        return view('website-previews.manage', [
            'previews' => WebsitePreview::query()
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('website-previews.create', ['preview' => new WebsitePreview]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $imagePath = $data['image']->store('website-previews', 'public');
        if (! $imagePath) {
            throw new RuntimeException('The website preview image could not be stored.');
        }

        $galleryImages = $this->storeGalleryImages($request);
        unset($data['image']);
        WebsitePreview::query()->create([
            ...$data,
            'image_path' => $imagePath,
            'gallery_images' => $galleryImages,
        ]);

        return redirect()->route('development.website-previews.manage')->with('success', 'Website preview added.');
    }

    public function edit(WebsitePreview $websitePreview): View
    {
        return view('website-previews.edit', ['preview' => $websitePreview]);
    }

    public function update(Request $request, WebsitePreview $websitePreview): RedirectResponse
    {
        $data = $this->validatedData($request, $websitePreview);
        $galleryImages = $websitePreview->gallery_images ?? [];
        $removeGalleryImages = $data['remove_gallery_images'] ?? [];
        unset($data['remove_gallery_images']);
        $previousFiles = array_intersect($galleryImages, $removeGalleryImages);
        $galleryImages = array_values(array_diff($galleryImages, $removeGalleryImages));
        if (count($galleryImages) + count($request->file('gallery_images', [])) > 8) {
            throw ValidationException::withMessages([
                'gallery_images' => 'A preview can have up to 8 additional images. Remove an existing image before adding more.',
            ]);
        }

        if (isset($data['image'])) {
            $imagePath = $data['image']->store('website-previews', 'public');
            if (! $imagePath) {
                throw new RuntimeException('The replacement website preview image could not be stored.');
            }
            $previousFiles[] = $websitePreview->image_path;
            $data['image_path'] = $imagePath;
            unset($data['image']);
        }

        $newGalleryImages = $this->storeGalleryImages($request);
        $data['gallery_images'] = [...$galleryImages, ...$newGalleryImages];

        $websitePreview->update($data);
        foreach ($previousFiles as $path) {
            if (! Storage::disk('public')->delete($path)) {
                throw new RuntimeException("A replaced website preview image could not be removed: {$path}");
            }
        }

        return redirect()->route('development.website-previews.manage')->with('success', 'Website preview updated.');
    }

    public function destroy(WebsitePreview $websitePreview): RedirectResponse
    {
        $imagePaths = [$websitePreview->image_path, ...($websitePreview->gallery_images ?? [])];
        $websitePreview->delete();
        foreach ($imagePaths as $path) {
            if (! Storage::disk('public')->delete($path)) {
                throw new RuntimeException("The website preview was removed, but its image could not be deleted: {$path}");
            }
        }

        return redirect()->route('development.website-previews.manage')->with('success', 'Website preview removed.');
    }

    private function validatedData(Request $request, ?WebsitePreview $preview = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'preview_url' => ['nullable', 'url:http,https', 'max:2048'],
            'image' => [$preview ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'gallery_images' => ['sometimes', 'array', 'max:8'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_gallery_images' => ['sometimes', 'array'],
            'remove_gallery_images.*' => ['string', Rule::in($preview?->gallery_images ?? [])],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ]);

        if ($preview && ! isset($data['image']) && ! Storage::disk('public')->exists($preview->image_path)) {
            throw ValidationException::withMessages([
                'image' => 'Upload an image because the current preview image is missing.',
            ]);
        }

        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    private function storeGalleryImages(Request $request): array
    {
        $paths = [];
        foreach ($request->file('gallery_images', []) as $image) {
            $path = $image->store('website-previews', 'public');
            if (! $path) {
                throw new RuntimeException('A website preview gallery image could not be stored.');
            }
            $paths[] = $path;
        }

        return $paths;
    }
}
