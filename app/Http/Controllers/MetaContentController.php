<?php

namespace App\Http\Controllers;

use App\Models\MetaContentPost;
use App\Services\MetaContentPublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class MetaContentController extends Controller
{
    public function index(): View
    {
        $mediaBaseUrl = config('services.meta.public_media_base_url') ?: config('app.url');
        $mediaScheme = is_string($mediaBaseUrl) ? parse_url($mediaBaseUrl, PHP_URL_SCHEME) : null;
        $mediaHost = is_string($mediaBaseUrl) ? parse_url($mediaBaseUrl, PHP_URL_HOST) : null;
        $localMediaHost = in_array($mediaHost, ['localhost', '127.0.0.1', '::1'], true);
        $publicMediaUrl = filter_var($mediaBaseUrl, FILTER_VALIDATE_URL)
            && (app()->environment(['local', 'testing']) || ($mediaScheme === 'https' && ! $localMediaHost));

        return view('meta-content.index', [
            'posts' => MetaContentPost::query()->with('creator')->latest()->paginate(20),
            'configured' => [
                'page_access_token' => filled(config('services.meta.page_access_token'))
                    || filled(config('services.meta.access_token')),
                'instagram_access_token' => filled(config('services.meta.instagram_access_token'))
                    || filled(config('services.meta.access_token')),
                'page_id' => filled(config('services.meta.page_id')),
                'instagram_user_id' => filled(config('services.meta.instagram_user_id')),
                'public_media_url' => $publicMediaUrl,
            ],
        ]);
    }

    public function create(): View
    {
        return view('meta-content.create');
    }

    public function store(Request $request, MetaContentPublisher $publisher): RedirectResponse
    {
        $data = $this->validatedData($request);
        $mediaPath = ($data['media'] ?? null)?->store('meta-content', 'public');
        $post = MetaContentPost::query()->create([
            'created_by' => $request->user()->id,
            'platform' => $data['platform'],
            'media_type' => $data['media_type'],
            'caption' => $data['caption'] ?? null,
            'media_path' => $mediaPath,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => isset($data['publish_now']) ? 'draft' : (isset($data['scheduled_at']) ? 'scheduled' : 'draft'),
        ]);

        if (isset($data['publish_now'])) {
            return $this->publishPost($post, $publisher);
        }

        return redirect()->route('meta-content.index')
            ->with('success', $post->status === 'scheduled' ? 'Meta content scheduled.' : 'Meta content saved as a draft.');
    }

    public function edit(MetaContentPost $metaContentPost): View
    {
        abort_if(! in_array($metaContentPost->status, ['draft', 'scheduled', 'failed'], true), 404);

        return view('meta-content.edit', ['post' => $metaContentPost]);
    }

    public function update(Request $request, MetaContentPost $metaContentPost): RedirectResponse
    {
        abort_if(! in_array($metaContentPost->status, ['draft', 'scheduled', 'failed'], true), 404);
        $data = $this->validatedData($request, $metaContentPost);
        $mediaPath = $metaContentPost->media_path;
        if (isset($data['media'])) {
            $mediaPath = $data['media']->store('meta-content', 'public');
            if ($metaContentPost->media_path) {
                Storage::disk('public')->delete($metaContentPost->media_path);
            }
        }
        $metaContentPost->update([
            'platform' => $data['platform'],
            'media_type' => $data['media_type'],
            'caption' => $data['caption'] ?? null,
            'media_path' => $mediaPath,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => isset($data['scheduled_at']) ? 'scheduled' : 'draft',
            'error_message' => null,
        ]);

        return redirect()->route('meta-content.index')->with('success', 'Meta content updated.');
    }

    public function publish(MetaContentPost $metaContentPost, MetaContentPublisher $publisher): RedirectResponse
    {
        abort_if(! in_array($metaContentPost->status, ['draft', 'failed'], true), 404);

        return $this->publishPost($metaContentPost, $publisher);
    }

    public function destroy(MetaContentPost $metaContentPost): RedirectResponse
    {
        abort_if($metaContentPost->status === 'published', 404);
        if ($metaContentPost->media_path) {
            Storage::disk('public')->delete($metaContentPost->media_path);
        }
        $metaContentPost->delete();

        return redirect()->route('meta-content.index')->with('success', 'Meta content deleted.');
    }

    private function publishPost(MetaContentPost $post, MetaContentPublisher $publisher): RedirectResponse
    {
        try {
            $metaId = $publisher->publish($post);
        } catch (RuntimeException $exception) {
            $post->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'scheduled_at' => null,
            ]);

            return redirect()->route('meta-content.index')->with('meta_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        $post->update([
            'status' => 'published',
            'published_at' => now(),
            'meta_post_id' => $metaId,
            'error_message' => null,
            'scheduled_at' => null,
        ]);

        return redirect()->route('meta-content.index')->with('success', 'Content published to Meta.');
    }

    private function validatedData(Request $request, ?MetaContentPost $post = null): array
    {
        $data = $request->validate([
            'platform' => ['required', Rule::in(['facebook', 'instagram'])],
            'media_type' => ['required', Rule::in(['text', 'image', 'reel'])],
            'caption' => ['nullable', 'string', 'max:63206'],
            'media' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4', 'max:102400'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'publish_now' => ['sometimes', 'accepted'],
        ]);
        $existingMediaExists = filled($post?->media_path)
            && Storage::disk('public')->exists($post->media_path);
        $existingMime = $existingMediaExists
            ? Storage::disk('public')->mimeType($post->media_path)
            : null;
        $mediaMime = isset($data['media']) ? $data['media']->getMimeType() : $existingMime;
        $hasExistingMedia = $existingMediaExists && ! isset($data['media']);
        if ($data['platform'] === 'instagram' && $data['media_type'] === 'text') {
            throw ValidationException::withMessages(['media_type' => 'Instagram posts require an image or reel.']);
        }
        if ($data['platform'] === 'instagram' && mb_strlen($data['caption'] ?? '') > 2200) {
            throw ValidationException::withMessages(['caption' => 'Instagram captions cannot exceed 2,200 characters.']);
        }
        if ($data['media_type'] === 'reel' && (! isset($data['media']) && ! $hasExistingMedia)) {
            throw ValidationException::withMessages(['media' => 'Reels require an MP4 video upload.']);
        }
        if ($data['media_type'] === 'image' && (! isset($data['media']) && ! $hasExistingMedia)) {
            throw ValidationException::withMessages(['media' => 'Upload an image for this post.']);
        }
        if (isset($data['media']) && $data['media_type'] === 'image'
            && ! str_starts_with((string) $data['media']->getMimeType(), 'image/')) {
            throw ValidationException::withMessages(['media' => 'Choose an image file when the media type is image.']);
        }
        if (isset($data['media']) && $data['media_type'] === 'reel'
            && $mediaMime !== 'video/mp4') {
            throw ValidationException::withMessages(['media' => 'Reels must be MP4 videos.']);
        }
        if ($data['platform'] === 'instagram' && $data['media_type'] === 'image'
            && $mediaMime !== null && $mediaMime !== 'image/jpeg') {
            throw ValidationException::withMessages(['media' => 'Instagram image posts require a JPEG image.']);
        }
        if ($data['media_type'] === 'image' && $mediaMime !== null
            && ! str_starts_with($mediaMime, 'image/')) {
            throw ValidationException::withMessages(['media' => 'Choose an image file when the media type is image.']);
        }
        if (isset($data['scheduled_at'], $data['publish_now'])) {
            throw ValidationException::withMessages(['scheduled_at' => 'Choose either publish now or schedule this content.']);
        }

        return $data;
    }
}
