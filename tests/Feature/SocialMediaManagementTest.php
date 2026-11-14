<?php

namespace Tests\Feature;

use App\Models\MetaPageConnection;
use App\Models\TikTokConnection;
use App\Models\User;
use App\Services\TikTokApi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SocialMediaManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_profiles_and_review_platform_connections(): void
    {
        config([
            'services.meta.app_id' => 'meta-app-id',
            'services.meta.app_secret' => 'meta-app-secret',
            'services.meta.instagram_user_id' => 'instagram-user',
            'services.meta.instagram_access_token' => 'instagram-token',
        ]);
        Http::fake([
            'https://graph.facebook.com/v25.0/123456?*' => Http::response([
                'id' => '123456',
                'name' => 'OMG Computer',
                'category' => 'Computer Store',
                'about' => 'Computer support',
                'website' => 'https://omgcomputer.com',
                'fan_count' => 1500,
                'link' => 'https://www.facebook.com/omg-computer',
            ]),
            'https://graph.facebook.com/v25.0/123456/posts?*' => Http::response(['data' => []]),
            'https://graph.facebook.com/v25.0/instagram-user?*' => Http::response([
                'id' => 'instagram-user',
                'username' => 'omg_computer',
                'name' => 'OMG Computer',
                'followers_count' => 1200,
                'follows_count' => 300,
                'media_count' => 40,
                'biography' => 'Computer support',
                'website' => 'https://omgcomputer.com',
            ]),
            'https://graph.facebook.com/v25.0/instagram-user/media?*' => Http::response(['data' => []]),
        ]);
        MetaPageConnection::query()->create([
            'page_id' => '123456',
            'page_name' => 'OMG Computer',
            'access_token' => 'page-token',
        ]);

        $this->actingAs($this->admin())->get(route('social-media.index'))
            ->assertOk()
            ->assertSee('Website profile links')
            ->assertSee('class="btn btn-primary" href="'.route('meta-content.index').'"', false)
            ->assertSee('Facebook Page')
            ->assertSee('OMG Computer')
            ->assertSee('1,500')
            ->assertSee('Edit Facebook Page profile')
            ->assertSee('Instagram')
            ->assertSee('1,200')
            ->assertSee('Instagram’s API does not support editing profile details.')
            ->assertSee('TikTok')
            ->assertSee('TikTok’s API provides profile details');
    }

    public function test_admin_can_save_profile_links_and_the_public_footer_uses_them(): void
    {
        $this->actingAs($this->admin())
            ->put(route('social-media.profiles.update'), [
                'facebook_url' => 'https://www.facebook.com/omg-computer',
                'instagram_url' => 'https://www.instagram.com/omg_computer/',
                'tiktok_url' => 'https://www.tiktok.com/@omgcomputer',
            ])
            ->assertRedirect(route('social-media.index'))
            ->assertSessionHas('success', 'Social media profile links saved.');

        $this->assertDatabaseHas('company_profiles', [
            'id' => 'primary',
            'facebook_url' => 'https://www.facebook.com/omg-computer',
            'instagram_url' => 'https://www.instagram.com/omg_computer/',
            'tiktok_url' => 'https://www.tiktok.com/@omgcomputer',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('href="https://www.facebook.com/omg-computer"', false)
            ->assertSee('href="https://www.instagram.com/omg_computer/"', false)
            ->assertSee('href="https://www.tiktok.com/@omgcomputer"', false);
    }

    public function test_admin_can_update_facebook_page_details_and_profile_photo_through_meta(): void
    {
        config([
            'services.meta.app_id' => 'meta-app-id',
            'services.meta.app_secret' => 'meta-app-secret',
        ]);
        Http::fake(fn () => Http::response(['success' => true]));
        MetaPageConnection::query()->create([
            'page_id' => '123456',
            'page_name' => 'OMG Computer',
            'access_token' => 'page-token',
        ]);
        $this->actingAs($this->admin());

        $this->put(route('social-media.facebook.profile.update'), [
            'name' => 'OMG Computer Support',
            'about' => 'Computer repair and support.',
            'website' => 'https://omgcomputer.com',
        ])->assertRedirect(route('social-media.index'))
            ->assertSessionHas('social_media_result', fn (array $result): bool => $result['success']);

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/123456')
            && ($request['name'] ?? null) === 'OMG Computer Support'
            && ($request['about'] ?? null) === 'Computer repair and support.'
            && ($request['website'] ?? null) === 'https://omgcomputer.com');

        $this->put(route('social-media.facebook.picture.update'), [
            'picture_url' => 'https://omgcomputer.com/profile.jpg',
        ])->assertRedirect(route('social-media.index'))
            ->assertSessionHas('social_media_result', fn (array $result): bool => $result['success']);

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/123456/picture')
            && ($request['url'] ?? null) === 'https://omgcomputer.com/profile.jpg');
    }

    public function test_admin_can_manage_comments_on_connected_facebook_page_posts(): void
    {
        config([
            'services.meta.app_id' => 'meta-app-id',
            'services.meta.app_secret' => 'meta-app-secret',
        ]);
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($request->method() === 'GET' && str_ends_with($path, '/123456_789')) {
                return Http::response(['id' => '123456_789', 'from' => ['id' => '123456']]);
            }
            if ($request->method() === 'GET' && str_ends_with($path, '/123456_789/comments')) {
                return Http::response(['data' => [[
                    'id' => '123456_456789',
                    'message' => 'Great service',
                    'from' => ['name' => 'Customer'],
                    'created_time' => '2026-11-13T10:00:00+0000',
                ]]]);
            }

            return Http::response(['id' => 'reply-1', 'success' => true]);
        });
        MetaPageConnection::query()->create([
            'page_id' => '123456',
            'page_name' => 'OMG Computer',
            'access_token' => 'page-token',
        ]);
        $this->actingAs($this->admin());

        $this->get(route('meta-ads.page.posts.comments', '123456_789'))
            ->assertOk()
            ->assertSee('Customer')
            ->assertSee('Great service');

        $this->post(route('meta-ads.page.comments.reply', ['123456_789', '123456_456789']), [
            'message' => 'Thank you!',
        ])->assertRedirect(route('meta-ads.page.posts.comments', '123456_789'));

        $this->delete(route('meta-ads.page.comments.delete', ['123456_789', '123456_456789']))
            ->assertRedirect(route('meta-ads.page.posts.comments', '123456_789'));

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/123456_456789/comments')
            && ($request['message'] ?? null) === 'Thank you!');
        Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/123456_456789'));
    }

    public function test_admin_can_manage_comments_on_instagram_media(): void
    {
        config([
            'services.meta.app_id' => 'meta-app-id',
            'services.meta.app_secret' => 'meta-app-secret',
            'services.meta.instagram_user_id' => '1781000',
            'services.meta.instagram_access_token' => 'instagram-token',
        ]);
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($request->method() === 'GET' && str_ends_with($path, '/112233')) {
                return Http::response(['id' => '112233', 'owner' => ['id' => '1781000']]);
            }
            if ($request->method() === 'GET' && str_ends_with($path, '/112233/comments')) {
                return Http::response(['data' => [[
                    'id' => '445566',
                    'text' => 'Love this build',
                    'username' => 'customer',
                    'timestamp' => '2026-11-13T10:00:00+0000',
                    'hidden' => false,
                ]]]);
            }

            return Http::response(['id' => 'reply-1', 'success' => true]);
        });
        $this->actingAs($this->admin());

        $this->get(route('social-media.instagram.comments', '112233'))
            ->assertOk()
            ->assertSee('@customer')
            ->assertSee('Love this build');

        $this->post(route('social-media.instagram.comments.reply', ['112233', '445566']), [
            'message' => 'Thank you!',
        ])->assertRedirect(route('social-media.instagram.comments', '112233'));

        $this->patch(route('social-media.instagram.comments.visibility', ['112233', '445566']), [
            'hidden' => '1',
        ])->assertRedirect(route('social-media.instagram.comments', '112233'));

        $this->delete(route('social-media.instagram.comments.delete', ['112233', '445566']))
            ->assertRedirect(route('social-media.instagram.comments', '112233'));

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/445566/replies')
            && ($request['message'] ?? null) === 'Thank you!');
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/445566')
            && ($request['hide'] ?? null) === 'true');
        Http::assertSent(fn ($request): bool => $request->method() === 'DELETE'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/445566'));
    }

    public function test_profile_links_only_accept_the_matching_social_network(): void
    {
        $this->actingAs($this->admin())
            ->from(route('social-media.index'))
            ->put(route('social-media.profiles.update'), [
                'facebook_url' => 'https://www.instagram.com/omg_computer/',
                'instagram_url' => 'https://www.facebook.com/omgcomputer',
                'tiktok_url' => 'https://example.com/omgcomputer',
            ])
            ->assertRedirect(route('social-media.index'))
            ->assertSessionHasErrors(['facebook_url', 'instagram_url', 'tiktok_url']);

        $this->assertDatabaseMissing('company_profiles', ['id' => 'primary']);
    }

    public function test_non_admin_cannot_manage_social_media_profiles(): void
    {
        $member = User::factory()->create();
        $member->assignRole(Role::findOrCreate('member', 'web'));

        $this->actingAs($member)->get(route('social-media.index'))->assertForbidden();
    }

    public function test_admin_can_connect_tiktok_read_profile_statistics_and_disconnect(): void
    {
        config([
            'services.tiktok.client_key' => 'tiktok-client-key',
            'services.tiktok.client_secret' => 'tiktok-client-secret',
            'services.tiktok.redirect_uri' => null,
        ]);
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($request->method() === 'POST' && str_ends_with($path, '/v2/oauth/token/')) {
                return Http::response([
                    'open_id' => 'user-open-id',
                    'access_token' => 'tiktok-access-token',
                    'refresh_token' => 'tiktok-refresh-token',
                    'scope' => 'user.info.basic,user.info.profile,user.info.stats',
                    'expires_in' => 7200,
                    'refresh_expires_in' => 15552000,
                ]);
            }
            if ($request->method() === 'GET' && str_ends_with($path, '/v2/user/info/')) {
                return Http::response([
                    'data' => ['user' => [
                        'open_id' => 'user-open-id',
                        'display_name' => 'OMG Computer',
                        'username' => 'omgcomputer',
                        'bio_description' => 'Computer support',
                        'follower_count' => 4200,
                        'following_count' => 120,
                        'likes_count' => 9000,
                        'video_count' => 40,
                    ]],
                    'error' => ['code' => 'ok', 'message' => ''],
                ]);
            }

            return Http::response(['error' => 'ok']);
        });
        $admin = $this->admin();

        $connectResponse = $this->actingAs($admin)->get(route('social-media.tiktok.connect'));
        $connectResponse->assertRedirectContains('https://www.tiktok.com/v2/auth/authorize/');
        parse_str((string) parse_url($connectResponse->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('user.info.basic,user.info.profile,user.info.stats', $query['scope'] ?? null);
        $this->assertSame(session('social_media_tiktok_state'), $query['state'] ?? null);

        $this->get(route('social-media.tiktok.callback', [
            'state' => session('social_media_tiktok_state'),
            'code' => 'authorization-code',
        ]))->assertRedirect(route('social-media.index'))
            ->assertSessionHas('social_media_result', fn (array $result): bool => $result['success']);

        $connection = TikTokConnection::query()->firstOrFail();
        $this->assertSame('user-open-id', $connection->open_id);
        $this->assertSame('tiktok-access-token', $connection->access_token);
        $this->assertNotSame('tiktok-access-token', $connection->getRawOriginal('access_token'));

        $this->get(route('social-media.index'))
            ->assertOk()
            ->assertSee('OMG Computer')
            ->assertSee('4,200')
            ->assertSee('TikTok’s Display API is read-only.');

        $this->delete(route('social-media.tiktok.disconnect'))
            ->assertRedirect(route('social-media.index'))
            ->assertSessionHas('social_media_result', fn (array $result): bool => $result['success']);
        $this->assertDatabaseCount('tiktok_connections', 0);
    }

    public function test_tiktok_oauth_callback_rejects_an_invalid_state(): void
    {
        config([
            'services.tiktok.client_key' => 'tiktok-client-key',
            'services.tiktok.client_secret' => 'tiktok-client-secret',
        ]);

        $this->actingAs($this->admin())
            ->get(route('social-media.tiktok.callback', ['state' => 'wrong', 'code' => 'authorization-code']))
            ->assertRedirect(route('social-media.index'))
            ->assertSessionHas('social_media_result', fn (array $result): bool => ! $result['success']);

        $this->assertDatabaseCount('tiktok_connections', 0);
    }

    public function test_expired_tiktok_access_tokens_refresh_and_rotate_securely(): void
    {
        config([
            'services.tiktok.client_key' => 'tiktok-client-key',
            'services.tiktok.client_secret' => 'tiktok-client-secret',
        ]);
        Http::fake(function ($request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($request->method() === 'POST' && str_ends_with($path, '/v2/oauth/token/')) {
                return Http::response([
                    'open_id' => 'user-open-id',
                    'access_token' => 'rotated-access-token',
                    'refresh_token' => 'rotated-refresh-token',
                    'scope' => 'user.info.basic,user.info.profile,user.info.stats',
                    'expires_in' => 7200,
                    'refresh_expires_in' => 15552000,
                ]);
            }

            return Http::response([
                'data' => ['user' => ['open_id' => 'user-open-id', 'follower_count' => 42]],
                'error' => ['code' => 'ok', 'message' => ''],
            ]);
        });
        $connection = TikTokConnection::query()->create([
            'open_id' => 'user-open-id',
            'access_token' => 'expired-access-token',
            'refresh_token' => 'valid-refresh-token',
            'access_token_expires_at' => now()->subMinute(),
            'refresh_token_expires_at' => now()->addDay(),
        ]);

        $profile = app(TikTokApi::class)->profile($connection);

        $this->assertSame(42, $profile['follower_count']);
        $connection->refresh();
        $this->assertSame('rotated-access-token', $connection->access_token);
        $this->assertSame('rotated-refresh-token', $connection->refresh_token);
        $this->assertNotSame('rotated-access-token', $connection->getRawOriginal('access_token'));
        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && ($request['grant_type'] ?? null) === 'refresh_token'
            && ($request['refresh_token'] ?? null) === 'valid-refresh-token');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }
}
