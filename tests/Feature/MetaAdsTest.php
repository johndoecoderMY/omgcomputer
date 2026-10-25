<?php

namespace Tests\Feature;

use App\Models\MetaAdsConnection;
use App\Models\MetaPageConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MetaAdsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.app_id' => 'meta-app-id',
            'services.meta.app_secret' => 'meta-app-secret',
            'services.meta.graph_api_version' => 'v25.0',
        ]);
    }

    public function test_only_admins_can_access_meta_ads(): void
    {
        $member = User::factory()->create();
        Role::findOrCreate('member', 'web');
        $member->assignRole('member');

        $this->actingAs($member)->get(route('meta-ads.index'))->assertForbidden();
    }

    public function test_admin_can_start_oauth_with_state_and_required_permissions(): void
    {
        $response = $this->actingAs($this->admin())->get(route('meta-ads.connect'));

        $response->assertRedirectContains('https://www.facebook.com/v25.0/dialog/oauth');
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame(
            'ads_read,ads_management,pages_show_list,pages_read_engagement,pages_manage_posts',
            $query['scope'] ?? null,
        );
        $this->assertSame(route('meta-ads.callback'), $query['redirect_uri'] ?? null);
        $this->assertSame(session('meta_ads_oauth_state'), $query['state'] ?? null);
    }

    public function test_oauth_callback_lists_accounts_and_selected_token_is_encrypted(): void
    {
        Http::fake([
            'https://graph.facebook.com/v25.0/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-lived-token', 'expires_in' => 3600])
                ->push(['access_token' => 'long-lived-token', 'expires_in' => 5184000]),
            'https://graph.facebook.com/v25.0/me/adaccounts*' => Http::response([
                'data' => [[
                    'id' => 'act_123456',
                    'name' => 'Primary account',
                    'account_id' => '123456',
                    'currency' => 'MYR',
                    'account_status' => 1,
                ]],
            ]),
            'https://graph.facebook.com/v25.0/me/accounts*' => Http::response(['data' => []]),
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->withSession(['meta_ads_oauth_state' => 'expected-state'])
            ->get(route('meta-ads.callback', ['state' => 'expected-state', 'code' => 'one-time-code']))
            ->assertRedirect(route('meta-ads.index'))
            ->assertSessionHas('success', 'Choose the Meta ad account and/or Page to connect.');

        $this->get(route('meta-ads.index'))
            ->assertOk()
            ->assertSee('Choose Meta assets')
            ->assertSee('Primary account');

        $this->post(route('meta-ads.account.select'), ['account_id' => 'act_123456'])
            ->assertRedirect(route('meta-ads.index'));

        $connection = MetaAdsConnection::query()->firstOrFail();
        $this->assertSame('act_123456', $connection->account_id);
        $this->assertSame('long-lived-token', $connection->access_token);
        $this->assertNotSame('long-lived-token', $connection->getRawOriginal('access_token'));
        $this->assertSame($admin->id, $connection->connected_by_user_id);

        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), 'fb_exchange_token')
            && str_contains($request->url(), 'short-lived-token'));
    }

    public function test_page_account_is_selected_and_page_token_is_encrypted(): void
    {
        Http::fake([
            'https://graph.facebook.com/v25.0/oauth/access_token*' => Http::sequence()
                ->push(['access_token' => 'short-user-token', 'expires_in' => 3600])
                ->push(['access_token' => 'long-user-token', 'expires_in' => 5184000]),
            'https://graph.facebook.com/v25.0/me/adaccounts*' => Http::response(['data' => []]),
            'https://graph.facebook.com/v25.0/me/accounts*' => Http::response(['data' => [[
                'id' => '123456',
                'name' => 'OMG Computer Page',
                'access_token' => 'page-access-secret',
                'category' => 'Computer Store',
            ]]]),
        ]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->withSession(['meta_ads_oauth_state' => 'page-state'])
            ->get(route('meta-ads.callback', ['state' => 'page-state', 'code' => 'page-code']))
            ->assertRedirect(route('meta-ads.index'))
            ->assertSessionHas('success', 'Choose the Meta ad account and/or Page to connect.');

        $this->get(route('meta-ads.index'))
            ->assertOk()
            ->assertSee('OMG Computer Page');

        $this->post(route('meta-ads.page.select'), ['page_id' => '123456'])
            ->assertRedirect(route('meta-ads.index'));

        $pageConnection = MetaPageConnection::query()->firstOrFail();
        $this->assertSame('123456', $pageConnection->page_id);
        $this->assertSame('page-access-secret', $pageConnection->access_token);
        $this->assertNotSame('page-access-secret', $pageConnection->getRawOriginal('access_token'));
        $this->assertSame('Computer Store', $pageConnection->category);
        $this->assertSame($admin->id, $pageConnection->connected_by_user_id);
    }

    public function test_page_details_posts_publish_and_post_update_use_page_api(): void
    {
        $admin = $this->admin();
        MetaPageConnection::query()->create([
            'page_id' => '123456',
            'page_name' => 'OMG Computer Page',
            'category' => 'Computer Store',
            'access_token' => 'page-access-secret',
            'connected_by_user_id' => $admin->id,
        ]);
        Http::fake(function (ClientRequest $request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($request->method() === 'GET' && str_ends_with($path, '/123456')) {
                return Http::response([
                    'id' => '123456',
                    'name' => 'OMG Computer Page',
                    'category' => 'Computer Store',
                    'fan_count' => 1250,
                    'link' => 'https://facebook.example.test/omgcomputer',
                ]);
            }
            if ($request->method() === 'GET' && str_ends_with($path, '/123456/posts')) {
                return Http::response(['data' => [[
                    'id' => '123456_987654',
                    'message' => 'Existing post',
                    'created_time' => '2026-10-25T12:00:00+0000',
                    'permalink_url' => 'https://facebook.example.test/posts/987654',
                ]]]);
            }
            if ($request->method() === 'POST' && str_ends_with($path, '/123456/feed')) {
                return Http::response(['id' => '123456_987655']);
            }
            if ($request->method() === 'GET' && str_ends_with($path, '/123456_987654')) {
                return Http::response(['id' => '123456_987654', 'from' => ['id' => '123456']]);
            }
            if ($request->method() === 'POST' && str_ends_with($path, '/123456_987654')) {
                return Http::response(['success' => true]);
            }

            return Http::response(['error' => ['message' => 'Unexpected request']], 400);
        });

        $this->actingAs($admin)->get(route('meta-ads.index'))
            ->assertOk()
            ->assertSee('OMG Computer Page')
            ->assertSee('1,250 followers')
            ->assertSee('Existing post');

        $this->post(route('meta-ads.page.posts.store'), [
            'message' => 'New Page announcement',
            'link' => 'https://example.test/product',
        ])->assertRedirect(route('meta-ads.index'));

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/123456/feed')
            && ($request['message'] ?? null) === 'New Page announcement'
            && ($request['link'] ?? null) === 'https://example.test/product');

        $this->patch(route('meta-ads.page.posts.update', '123456_987654'), [
            'message' => 'Edited post',
        ])->assertRedirect(route('meta-ads.index'));

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/123456_987654')
            && ($request['message'] ?? null) === 'Edited post');
    }

    public function test_page_post_from_another_page_cannot_be_updated(): void
    {
        $admin = $this->admin();
        MetaPageConnection::query()->create([
            'page_id' => '123456',
            'page_name' => 'OMG Computer Page',
            'access_token' => 'page-access-secret',
            'connected_by_user_id' => $admin->id,
        ]);
        Http::fake([
            'https://graph.facebook.com/v25.0/123456_987654*' => Http::response([
                'id' => '123456_987654',
                'from' => ['id' => '999999'],
            ]),
        ]);

        $this->actingAs($admin)->patch(route('meta-ads.page.posts.update', '123456_987654'), [
            'message' => 'Attempted update',
        ])->assertRedirect(route('meta-ads.index'))
            ->assertSessionHas('meta_ads_result', fn (array $result): bool => ! $result['success']);

        Http::assertSentCount(1);
    }

    public function test_account_selection_reuses_a_connection_even_after_its_row_id_has_advanced(): void
    {
        $admin = $this->admin();
        $firstConnection = MetaAdsConnection::query()->create([
            'account_id' => 'act_111111',
            'account_name' => 'Previous account',
            'access_token' => 'previous-token',
            'connected_by_user_id' => $admin->id,
        ]);
        $firstConnection->delete();
        $nextConnection = MetaAdsConnection::query()->create([
            'account_id' => 'act_222222',
            'account_name' => 'Temporary account',
            'access_token' => 'temporary-token',
            'connected_by_user_id' => $admin->id,
        ]);
        $this->assertGreaterThan(1, $nextConnection->id);

        $this->actingAs($admin)
            ->withSession(['meta_ads_pending' => [
                'access_token' => 'new-token',
                'expires_at' => null,
                'accounts' => [[
                    'id' => 'act_333333',
                    'name' => 'Selected account',
                    'account_id' => '333333',
                    'currency' => 'MYR',
                    'account_status' => 1,
                ]],
            ]])
            ->post(route('meta-ads.account.select'), ['account_id' => 'act_333333'])
            ->assertRedirect(route('meta-ads.index'));

        $this->assertDatabaseCount('meta_ads_connections', 1);
        $this->assertSame('act_333333', MetaAdsConnection::query()->firstOrFail()->account_id);
    }

    public function test_campaign_creation_starts_paused_and_status_update_is_scoped_to_connected_account(): void
    {
        $admin = $this->admin();
        MetaAdsConnection::query()->create([
            'account_id' => 'act_123456',
            'account_name' => 'Primary account',
            'access_token' => 'long-lived-token',
            'connected_by_user_id' => $admin->id,
        ]);
        Http::fake(function (ClientRequest $request) {
            if ($request->method() === 'GET' && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/campaigns')) {
                return Http::response(['data' => [[
                    'id' => '987654321',
                    'name' => 'Spring sale',
                    'objective' => 'OUTCOME_TRAFFIC',
                    'status' => 'PAUSED',
                    'effective_status' => 'PAUSED',
                    'created_time' => '2026-10-25T12:00:00+0000',
                ]]]);
            }
            if ($request->method() === 'POST' && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/campaigns')) {
                return Http::response(['id' => '987654322']);
            }
            if ($request->method() === 'GET' && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/987654321')) {
                return Http::response(['id' => '987654321', 'account_id' => '123456']);
            }
            if ($request->method() === 'POST' && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/987654321')) {
                return Http::response(['success' => true]);
            }

            return Http::response(['error' => ['message' => 'Unexpected request']], 400);
        });

        $this->actingAs($admin)->get(route('meta-ads.index'))
            ->assertOk()
            ->assertSee('Spring sale')
            ->assertSee('Create paused');

        $this->post(route('meta-ads.campaigns.store'), [
            'name' => 'New campaign',
            'objective' => 'OUTCOME_TRAFFIC',
        ])->assertRedirect(route('meta-ads.index'));

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/act_123456/campaigns')
            && ($request['status'] ?? null) === 'PAUSED'
            && ($request['objective'] ?? null) === 'OUTCOME_TRAFFIC');

        $this->patch(route('meta-ads.campaigns.update', '987654321'), [
            'name' => 'Spring sale',
            'status' => 'ACTIVE',
        ])->assertRedirect(route('meta-ads.index'));

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'POST'
            && str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/987654321')
            && ($request['status'] ?? null) === 'ACTIVE');
    }

    public function test_campaign_from_another_account_cannot_be_updated(): void
    {
        $admin = $this->admin();
        MetaAdsConnection::query()->create([
            'account_id' => 'act_123456',
            'account_name' => 'Primary account',
            'access_token' => 'long-lived-token',
            'connected_by_user_id' => $admin->id,
        ]);
        Http::fake([
            'https://graph.facebook.com/v25.0/987654321*' => Http::response([
                'id' => '987654321',
                'account_id' => '654321',
            ]),
        ]);

        $this->actingAs($admin)->patch(route('meta-ads.campaigns.update', '987654321'), [
            'name' => 'Other account campaign',
            'status' => 'PAUSED',
        ])->assertRedirect(route('meta-ads.index'))
            ->assertSessionHas('meta_ads_result', fn (array $result): bool => ! $result['success']);

        Http::assertSentCount(1);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        return $admin;
    }
}
