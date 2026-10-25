<?php

namespace App\Services;

use App\Models\MetaAdsConnection;
use App\Models\MetaPageConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaGraphApi
{
    private const OBJECTIVES = [
        'OUTCOME_AWARENESS',
        'OUTCOME_TRAFFIC',
        'OUTCOME_ENGAGEMENT',
        'OUTCOME_LEADS',
        'OUTCOME_APP_PROMOTION',
        'OUTCOME_SALES',
    ];

    public function configured(): bool
    {
        return filled(config('services.meta.app_id'))
            && filled(config('services.meta.app_secret'));
    }

    public function authorizationUrl(string $state, string $redirectUri): string
    {
        $this->assertConfigured();

        return 'https://www.facebook.com/'.$this->graphVersion().'/dialog/oauth?'.http_build_query([
            'client_id' => config('services.meta.app_id'),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'scope' => 'ads_read,ads_management,pages_show_list,pages_read_engagement,pages_manage_posts',
        ]);
    }

    /**
     * @return array{access_token: string, expires_at: ?string, meta_user_id: string, accounts: list<array{id: string, name: string, account_id: string, currency: ?string, account_status: ?int}>, pages: list<array{id: string, name: string, access_token: string, category: ?string}>}
     */
    public function exchangeAuthorizationCode(string $code, string $redirectUri): array
    {
        $this->assertConfigured();

        $shortLived = $this->oauthRequest([
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);
        $shortToken = $this->requiredString($shortLived, 'access_token');

        $longLived = $this->oauthRequest([
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.meta.app_id'),
            'client_secret' => config('services.meta.app_secret'),
            'fb_exchange_token' => $shortToken,
        ]);
        $token = $this->requiredString($longLived, 'access_token');
        $expiresIn = $longLived['expires_in'] ?? null;

        $profile = $this->requestJson($token, 'GET', 'me', ['fields' => 'id']);
        $metaUserId = $profile['id'] ?? null;
        if (! is_string($metaUserId) || ! ctype_digit($metaUserId)) {
            throw new RuntimeException('Meta did not return a valid account ID for data-deletion handling.');
        }

        $accountResponse = $this->requestJson($token, 'GET', 'me/adaccounts', [
            'fields' => 'id,name,account_id,currency,account_status',
            'limit' => 100,
        ]);
        $accounts = [];
        foreach ($accountResponse['data'] ?? [] as $account) {
            if (! is_array($account)
                || ! is_string($account['id'] ?? null)
                || ! preg_match('/^act_[0-9]{1,30}$/', $account['id'])
                || ! is_string($account['name'] ?? null)
                || ! is_string($account['account_id'] ?? null)) {
                continue;
            }

            $accounts[] = [
                'id' => $account['id'],
                'name' => $account['name'],
                'account_id' => $account['account_id'],
                'currency' => is_string($account['currency'] ?? null) ? $account['currency'] : null,
                'account_status' => is_int($account['account_status'] ?? null) ? $account['account_status'] : null,
            ];
        }

        $pageResponse = $this->requestJson($token, 'GET', 'me/accounts', [
            'fields' => 'id,name,access_token,category',
            'limit' => 100,
        ]);
        $pages = [];
        foreach ($pageResponse['data'] ?? [] as $page) {
            if (! is_array($page)
                || ! is_string($page['id'] ?? null)
                || ! ctype_digit($page['id'])
                || ! is_string($page['name'] ?? null)
                || ! is_string($page['access_token'] ?? null)
                || $page['access_token'] === '') {
                continue;
            }

            $pages[] = [
                'id' => $page['id'],
                'name' => $page['name'],
                'access_token' => $page['access_token'],
                'category' => is_string($page['category'] ?? null) ? $page['category'] : null,
            ];
        }

        return [
            'access_token' => $token,
            'expires_at' => is_numeric($expiresIn) && (int) $expiresIn > 0
                ? now()->addSeconds((int) $expiresIn)->toIso8601String()
                : null,
            'meta_user_id' => $metaUserId,
            'accounts' => $accounts,
            'pages' => $pages,
        ];
    }

    /**
     * @return list<array{id: string, name: string, objective: string, status: string, effective_status: ?string, created_time: ?string}>
     */
    public function campaigns(MetaAdsConnection $connection): array
    {
        $this->assertUsableConnection($connection);
        $response = $this->requestJson($connection->access_token, 'GET', $connection->account_id.'/campaigns', [
            'fields' => 'id,name,objective,status,effective_status,created_time',
            'limit' => 100,
        ]);

        $campaigns = [];
        foreach ($response['data'] ?? [] as $campaign) {
            if (! is_array($campaign)
                || ! is_string($campaign['id'] ?? null)
                || ! ctype_digit($campaign['id'])
                || ! is_string($campaign['name'] ?? null)
                || ! is_string($campaign['objective'] ?? null)
                || ! is_string($campaign['status'] ?? null)) {
                continue;
            }

            $campaigns[] = [
                'id' => $campaign['id'],
                'name' => $campaign['name'],
                'objective' => $campaign['objective'],
                'status' => $campaign['status'],
                'effective_status' => is_string($campaign['effective_status'] ?? null)
                    ? $campaign['effective_status']
                    : null,
                'created_time' => is_string($campaign['created_time'] ?? null)
                    ? $campaign['created_time']
                    : null,
            ];
        }

        return $campaigns;
    }

    public function createCampaign(MetaAdsConnection $connection, string $name, string $objective): string
    {
        $this->assertUsableConnection($connection);
        if (! in_array($objective, self::OBJECTIVES, true)) {
            throw new RuntimeException('Choose a supported campaign objective.');
        }

        $response = $this->requestJson($connection->access_token, 'POST', $connection->account_id.'/campaigns', [
            'name' => $name,
            'objective' => $objective,
            'status' => 'PAUSED',
            'special_ad_categories' => '[]',
        ]);

        return $this->requiredString($response, 'id');
    }

    public function updateCampaign(MetaAdsConnection $connection, string $campaignId, string $name, string $status): void
    {
        $this->assertUsableConnection($connection);
        if (! ctype_digit($campaignId) || ! in_array($status, ['ACTIVE', 'PAUSED'], true)) {
            throw new RuntimeException('The campaign details are invalid.');
        }

        $campaign = $this->requestJson($connection->access_token, 'GET', $campaignId, [
            'fields' => 'id,account_id',
        ]);
        $campaignAccountId = $campaign['account_id'] ?? null;
        if (! is_string($campaignAccountId)
            || preg_replace('/^act_/', '', $campaignAccountId) !== preg_replace('/^act_/', '', $connection->account_id)) {
            throw new RuntimeException('That campaign does not belong to the connected ad account.');
        }

        $this->requestJson($connection->access_token, 'POST', $campaignId, [
            'name' => $name,
            'status' => $status,
        ]);
    }

    /**
     * @return array{page: array{id: string, name: string, category: ?string, fan_count: ?int, link: ?string}, posts: list<array{id: string, message: string, created_time: ?string, permalink_url: ?string}>}
     */
    public function pageContent(MetaPageConnection $connection): array
    {
        $this->assertPageConnectionUsable($connection);
        $page = $this->requestJson($connection->access_token, 'GET', $connection->page_id, [
            'fields' => 'id,name,category,fan_count,link',
        ]);
        if (($page['id'] ?? null) !== $connection->page_id || ! is_string($page['name'] ?? null)) {
            throw new RuntimeException('Meta did not return the connected Page details.');
        }

        $postResponse = $this->requestJson($connection->access_token, 'GET', $connection->page_id.'/posts', [
            'fields' => 'id,message,created_time,permalink_url',
            'limit' => 25,
        ]);
        $posts = [];
        foreach ($postResponse['data'] ?? [] as $post) {
            if (! is_array($post)
                || ! is_string($post['id'] ?? null)
                || ! preg_match('/^[0-9]+_[0-9]+$/', $post['id'])) {
                continue;
            }

            $posts[] = [
                'id' => $post['id'],
                'message' => is_string($post['message'] ?? null) ? $post['message'] : '',
                'created_time' => is_string($post['created_time'] ?? null) ? $post['created_time'] : null,
                'permalink_url' => is_string($post['permalink_url'] ?? null) ? $post['permalink_url'] : null,
            ];
        }

        return [
            'page' => [
                'id' => $connection->page_id,
                'name' => $connection->page_name,
                'category' => is_string($page['category'] ?? null) ? $page['category'] : $connection->category,
                'fan_count' => is_int($page['fan_count'] ?? null) ? $page['fan_count'] : null,
                'link' => is_string($page['link'] ?? null) ? $page['link'] : null,
            ],
            'posts' => $posts,
        ];
    }

    public function publishPagePost(MetaPageConnection $connection, string $message, ?string $link): string
    {
        $this->assertPageConnectionUsable($connection);
        $parameters = ['message' => $message];
        if ($link !== null && $link !== '') {
            $parameters['link'] = $link;
        }

        return $this->requiredString(
            $this->requestJson($connection->access_token, 'POST', $connection->page_id.'/feed', $parameters),
            'id',
        );
    }

    public function updatePagePost(MetaPageConnection $connection, string $postId, string $message): void
    {
        $this->assertPageConnectionUsable($connection);
        if (! preg_match('/^[0-9]+_[0-9]+$/', $postId)) {
            throw new RuntimeException('The Page post ID is invalid.');
        }

        $post = $this->requestJson($connection->access_token, 'GET', $postId, ['fields' => 'id,from']);
        if (($post['id'] ?? null) !== $postId || ($post['from']['id'] ?? null) !== $connection->page_id) {
            throw new RuntimeException('That post does not belong to the connected Page.');
        }

        $this->requestJson($connection->access_token, 'POST', $postId, ['message' => $message]);
    }

    public function assertPageConnectionUsable(MetaPageConnection $connection): void
    {
        if ($connection->token_expires_at?->isPast()) {
            throw new RuntimeException('The Meta authorization has expired. Reconnect the Page to continue.');
        }
    }

    private function oauthRequest(array $parameters): array
    {
        try {
            $response = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(30)
                ->get($this->graphBaseUrl().'oauth/access_token', $parameters);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not connect to Meta. Please try again.');
        }

        return $this->responseData($response);
    }

    private function requestJson(string $token, string $method, string $endpoint, array $parameters = []): array
    {
        $this->assertConfigured();
        $proof = hash_hmac('sha256', $token, (string) config('services.meta.app_secret'));
        $url = $this->graphBaseUrl().ltrim($endpoint, '/');

        try {
            $request = Http::withToken($token)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(30);

            $response = $method === 'POST'
                ? $request->asForm()->post($url.'?'.http_build_query(['appsecret_proof' => $proof]), $parameters)
                : $request->get($url, [...$parameters, 'appsecret_proof' => $proof]);
        } catch (ConnectionException) {
            throw new RuntimeException('Could not connect to Meta. Please try again.');
        }

        return $this->responseData($response);
    }

    private function responseData(Response $response): array
    {
        $body = $response->json();
        if (! $response->successful() || ! is_array($body) || isset($body['error'])) {
            $error = is_array($body) ? ($body['error'] ?? null) : null;
            $message = is_array($error) && is_string($error['message'] ?? null)
                ? trim(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $error['message']) ?? '')
                : '';
            $code = is_array($error) && is_numeric($error['code'] ?? null)
                ? ' (#'.(int) $error['code'].')'
                : '';

            if ($message !== '') {
                throw new RuntimeException('Meta API error'.$code.': '.mb_substr($message, 0, 600));
            }

            throw new RuntimeException('Meta rejected the request. Check the app permissions, connected asset access, and connection.');
        }

        return $body;
    }

    private function assertConfigured(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('Configure META_APP_ID and META_APP_SECRET before connecting to Meta.');
        }
    }

    private function assertUsableConnection(MetaAdsConnection $connection): void
    {
        if ($connection->token_expires_at?->isPast()) {
            throw new RuntimeException('The Meta authorization has expired. Reconnect the ad account to continue.');
        }
    }

    private function graphBaseUrl(): string
    {
        return 'https://graph.facebook.com/'.$this->graphVersion().'/';
    }

    private function graphVersion(): string
    {
        $version = (string) config('services.meta.graph_api_version', 'v25.0');
        if (! preg_match('/^v[0-9]{1,3}\.[0-9]{1,2}$/', $version)) {
            throw new RuntimeException('The configured Meta Graph API version is invalid.');
        }

        return $version;
    }

    private function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        if (! is_string($value) || $value === '') {
            throw new RuntimeException('Meta did not return the expected authorization or campaign data.');
        }

        return $value;
    }
}
