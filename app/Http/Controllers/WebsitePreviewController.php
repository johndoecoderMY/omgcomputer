<?php

namespace App\Http\Controllers;

use App\Models\WebsitePreview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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

        unset($data['image']);
        WebsitePreview::query()->create([...$data, 'image_path' => $imagePath]);

        return redirect()->route('development.website-previews.manage')->with('success', 'Website preview added.');
    }

    public function edit(WebsitePreview $websitePreview): View
    {
        return view('website-previews.edit', ['preview' => $websitePreview]);
    }

    public function update(Request $request, WebsitePreview $websitePreview): RedirectResponse
    {
        $data = $this->validatedData($request, $websitePreview);
        $previousImagePath = null;
        if (isset($data['image'])) {
            $imagePath = $data['image']->store('website-previews', 'public');
            if (! $imagePath) {
                throw new RuntimeException('The replacement website preview image could not be stored.');
            }
            $previousImagePath = $websitePreview->image_path;
            $data['image_path'] = $imagePath;
            unset($data['image']);
        }

        $websitePreview->update($data);
        if ($previousImagePath && ! Storage::disk('public')->delete($previousImagePath)) {
            throw new RuntimeException('The previous website preview image could not be removed.');
        }

        return redirect()->route('development.website-previews.manage')->with('success', 'Website preview updated.');
    }

    public function destroy(WebsitePreview $websitePreview): RedirectResponse
    {
        $websitePreview->delete();
        if (! Storage::disk('public')->delete($websitePreview->image_path)) {
            throw new RuntimeException('The website preview was removed, but its image could not be deleted.');
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
}
