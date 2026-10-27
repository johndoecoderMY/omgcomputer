<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceShop;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MarketplaceApi
{
    private const SHOPEE_HOST = 'https://partner.shopeemobile.com';

    private const SHOPEE_REFRESH_TOKEN_TTL = 30 * 24 * 60 * 60;

    public function configured(string $platform): bool
    {
        return match ($platform) {
            'shopee' => filled(config('services.marketplaces.shopee.partner_id'))
                && filled(config('services.marketplaces.shopee.partner_key'))
                && filled(config('services.marketplaces.shopee.redirect_url')),
            'lazada' => filled(config('services.marketplaces.lazada.app_key'))
                && filled(config('services.marketplaces.lazada.app_secret'))
                && filled(config('services.marketplaces.lazada.redirect_url')),
            default => false,
        };
    }

    public function authorizationUrl(string $platform, string $state): string
    {
        if (! $this->configured($platform)) {
            throw new RuntimeException('Configure the marketplace app credentials and callback URL first.');
        }

        if ($platform === 'shopee') {
            $partnerId = (string) config('services.marketplaces.shopee.partner_id');
            $timestamp = (string) now()->timestamp;
            $path = '/api/v2/shop/auth_partner';
            $signature = hash_hmac(
                'sha256',
                $partnerId.$path.$timestamp,
                (string) config('services.marketplaces.shopee.partner_key'),
            );
            $redirect = config('services.marketplaces.shopee.redirect_url').'?state='.rawurlencode($state);

            return self::SHOPEE_HOST.$path.'?'.http_build_query([
                'partner_id' => $partnerId,
                'timestamp' => $timestamp,
                'sign' => $signature,
                'redirect' => $redirect,
            ]);
        }

        if ($platform === 'lazada') {
            return 'https://auth.lazada.com/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'force_auth' => 'true',
                'redirect_uri' => config('services.marketplaces.lazada.redirect_url'),
                'client_id' => config('services.marketplaces.lazada.app_key'),
                'state' => $state,
            ]);
        }

        throw new RuntimeException('This marketplace is not supported.');
    }

    /**
     * @return array{external_shop_id: string, shop_name: string, region: ?string, access_token: string, refresh_token: string, access_expires_in: int, refresh_expires_in: int}
     */
    public function exchangeAuthorizationCode(string $platform, string $code, ?string $shopId): array
    {
        if ($platform === 'shopee') {
            if (blank($shopId) || ! ctype_digit($shopId)) {
                throw new RuntimeException('Shopee did not return a valid shop ID.');
            }

            $partnerId = (string) config('services.marketplaces.shopee.partner_id');
            $path = '/api/v2/auth/token/get';
            $timestamp = (string) now()->timestamp;
            $response = $this->request('POST', self::SHOPEE_HOST.$path, [
                'partner_id' => $partnerId,
                'timestamp' => $timestamp,
                'sign' => $this->shopeeSign($path, $timestamp),
            ], [
                'code' => $code,
                'shop_id' => (int) $shopId,
                'partner_id' => (int) $partnerId,
            ]);
            $this->assertApiResponse($response, 'Shopee token exchange');
            $body = $response->json();

            return [
                'external_shop_id' => $shopId,
                'shop_name' => 'Shopee shop '.$shopId,
                'region' => 'MY',
                'access_token' => $this->requiredString($body, 'access_token', 'Shopee'),
                'refresh_token' => $this->requiredString($body, 'refresh_token', 'Shopee'),
                'access_expires_in' => $this->requiredLifetime($body, 'expire_in', 'Shopee'),
                'refresh_expires_in' => $this->shopeeRefreshLifetime($body),
            ];
        }

        if ($platform === 'lazada') {
            $data = $this->lazadaAuthRequest('/auth/token/create', ['code' => $code]);
            $userInfo = $data['country_user_info'] ?? [];
            $externalId = $data['user_id'] ?? ($userInfo[0]['seller_id'] ?? null);
            if (! is_string($externalId) && ! is_int($externalId)) {
                throw new RuntimeException('Lazada did not return a seller ID.');
            }

            return [
                'external_shop_id' => (string) $externalId,
                'shop_name' => 'Lazada shop '.$externalId,
                'region' => $userInfo[0]['country'] ?? 'MY',
                'access_token' => $this->requiredString($data, 'access_token', 'Lazada'),
                'refresh_token' => $this->requiredString($data, 'refresh_token', 'Lazada'),
                'access_expires_in' => $this->requiredLifetime($data, 'expires_in', 'Lazada'),
                'refresh_expires_in' => $this->requiredLifetime($data, 'refresh_expires_in', 'Lazada'),
            ];
        }

        throw new RuntimeException('This marketplace is not supported.');
    }

    public function importOrders(MarketplaceShop $shop, Carbon $from, Carbon $to): int
    {
        $this->ensureFreshToken($shop);
        $orders = $shop->platform === 'shopee'
            ? $this->shopeeOrders($shop, $from, $to)
            : $this->lazadaOrders($shop, $from, $to);
        $imported = $this->storeOrders($shop, $orders);

        $shop->forceFill(['last_order_import_at' => now()])->save();

        return $imported;
    }

    public function syncRecentShopeeOrders(MarketplaceShop $shop): int
    {
        if ($shop->platform !== 'shopee') {
            throw new RuntimeException('Automatic order sync is currently available for Shopee shops only.');
        }

        $this->ensureFreshToken($shop);
        $now = now();
        $from = $shop->last_order_import_at
            ? $shop->last_order_import_at->copy()->subMinutes(30)
            : $now->copy()->subDays(14);
        $from = $from->max($now->copy()->subDays(14));
        $orders = $this->shopeeOrders($shop, $from, $now, 'update_time');
        $imported = $this->storeOrders($shop, $orders);

        $shop->forceFill(['last_order_import_at' => $now])->save();

        return $imported;
    }

    /**
     * @param  list<array<string, mixed>>  $orders
     */
    private function storeOrders(MarketplaceShop $shop, array $orders): int
    {
        $imported = 0;

        foreach ($orders as $order) {
            $id = $shop->platform === 'shopee'
                ? ($order['order_sn'] ?? null)
                : ($order['order_number'] ?? null);

            if (! is_string($id) && ! is_int($id)) {
                continue;
            }

            $timestamp = $shop->platform === 'shopee'
                ? ($order['create_time'] ?? null)
                : ($order['created_at'] ?? null);

            $shop->orders()->updateOrCreate(
                ['external_order_id' => (string) $id],
                [
                    'status' => $order['order_status'] ?? $order['status'] ?? null,
                    'ordered_at' => $this->parseOrderDate($timestamp),
                    'currency' => $order['currency'] ?? null,
                    'total_amount' => $order['total_amount'] ?? $order['price'] ?? null,
                    'raw_payload' => $order,
                ],
            );
            $imported++;
        }

        return $imported;
    }

    private function ensureFreshToken(MarketplaceShop $shop): void
    {
        if (blank($shop->access_token) || blank($shop->refresh_token)) {
            throw new RuntimeException('Reconnect this shop before importing orders.');
        }

        if ($shop->access_token_expires_at?->isAfter(now()->addMinutes(5))) {
            return;
        }

        if ($shop->refresh_token_expires_at?->isPast()) {
            throw new RuntimeException('The shop authorization expired. Reconnect this shop before importing orders.');
        }

        $refreshed = $shop->platform === 'shopee'
            ? $this->refreshShopeeToken($shop)
            : $this->refreshLazadaToken($shop);

        $shop->forceFill([
            'access_token' => $refreshed['access_token'],
            'refresh_token' => $refreshed['refresh_token'],
            'access_token_expires_at' => now()->addSeconds($refreshed['access_expires_in']),
            'refresh_token_expires_at' => now()->addSeconds($refreshed['refresh_expires_in']),
        ])->save();
    }

    /**
     * @return array{access_token: string, refresh_token: string, access_expires_in: int, refresh_expires_in: int}
     */
    private function refreshShopeeToken(MarketplaceShop $shop): array
    {
        $path = '/api/v2/auth/access_token/get';
        $timestamp = (string) now()->timestamp;
        $partnerId = (string) config('services.marketplaces.shopee.partner_id');
        $response = $this->request('POST', self::SHOPEE_HOST.$path, [
            'partner_id' => $partnerId,
            'timestamp' => $timestamp,
            'sign' => $this->shopeeSign($path, $timestamp),
        ], [
            'refresh_token' => $shop->refresh_token,
            'shop_id' => (int) $shop->external_shop_id,
            'partner_id' => (int) $partnerId,
        ]);
        $this->assertApiResponse($response, 'Shopee token refresh');
        $body = $response->json();

        return [
            'access_token' => $this->requiredString($body, 'access_token', 'Shopee'),
            'refresh_token' => $this->requiredString($body, 'refresh_token', 'Shopee'),
            'access_expires_in' => $this->requiredLifetime($body, 'expire_in', 'Shopee'),
            'refresh_expires_in' => $this->shopeeRefreshLifetime($body),
        ];
    }

    /**
     * @return array{access_token: string, refresh_token: string, access_expires_in: int, refresh_expires_in: int}
     */
    private function refreshLazadaToken(MarketplaceShop $shop): array
    {
        $data = $this->lazadaAuthRequest('/auth/token/refresh', ['refresh_token' => $shop->refresh_token]);

        return [
            'access_token' => $this->requiredString($data, 'access_token', 'Lazada'),
            'refresh_token' => $this->requiredString($data, 'refresh_token', 'Lazada'),
            'access_expires_in' => $this->requiredLifetime($data, 'expires_in', 'Lazada'),
            'refresh_expires_in' => $this->requiredLifetime($data, 'refresh_expires_in', 'Lazada'),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function shopeeOrders(
        MarketplaceShop $shop,
        Carbon $from,
        Carbon $to,
        string $timeRangeField = 'create_time',
    ): array
    {
        $all = [];
        $cursor = '';
        $path = '/api/v2/order/get_order_list';
        $start = $timeRangeField === 'create_time'
            ? $from->copy()->startOfDay()->timestamp
            : $from->timestamp;
        $end = $timeRangeField === 'create_time'
            ? $to->copy()->endOfDay()->timestamp
            : $to->timestamp;

        for ($page = 0; $page < 100; $page++) {
            $data = $this->shopeeRequest($shop, $path, [
                'time_range_field' => $timeRangeField,
                'time_from' => $start,
                'time_to' => $end,
                'page_size' => 100,
                'cursor' => $cursor,
            ]);
            $pageOrders = $data['order_list'] ?? [];
            if (! is_array($pageOrders)) {
                throw new RuntimeException('Shopee returned an invalid order list.');
            }
            $all = [...$all, ...$pageOrders];
            if (! ($data['more'] ?? false)) {
                return $all;
            }

            $nextCursor = $data['next_cursor'] ?? '';
            if (! is_string($nextCursor) || $nextCursor === '' || $nextCursor === $cursor) {
                throw new RuntimeException('Shopee returned an invalid order pagination cursor.');
            }
            $cursor = $nextCursor;
        }

        throw new RuntimeException('Shopee returned too many order pages. Narrow the date range and retry.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lazadaOrders(MarketplaceShop $shop, Carbon $from, Carbon $to): array
    {
        $all = [];
        $offset = 0;
        $path = '/orders/get';

        for ($page = 0; $page < 100; $page++) {
            $data = $this->lazadaRequest($shop, $path, [
                'created_after' => $from->copy()->startOfDay()->toIso8601String(),
                'created_before' => $to->copy()->endOfDay()->toIso8601String(),
                'sort_by' => 'created_at',
                'sort_direction' => 'ASC',
                'offset' => $offset,
                'limit' => 100,
            ]);
            $pageOrders = $data['orders'] ?? [];
            if (! is_array($pageOrders)) {
                throw new RuntimeException('Lazada returned an invalid order list.');
            }
            $all = [...$all, ...$pageOrders];
            if (count($pageOrders) < 100) {
                return $all;
            }
            $offset += count($pageOrders);
        }

        throw new RuntimeException('Lazada returned too many order pages. Narrow the date range and retry.');
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function shopeeRequest(MarketplaceShop $shop, string $path, array $params): array
    {
        $partnerId = (string) config('services.marketplaces.shopee.partner_id');
        $timestamp = (string) now()->timestamp;
        $query = [
            ...$params,
            'partner_id' => (int) $partnerId,
            'timestamp' => $timestamp,
            'access_token' => $shop->access_token,
            'shop_id' => (int) $shop->external_shop_id,
        ];
        $query['sign'] = hash_hmac(
            'sha256',
            $partnerId.$path.$timestamp.$shop->access_token.$shop->external_shop_id,
            (string) config('services.marketplaces.shopee.partner_key'),
        );
        $response = $this->request('GET', self::SHOPEE_HOST.$path, $query);
        $this->assertApiResponse($response, 'Shopee order lookup');

        $data = $response->json('response');
        if (! is_array($data)) {
            throw new RuntimeException('Shopee returned an invalid order response.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function lazadaRequest(MarketplaceShop $shop, string $path, array $params): array
    {
        $params = [
            ...$params,
            'app_key' => (string) config('services.marketplaces.lazada.app_key'),
            'access_token' => (string) $shop->access_token,
            'sign_method' => 'sha256',
            'timestamp' => (string) now()->getTimestampMs(),
        ];
        $params['sign'] = $this->lazadaSign($path, $params);
        $response = $this->request(
            'GET',
            rtrim((string) config('services.marketplaces.lazada.api_url'), '/').$path,
            $params,
        );
        $this->assertLazadaResponse($response, 'Lazada order lookup');

        $data = $response->json('data', []);
        if (! is_array($data)) {
            throw new RuntimeException('Lazada returned an invalid order response.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function lazadaAuthRequest(string $path, array $params): array
    {
        $params = [
            ...$params,
            'app_key' => (string) config('services.marketplaces.lazada.app_key'),
            'sign_method' => 'sha256',
            'timestamp' => (string) now()->getTimestampMs(),
        ];
        $params['sign'] = $this->lazadaSign($path, $params);
        $response = $this->request(
            'GET',
            rtrim((string) config('services.marketplaces.lazada.auth_url'), '/').$path,
            $params,
        );
        $this->assertLazadaResponse($response, 'Lazada authorization');

        $data = $response->json('data', $response->json());

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function lazadaSign(string $path, array $params): string
    {
        ksort($params, SORT_STRING);
        $signingString = $path;
        foreach ($params as $key => $value) {
            if ($key !== 'sign' && ! is_array($value) && ! is_object($value)) {
                $signingString .= $key.(string) $value;
            }
        }

        return strtoupper(hash_hmac(
            'sha256',
            $signingString,
            (string) config('services.marketplaces.lazada.app_secret'),
        ));
    }

    private function shopeeSign(string $path, string $timestamp): string
    {
        return hash_hmac(
            'sha256',
            (string) config('services.marketplaces.shopee.partner_id').$path.$timestamp,
            (string) config('services.marketplaces.shopee.partner_key'),
        );
    }

    private function request(string $method, string $url, array $query = [], ?array $body = null): Response
    {
        try {
            $request = Http::acceptJson()->connectTimeout(5)->timeout(20);

            return $body === null
                ? $request->send($method, $url, ['query' => $query])
                : $request->send($method, $url, ['query' => $query, 'json' => $body]);
        } catch (ConnectionException $exception) {
            report($exception);
            throw new RuntimeException('Could not reach the marketplace API. Check the server connection and try again.');
        }
    }

    private function assertApiResponse(Response $response, string $operation): void
    {
        $body = $response->json();
        if (! $response->successful() || ! is_array($body) || ! empty($body['error'])) {
            $message = is_array($body) && is_string($body['message'] ?? null)
                ? Str::limit($body['message'], 300)
                : 'The marketplace rejected the request.';
            throw new RuntimeException("{$operation} failed: {$message}");
        }
    }

    private function assertLazadaResponse(Response $response, string $operation): void
    {
        $body = $response->json();
        if (! $response->successful() || ! is_array($body) || ! empty($body['code'])) {
            $message = is_array($body) && is_string($body['message'] ?? null)
                ? Str::limit($body['message'], 300)
                : 'The marketplace rejected the request.';
            throw new RuntimeException("{$operation} failed: {$message}");
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function requiredString(array $data, string $key, string $platform): string
    {
        $value = $data[$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new RuntimeException("{$platform} did not return a valid {$key}.");
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function requiredLifetime(array $data, string $key, string $platform): int
    {
        $value = filter_var($data[$key] ?? null, FILTER_VALIDATE_INT);
        if (! is_int($value) || $value <= 0) {
            throw new RuntimeException("{$platform} did not return a valid {$key} value.");
        }

        return $value;
    }

    /**
     * Shopee documents a fixed 30-day refresh-token lifetime but may omit it from token responses.
     *
     * @param  array<string, mixed>  $data
     */
    private function shopeeRefreshLifetime(array $data): int
    {
        if (! array_key_exists('refresh_expires_in', $data)) {
            return self::SHOPEE_REFRESH_TOKEN_TTL;
        }

        return $this->requiredLifetime($data, 'refresh_expires_in', 'Shopee');
    }

    private function parseOrderDate(mixed $value): ?Carbon
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return Carbon::createFromTimestamp((int) $value);
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $exception) {
            report($exception);
            throw new RuntimeException('The marketplace returned an invalid order date.');
        }
    }
}
