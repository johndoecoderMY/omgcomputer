<?php

namespace App\Services;

use App\Models\MetaContentPost;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MetaContentPublisher
{
    public function publish(MetaContentPost $post): string
    {
        $token = $post->platform === 'instagram'
            ? (config('services.meta.instagram_access_token') ?: config('services.meta.access_token'))
            : (config('services.meta.page_access_token') ?: config('services.meta.access_token'));
        $version = config('services.meta.graph_api_version');
        $accountId = $post->platform === 'instagram'
            ? config('services.meta.instagram_user_id')
            : config('services.meta.page_id');

        if (blank($token) || blank($accountId)) {
            throw new RuntimeException('Configure a publishing access token and the matching Meta account ID before publishing.');
        }
        if (! is_string($version) || ! preg_match('/^v\d+\.\d+$/', $version)) {
            throw new RuntimeException('The configured Meta Graph API version is invalid.');
        }
        if ($post->platform === 'instagram' && $post->media_type === 'text') {
            throw new RuntimeException('Instagram publishing requires an image or reel.');
        }
        if ($post->media_type !== 'text' && blank($post->media_path)) {
            throw new RuntimeException('This post is missing its uploaded media.');
        }

        try {
            if ($post->platform === 'instagram') {
                return $this->publishInstagram($post, (string) $accountId, (string) $token, $version);
            }

            if ($post->media_type === 'reel') {
                return $this->publishFacebookReel($post, (string) $accountId, (string) $token, $version);
            }

            return $this->publishFacebook($post, (string) $accountId, (string) $token, $version);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not reach Meta Graph API. Check the server connection and retry.', previous: $exception);
        }
    }

    private function publishFacebook(MetaContentPost $post, string $pageId, string $token, string $version): string
    {
        $endpoint = $this->endpoint($version, $pageId);
        $fields = ['message' => $post->caption ?? ''];

        if ($post->media_type === 'text') {
            $response = Http::withToken($token)->acceptJson()->connectTimeout(5)->timeout(30)
                ->post("{$endpoint}/feed", $fields);
        } else {
            $mediaUrl = $this->publicMediaUrl($post);
            $response = Http::withToken($token)->acceptJson()->connectTimeout(5)->timeout(60)
                ->post("{$endpoint}/photos", [
                    ...$fields,
                    'url' => $mediaUrl,
                ]);
        }

        return $this->responseId($response);
    }

    private function publishFacebookReel(MetaContentPost $post, string $pageId, string $token, string $version): string
    {
        $endpoint = $this->endpoint($version, $pageId, 'video_reels');
        $start = $this->post($endpoint, $token, ['upload_phase' => 'start']);
        $upload = $this->responseData($start);
        $videoId = $upload['video_id'] ?? null;
        $uploadUrl = $upload['upload_url'] ?? null;
        if (! is_string($videoId) || $videoId === '' || ! is_string($uploadUrl)
            || ! str_starts_with($uploadUrl, 'https://rupload.facebook.com/')) {
            throw new RuntimeException('Meta did not return a valid Facebook Reel upload session.');
        }

        $uploadResponse = Http::withHeaders([
            'Authorization' => "OAuth {$token}",
            'file_url' => $this->publicMediaUrl($post),
        ])->acceptJson()->connectTimeout(5)->timeout(60)->post($uploadUrl);
        $this->responseData($uploadResponse);

        $finish = $this->post($endpoint, $token, [
            'upload_phase' => 'finish',
            'video_id' => $videoId,
            'video_state' => 'PUBLISHED',
            'description' => $post->caption ?? '',
        ]);
        $data = $this->responseData($finish);
        if (($data['success'] ?? false) !== true) {
            throw new RuntimeException('Meta did not confirm the Facebook Reel publication.');
        }

        return $videoId;
    }

    private function publishInstagram(MetaContentPost $post, string $instagramId, string $token, string $version): string
    {
        $mediaUrl = $this->publicMediaUrl($post);
        $mediaData = $post->media_type === 'reel'
            ? ['media_type' => 'REELS', 'video_url' => $mediaUrl]
            : ['image_url' => $mediaUrl];
        $container = $this->post(
            $this->endpoint($version, $instagramId, 'media'),
            $token,
            [...$mediaData, 'caption' => $post->caption ?? ''],
        );
        $containerId = $this->responseId($container);

        if ($post->media_type === 'reel') {
            $this->waitForInstagramReel($containerId, $token, $version);
        }

        $published = $this->post(
            $this->endpoint($version, $instagramId, 'media_publish'),
            $token,
            ['creation_id' => $containerId],
        );

        return $this->responseId($published);
    }

    private function waitForInstagramReel(string $containerId, string $token, string $version): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $response = Http::withToken($token)->acceptJson()->connectTimeout(5)->timeout(20)
                ->get($this->endpoint($version, $containerId), ['fields' => 'status_code']);
            $status = $this->responseData($response)['status_code'] ?? null;

            if ($status === 'FINISHED') {
                return;
            }
            if ($status === 'ERROR' || $status === 'EXPIRED') {
                throw new RuntimeException('Meta could not process the Instagram reel. Check the video format and public media URL.');
            }
            if ($attempt < 4) {
                sleep(2);
            }
        }

        throw new RuntimeException('Meta is still processing the Instagram reel. Retry publishing after processing completes.');
    }

    private function post(string $url, string $token, array $data): Response
    {
        return Http::withToken($token)->acceptJson()->connectTimeout(5)->timeout(30)
            ->post($url, $data);
    }

    private function responseId(Response $response): string
    {
        $data = $this->responseData($response);
        $id = $data['id'] ?? $data['post_id'] ?? null;

        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Meta accepted the request but returned no post ID.');
        }

        return $id;
    }

    private function responseData(Response $response): array
    {
        $data = $response->json();
        if (! $response->successful() || ! is_array($data)) {
            $message = is_array($data) ? data_get($data, 'error.message') : null;
            throw new RuntimeException(is_string($message) && $message !== ''
                ? mb_substr($message, 0, 500)
                : "Meta Graph API returned HTTP {$response->status()}.");
        }

        return $data;
    }

    private function publicMediaUrl(MetaContentPost $post): string
    {
        $baseUrl = config('services.meta.public_media_base_url') ?: config('app.url');
        if (! is_string($baseUrl) || ! filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Set APP_URL or META_PUBLIC_MEDIA_BASE_URL to a publicly reachable URL for Meta media uploads.');
        }

        $mediaUrl = Storage::disk('public')->url((string) $post->media_path);
        if (filled(config('services.meta.public_media_base_url'))) {
            $path = parse_url($mediaUrl, PHP_URL_PATH);
            $mediaUrl = rtrim($baseUrl, '/').(is_string($path) ? $path : '/storage/'.$post->media_path);
        }
        if (! filter_var($mediaUrl, FILTER_VALIDATE_URL)
            || (! app()->environment(['local', 'testing']) && parse_url($mediaUrl, PHP_URL_SCHEME) !== 'https')) {
            throw new RuntimeException('Meta media URLs must be publicly reachable; use HTTPS in non-local environments.');
        }

        return $mediaUrl;
    }

    private function endpoint(string $version, string $id, ?string $edge = null): string
    {
        return 'https://graph.facebook.com/'.$version.'/'.rawurlencode($id).($edge ? '/'.$edge : '');
    }
}
