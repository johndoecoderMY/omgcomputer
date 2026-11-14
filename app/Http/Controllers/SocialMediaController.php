<?php

namespace App\Http\Controllers;

use App\Models\CompanyProfile;
use App\Models\MetaPageConnection;
use App\Models\TikTokConnection;
use App\Services\MetaGraphApi;
use App\Services\TikTokApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class SocialMediaController extends Controller
{
    public function index(MetaGraphApi $metaApi, TikTokApi $tiktokApi): View
    {
        $facebookPage = MetaPageConnection::query()->first();
        $facebookProfile = null;
        $facebookPosts = [];
        $facebookError = null;
        if ($facebookPage) {
            try {
                $pageContent = $metaApi->pageContent($facebookPage);
                $facebookProfile = $pageContent['page'];
                $facebookPosts = $pageContent['posts'];
            } catch (RuntimeException $exception) {
                Log::warning('Facebook profile data could not be loaded.', [
                    'connection_id' => $facebookPage->id,
                    'exception' => $exception::class,
                ]);
                $facebookError = $exception->getMessage();
            }
        }

        $instagramProfile = null;
        $instagramMedia = [];
        $instagramError = null;
        $instagramMediaError = null;
        $instagramConfigured = filled(config('services.meta.instagram_user_id'))
            && (filled(config('services.meta.instagram_access_token')) || filled(config('services.meta.access_token')));
        if ($instagramConfigured) {
            try {
                $instagramProfile = $metaApi->instagramProfile();
            } catch (RuntimeException $exception) {
                Log::warning('Instagram profile data could not be loaded.', ['exception' => $exception::class]);
                $instagramError = $exception->getMessage();
            }
            if ($instagramProfile) {
                try {
                    $instagramMedia = $metaApi->instagramMedia();
                } catch (RuntimeException $exception) {
                    Log::warning('Instagram media could not be loaded.', ['exception' => $exception::class]);
                    $instagramMediaError = $exception->getMessage();
                }
            }
        }

        $tiktokConnection = TikTokConnection::query()->first();
        $tiktokProfile = null;
        $tiktokError = null;
        if ($tiktokConnection) {
            try {
                $tiktokProfile = $tiktokApi->profile($tiktokConnection);
            } catch (RuntimeException $exception) {
                Log::warning('TikTok profile data could not be loaded.', [
                    'connection_id' => $tiktokConnection->id,
                    'exception' => $exception::class,
                ]);
                $tiktokError = $exception->getMessage();
            }
        }

        return view('social-media.manage', [
            'companyProfile' => CompanyProfile::current(),
            'facebookPage' => $facebookPage,
            'facebookProfile' => $facebookProfile,
            'facebookPosts' => $facebookPosts,
            'facebookError' => $facebookError,
            'instagramProfile' => $instagramProfile,
            'instagramMedia' => $instagramMedia,
            'instagramError' => $instagramError,
            'instagramMediaError' => $instagramMediaError,
            'tiktokConnection' => $tiktokConnection,
            'tiktokProfile' => $tiktokProfile,
            'tiktokError' => $tiktokError,
            'configured' => [
                'instagram' => $instagramConfigured,
                'tiktok' => $tiktokApi->configured(),
                'meta' => $metaApi->configured(),
            ],
        ]);
    }

    public function updateProfiles(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'facebook_url' => ['nullable', 'url:http,https', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                $this->validateHost($value, ['facebook.com', 'www.facebook.com', 'm.facebook.com', 'fb.me'], $attribute, $fail);
            }],
            'instagram_url' => ['nullable', 'url:http,https', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                $this->validateHost($value, ['instagram.com', 'www.instagram.com'], $attribute, $fail);
            }],
            'tiktok_url' => ['nullable', 'url:http,https', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                $this->validateHost($value, ['tiktok.com', 'www.tiktok.com', 'vm.tiktok.com', 'vt.tiktok.com'], $attribute, $fail);
            }],
        ]);

        $profile = CompanyProfile::query()->firstOrNew(['id' => 'primary']);
        $profile->fill($data);
        $profile->save();

        return redirect()->route('social-media.index')->with('success', 'Social media profile links saved.');
    }

    public function updateFacebookProfile(Request $request, MetaGraphApi $api): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:75'],
            'about' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
        ]);

        try {
            $api->updatePageProfile(
                MetaPageConnection::query()->firstOrFail(),
                $data['name'],
                $data['about'] ?? '',
                $data['website'] ?? null,
            );
        } catch (RuntimeException $exception) {
            Log::warning('Facebook Page profile could not be updated.', ['exception' => $exception::class]);

            return redirect()->route('social-media.index')->with('social_media_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('social-media.index')->with('social_media_result', [
            'success' => true,
            'message' => 'Facebook Page profile updated.',
        ]);
    }

    public function updateFacebookPicture(Request $request, MetaGraphApi $api): RedirectResponse
    {
        $data = $request->validate([
            'picture_url' => ['required', 'url:http,https', 'max:2048'],
        ]);

        try {
            $api->updatePagePicture(MetaPageConnection::query()->firstOrFail(), $data['picture_url']);
        } catch (RuntimeException $exception) {
            Log::warning('Facebook Page profile picture could not be updated.', ['exception' => $exception::class]);

            return redirect()->route('social-media.index')->with('social_media_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('social-media.index')->with('social_media_result', [
            'success' => true,
            'message' => 'Facebook Page profile picture updated.',
        ]);
    }

    public function connectTikTok(TikTokApi $api): RedirectResponse
    {
        if (! $api->configured()) {
            return redirect()->route('social-media.index')->with('social_media_result', [
                'success' => false,
                'message' => 'Set TIKTOK_CLIENT_KEY and TIKTOK_CLIENT_SECRET in the server environment before connecting.',
            ]);
        }

        $redirectUri = route('social-media.tiktok.callback');
        $configuredRedirectUri = config('services.tiktok.redirect_uri');
        if (filled($configuredRedirectUri) && (string) $configuredRedirectUri !== $redirectUri) {
            return redirect()->route('social-media.index')->with('social_media_result', [
                'success' => false,
                'message' => 'Set TIKTOK_REDIRECT_URI to '.$redirectUri.' in the server environment.',
            ]);
        }

        $state = Str::random(48);
        session()->put('social_media_tiktok_state', $state);

        return redirect()->away($api->authorizationUrl($state, $redirectUri));
    }

    public function tiktokCallback(Request $request, TikTokApi $api): RedirectResponse
    {
        $expectedState = session()->pull('social_media_tiktok_state');
        $receivedState = $request->query('state');
        if (! is_string($expectedState) || ! is_string($receivedState) || ! hash_equals($expectedState, $receivedState)) {
            return redirect()->route('social-media.index')->with('social_media_result', [
                'success' => false,
                'message' => 'TikTok authorization could not be verified. Start the connection again.',
            ]);
        }

        if ($request->filled('error')) {
            return redirect()->route('social-media.index')->with('social_media_result', [
                'success' => false,
                'message' => 'TikTok authorization was cancelled or declined.',
            ]);
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '' || strlen($code) > 4096) {
            return redirect()->route('social-media.index')->with('social_media_result', [
                'success' => false,
                'message' => 'TikTok did not return an authorization code.',
            ]);
        }

        try {
            $tokens = $api->exchangeAuthorizationCode($code, route('social-media.tiktok.callback'));
        } catch (RuntimeException $exception) {
            Log::warning('TikTok authorization could not be completed.', ['exception' => $exception::class]);

            return redirect()->route('social-media.index')->with('social_media_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        $connection = TikTokConnection::query()->first();
        $connectionData = [
            ...$tokens,
            'connected_by_user_id' => $request->user()->id,
        ];
        if ($connection) {
            $connection->update($connectionData);
        } else {
            TikTokConnection::query()->create($connectionData);
        }

        return redirect()->route('social-media.index')->with('social_media_result', [
            'success' => true,
            'message' => 'TikTok account connected. Profile and follower data are now available.',
        ]);
    }

    public function disconnectTikTok(TikTokApi $api): RedirectResponse
    {
        $connection = TikTokConnection::query()->firstOrFail();

        try {
            $api->revoke($connection);
        } catch (RuntimeException $exception) {
            Log::warning('TikTok account could not be disconnected.', [
                'connection_id' => $connection->id,
                'exception' => $exception::class,
            ]);

            return redirect()->route('social-media.index')->with('social_media_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        $connection->delete();

        return redirect()->route('social-media.index')->with('social_media_result', [
            'success' => true,
            'message' => 'TikTok account disconnected and authorization revoked.',
        ]);
    }

    public function instagramMediaComments(string $mediaId, MetaGraphApi $api): View
    {
        $comments = [];
        $commentsError = null;
        try {
            $comments = $api->instagramMediaComments($mediaId);
        } catch (RuntimeException $exception) {
            Log::warning('Instagram media comments could not be loaded.', [
                'media_id' => $mediaId,
                'exception' => $exception::class,
            ]);
            $commentsError = $exception->getMessage();
        }

        return view('social-media.instagram-comments', compact('mediaId', 'comments', 'commentsError'));
    }

    public function replyToInstagramComment(
        Request $request,
        string $mediaId,
        string $commentId,
        MetaGraphApi $api,
    ): RedirectResponse {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2200'],
        ]);

        try {
            $api->replyToInstagramComment($mediaId, $commentId, $data['message']);

            return redirect()->route('social-media.instagram.comments', $mediaId)->with('success', 'Instagram reply posted.');
        } catch (RuntimeException $exception) {
            Log::warning('Instagram comment reply could not be posted.', [
                'media_id' => $mediaId,
                'comment_id' => $commentId,
                'exception' => $exception::class,
            ]);

            return redirect()->route('social-media.instagram.comments', $mediaId)->with('social_media_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function updateInstagramCommentVisibility(
        Request $request,
        string $mediaId,
        string $commentId,
        MetaGraphApi $api,
    ): RedirectResponse {
        $data = $request->validate(['hidden' => ['required', 'boolean']]);

        try {
            $api->updateInstagramCommentVisibility($mediaId, $commentId, (bool) $data['hidden']);

            return redirect()->route('social-media.instagram.comments', $mediaId)->with('success', 'Instagram comment visibility updated.');
        } catch (RuntimeException $exception) {
            Log::warning('Instagram comment visibility could not be updated.', [
                'media_id' => $mediaId,
                'comment_id' => $commentId,
                'exception' => $exception::class,
            ]);

            return redirect()->route('social-media.instagram.comments', $mediaId)->with('social_media_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function deleteInstagramComment(string $mediaId, string $commentId, MetaGraphApi $api): RedirectResponse
    {
        try {
            $api->deleteInstagramComment($mediaId, $commentId);

            return redirect()->route('social-media.instagram.comments', $mediaId)->with('success', 'Instagram comment deleted.');
        } catch (RuntimeException $exception) {
            Log::warning('Instagram comment could not be deleted.', [
                'media_id' => $mediaId,
                'comment_id' => $commentId,
                'exception' => $exception::class,
            ]);

            return redirect()->route('social-media.instagram.comments', $mediaId)->with('social_media_result', [
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function validateHost(mixed $value, array $allowedHosts, string $attribute, \Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $host = strtolower((string) parse_url((string) $value, PHP_URL_HOST));
        if (! in_array($host, $allowedHosts, true)) {
            $fail("The {$attribute} must be a URL from the matching social media site.");
        }
    }
}
