<?php

namespace Tests\Feature;

use App\Models\MarketplaceShop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarketplaceShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_shopee_connect_link_has_a_signed_authorization_request_and_state(): void
    {
        $admin = $this->admin();
        config([
            'services.marketplaces.shopee.partner_id' => '12345',
            'services.marketplaces.shopee.partner_key' => 'shopee-test-key',
            'services.marketplaces.shopee.redirect_url' => 'https://example.test/marketplaces/callback/shopee',
        ]);

        $response = $this->actingAs($admin)->get(route('marketplace-shops.connect', 'shopee'));
        $response->assertRedirectContains('/api/v2/shop/auth_partner');
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $timestamp = (string) ($query['timestamp'] ?? '');
        $expectedSign = hash_hmac(
            'sha256',
            '12345/api/v2/shop/auth_partner'.$timestamp,
            'shopee-test-key',
        );
        parse_str((string) parse_url((string) ($query['redirect'] ?? ''), PHP_URL_QUERY), $redirectQuery);

        $this->assertSame($expectedSign, $query['sign'] ?? null);
        $this->assertNotEmpty($redirectQuery['state'] ?? null);
        $this->assertSame($redirectQuery['state'], session('marketplace_oauth_state.shopee'));
    }

    public function test_admin_can_view_marketplace_setup_and_customer_cannot_access_it(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('marketplace-shops.index'))
            ->assertOk()
            ->assertSee('E-commerce Shops')
            ->assertSee('SHOPEE_PARTNER_KEY')
            ->assertSee('LAZADA_APP_SECRET')
            ->assertSee('php artisan config:clear')
            ->assertSee('table-bordered', false);

        $customer = User::factory()->create();
        Role::findOrCreate('customer', 'web');
        $customer->assignRole('customer');
        $this->actingAs($customer)->get(route('marketplace-shops.index'))->assertForbidden();
    }

    public function test_shopee_oauth_callback_saves_encrypted_shop_tokens(): void
    {
        $admin = $this->admin();
        config([
            'services.marketplaces.shopee.partner_id' => '12345',
            'services.marketplaces.shopee.partner_key' => 'shopee-test-key',
            'services.marketplaces.shopee.redirect_url' => 'https://example.test/marketplaces/callback/shopee',
        ]);
        Http::fake([
            'https://partner.shopeemobile.com/api/v2/auth/token/get*' => Http::response([
                'access_token' => 'shopee-access-secret',
                'refresh_token' => 'shopee-refresh-secret',
                'expire_in' => 14400,
            ]),
        ]);

        $this->actingAs($admin)
            ->withSession(['marketplace_oauth_state' => ['shopee' => 'oauth-state']])
            ->get(route('marketplace-shops.callback', [
                'platform' => 'shopee',
                'state' => 'oauth-state',
                'code' => 'one-use-code',
                'shop_id' => '98765',
            ]))
            ->assertRedirect(route('marketplace-shops.index'));

        $shop = MarketplaceShop::query()->where('platform', 'shopee')->firstOrFail();
        $this->assertSame('98765', $shop->external_shop_id);
        $this->assertSame('shopee-access-secret', $shop->access_token);
        $this->assertSame('shopee-refresh-secret', $shop->refresh_token);
        $this->assertTrue($shop->refresh_token_expires_at->between(now()->addDays(29), now()->addDays(31)));
        $this->assertNotSame('shopee-access-secret', $shop->getRawOriginal('access_token'));
        $this->assertNotSame('shopee-refresh-secret', $shop->getRawOriginal('refresh_token'));

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'POST'
            && str_contains($request->url(), '/api/v2/auth/token/get')
            && $request['code'] === 'one-use-code'
            && $request['shop_id'] === 98765);
    }

    public function test_admin_can_manually_import_shopee_orders_with_signed_api_request(): void
    {
        $admin = $this->admin();
        config([
            'services.marketplaces.shopee.partner_id' => '12345',
            'services.marketplaces.shopee.partner_key' => 'shopee-test-key',
        ]);
        $shop = MarketplaceShop::query()->create([
            'platform' => 'shopee',
            'external_shop_id' => '98765',
            'shop_name' => 'Test Shopee Store',
            'region' => 'MY',
            'access_token' => 'valid-access-token',
            'refresh_token' => 'valid-refresh-token',
            'access_token_expires_at' => now()->addHours(2),
            'refresh_token_expires_at' => now()->addDays(10),
        ]);
        $createdAt = now()->subDay()->timestamp;
        Http::fake([
            'https://partner.shopeemobile.com/api/v2/order/get_order_list*' => Http::response([
                'response' => [
                    'order_list' => [[
                        'order_sn' => 'ORDER-1001',
                        'order_status' => 'READY_TO_SHIP',
                        'create_time' => $createdAt,
                    ]],
                    'more' => false,
                    'next_cursor' => '',
                ],
            ]),
        ]);

        $this->actingAs($admin)->post(route('marketplace-shops.orders.import', $shop), [
            'from' => now()->subDays(3)->toDateString(),
            'to' => now()->toDateString(),
        ])->assertRedirect()->assertSessionHas('marketplace_result.success', true);

        $this->assertDatabaseHas('marketplace_orders', [
            'marketplace_shop_id' => $shop->id,
            'external_order_id' => 'ORDER-1001',
            'status' => 'READY_TO_SHIP',
        ]);

        Http::assertSent(function (ClientRequest $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            $base = '12345/api/v2/order/get_order_list'.$query['timestamp'].'valid-access-token98765';

            return $request->method() === 'GET'
                && ($query['time_range_field'] ?? null) === 'create_time'
                && ($query['page_size'] ?? null) === '100'
                && hash_equals(hash_hmac('sha256', $base, 'shopee-test-key'), $query['sign'] ?? '');
        });

        $this->actingAs($admin)->get(route('marketplace-shops.index'))
            ->assertOk()
            ->assertSee('ORDER-1001')
            ->assertSee('Test Shopee Store');
    }

    public function test_scheduled_shopee_sync_updates_orders_by_last_update_time(): void
    {
        config([
            'services.marketplaces.shopee.partner_id' => '12345',
            'services.marketplaces.shopee.partner_key' => 'shopee-test-key',
        ]);
        $shop = MarketplaceShop::query()->create([
            'platform' => 'shopee',
            'external_shop_id' => '98765',
            'shop_name' => 'Test Shopee Store',
            'access_token' => 'valid-access-token',
            'refresh_token' => 'valid-refresh-token',
            'access_token_expires_at' => now()->addHours(2),
            'refresh_token_expires_at' => now()->addDays(10),
        ]);
        $order = $shop->orders()->create([
            'external_order_id' => 'ORDER-1001',
            'status' => 'READY_TO_SHIP',
            'raw_payload' => ['order_sn' => 'ORDER-1001'],
        ]);
        Http::fake([
            'https://partner.shopeemobile.com/api/v2/order/get_order_list*' => Http::response([
                'response' => [
                    'order_list' => [[
                        'order_sn' => 'ORDER-1001',
                        'order_status' => 'SHIPPED',
                        'create_time' => now()->subDays(2)->timestamp,
                        'update_time' => now()->timestamp,
                    ]],
                    'more' => false,
                    'next_cursor' => '',
                ],
            ]),
        ]);

        $this->artisan('marketplaces:sync-shopee-orders')->assertSuccessful();

        $this->assertSame('SHIPPED', $order->fresh()->status);
        $this->assertSame('SHIPPED', $order->fresh()->raw_payload['order_status']);
        $this->assertNotNull($shop->fresh()->last_order_import_at);
        Http::assertSent(function (ClientRequest $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && str_contains($request->url(), '/api/v2/order/get_order_list')
                && ($query['time_range_field'] ?? null) === 'update_time';
        });
    }

    public function test_lazada_oauth_uses_signed_token_exchange_and_stores_the_seller(): void
    {
        $admin = $this->admin();
        config([
            'services.marketplaces.lazada.app_key' => 'lazada-test-app',
            'services.marketplaces.lazada.app_secret' => 'lazada-test-secret',
            'services.marketplaces.lazada.redirect_url' => 'https://example.test/marketplaces/callback/lazada',
        ]);
        Http::fake([
            'https://auth.lazada.com/rest/auth/token/create*' => Http::response([
                'code' => '0',
                'access_token' => 'lazada-access-secret',
                'refresh_token' => 'lazada-refresh-secret',
                'expires_in' => 864000,
                'refresh_expires_in' => 4320000,
                'user_id' => 78910,
                'country_user_info' => [['seller_id' => '78910', 'country' => 'MY']],
            ]),
        ]);

        $this->actingAs($admin)
            ->withSession(['marketplace_oauth_state' => ['lazada' => 'lazada-state']])
            ->get(route('marketplace-shops.callback', [
                'platform' => 'lazada',
                'state' => 'lazada-state',
                'code' => 'lazada-one-use-code',
            ]))
            ->assertRedirect(route('marketplace-shops.index'));

        $shop = MarketplaceShop::query()->where('platform', 'lazada')->firstOrFail();
        $this->assertSame('78910', $shop->external_shop_id);
        $this->assertSame('MY', $shop->region);
        $this->assertSame('lazada-access-secret', $shop->access_token);

        Http::assertSent(function (ClientRequest $request): bool {
            $queryString = (string) parse_url($request->url(), PHP_URL_QUERY);
            parse_str($queryString, $query);
            $actualSignature = $query['sign'] ?? '';
            unset($query['sign']);
            ksort($query, SORT_STRING);
            $signingString = '/auth/token/create';
            foreach ($query as $key => $value) {
                $signingString .= $key.$value;
            }

            return str_contains($request->url(), '/auth/token/create')
                && ($query['code'] ?? null) === 'lazada-one-use-code'
                && ctype_digit((string) ($query['timestamp'] ?? ''))
                && strlen((string) $query['timestamp']) >= 13
                && hash_equals(
                    strtoupper(hash_hmac('sha256', $signingString, 'lazada-test-secret')),
                    $actualSignature,
                );
        });
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        return $admin;
    }
}
