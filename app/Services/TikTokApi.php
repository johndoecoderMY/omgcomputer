<?php

namespace App\Services;

use App\Models\TikTokConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TikTokApi
{
    public function configured(): bool
    {
        return filled(config('services.tiktok.client_key'))
            && filled(config('services.tiktok.client_secret'));
    }

    public function authorizationUrl(string $state, string $redirectUri): string
    {
        $this->assertConfigured();

        return 'https://www.tiktok.com/v2/auth/authorize/?'.http_build_query([
            'client_key' => config('services.tiktok.client_key'),
            'response_type' => 'code',
            'scope' => 'user.info.basic,user.info.profile,user.info.stats',
            'redirect_uri' => $redirectUri,
            'state' => $state,
        ]);
    }

    public function exchangeAuthorizationCode(string $code, string $redirectUri): array
    {
        $response = $this->tokenRequest([
            'client_key' => config('services.tiktok.client_key'),
            'client_secret' => config('services.tiktok.client_secret'),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        return $this->validatedTokenData($response);
    }

    public function profile(TikTokConnection $connection): array
    {
        $this->refreshIfNeeded($connection);

        try {
            $response = Http::withToken($connection->access_token)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(30)
                ->get('https://open.tiktokapis.com/v2/user/info/', [
                    'fields' => 'open_id,avatar_url,display_name,bio_description,profile_deep_link,username,follower_count,following_count,likes_count,video_count',
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to TikTok. Please try again.', previous: $exception);
        }

        $data = $response->json();
        if (! $response->successful()
            || ! is_array($data)
            || (($data['error']['code'] ?? null) !== 'ok')
            || ! is_array($data['data']['user'] ?? null)) {
            throw new RuntimeException($this->errorMessage($data, 'TikTok could not return profile details.'));
        }

        return $data['data']['user'];
    }

    public function revoke(TikTokConnection $connection): void
    {
        $this->assertConfigured();

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(30)
                ->post('https://open.tiktokapis.com/v2/oauth/revoke/', [
                    'client_key' => config('services.tiktok.client_key'),
                    'client_secret' => config('services.tiktok.client_secret'),
                    'token' => $connection->access_token,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to TikTok to revoke authorization.', previous: $exception);
        }

        $data = $response->json();
        if (! $response->successful()
            || ! is_array($data)
            || (! is_array($data['error'] ?? null) && ($data['error'] ?? null) !== 'ok')
            || (is_array($data['error'] ?? null) && ($data['error']['code'] ?? null) !== 'ok')) {
            throw new RuntimeException($this->errorMessage($data, 'TikTok could not revoke the authorization.'));
        }
    }

    private function refreshIfNeeded(TikTokConnection $connection): void
    {
        if (! $connection->access_token_expires_at || $connection->access_token_expires_at->isFuture()) {
            return;
        }
        if ($connection->refresh_token_expires_at?->isPast()) {
            throw new RuntimeException('TikTok authorization expired. Reconnect the account to continue.');
        }

        $tokens = $this->tokenRequest([
            'client_key' => config('services.tiktok.client_key'),
            'client_secret' => config('services.tiktok.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $connection->refresh_token,
        ]);
        $tokenData = $this->validatedTokenData($tokens);

        $connection->update([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'scope' => $tokenData['scope'] ?? $connection->scope,
            'access_token_expires_at' => $tokenData['access_token_expires_at'],
            'refresh_token_expires_at' => $tokenData['refresh_token_expires_at'],
        ]);
    }

    private function tokenRequest(array $parameters): array
    {
        $this->assertConfigured();

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(30)
                ->post('https://open.tiktokapis.com/v2/oauth/token/', $parameters);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to TikTok authorization. Please try again.', previous: $exception);
        }

        $data = $response->json();
        if (! $response->successful() || ! is_array($data) || isset($data['error'])) {
            throw new RuntimeException($this->errorMessage($data, 'TikTok authorization could not be completed.'));
        }

        return $data;
    }

    private function validatedTokenData(array $data): array
    {
        foreach (['open_id', 'access_token', 'refresh_token'] as $required) {
            if (! is_string($data[$required] ?? null) || $data[$required] === '') {
                throw new RuntimeException('TikTok returned incomplete authorization data.');
            }
        }
        if (! is_numeric($data['expires_in'] ?? null) || ! is_numeric($data['refresh_expires_in'] ?? null)) {
            throw new RuntimeException('TikTok returned invalid token expiry data.');
        }

        return [
            'open_id' => $data['open_id'],
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'scope' => is_string($data['scope'] ?? null) ? $data['scope'] : null,
            'access_token_expires_at' => now()->addSeconds((int) $data['expires_in']),
            'refresh_token_expires_at' => now()->addSeconds((int) $data['refresh_expires_in']),
        ];
    }

    private function errorMessage(mixed $data, string $fallback): string
    {
        $message = is_array($data)
            ? (data_get($data, 'error.message')
                ?? ($data['error_description'] ?? null)
                ?? (is_string($data['error'] ?? null) && $data['error'] !== 'ok' ? $data['error'] : null))
            : null;

        return is_string($message) && $message !== '' ? mb_substr($message, 0, 500) : $fallback;
    }

    private function assertConfigured(): void
    {
        if (! $this->configured()) {
            throw new RuntimeException('Set TIKTOK_CLIENT_KEY and TIKTOK_CLIENT_SECRET before connecting a TikTok account.');
        }
    }
}
