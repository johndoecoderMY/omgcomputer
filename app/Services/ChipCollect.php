<?php

namespace App\Services;

use App\Models\ChipSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ChipCollect
{
    public function isTestMode(): bool
    {
        return ChipSetting::testMode();
    }

    public function isConfigured(?bool $testMode = null): bool
    {
        $testMode ??= $this->isTestMode();

        return filled($this->secretKey($testMode)) && filled($this->brandId($testMode));
    }

    public function brandId(?bool $testMode = null): ?string
    {
        $testMode ??= $this->isTestMode();
        $brandId = $testMode
            ? (config('services.chip.test_brand_id') ?: config('services.chip.brand_id'))
            : config('services.chip.brand_id');

        return is_string($brandId) && $brandId !== '' ? $brandId : null;
    }

    public function testConnection(?bool $testMode = null): void
    {
        $key = $this->request($testMode)
            ->get($this->endpoint('/public_key/'))
            ->throw()
            ->json();

        if (! is_string($key) || openssl_pkey_get_public($key) === false) {
            throw new RuntimeException('CHIP returned an invalid public key. Check the API URL and credentials.');
        }
    }

    public function createPurchase(array $payload, ?bool $testMode = null): array
    {
        $response = $this->request($testMode)
            ->post($this->endpoint('/purchases/'), $payload)
            ->throw()
            ->json();
        if (! is_array($response)) {
            throw new RuntimeException('CHIP returned an invalid purchase response.');
        }

        return $response;
    }

    public function purchase(string $purchaseId, ?bool $testMode = null): array
    {
        $response = $this->request($testMode)
            ->get($this->endpoint('/purchases/'.rawurlencode($purchaseId).'/'))
            ->throw()
            ->json();
        if (! is_array($response)) {
            throw new RuntimeException('CHIP returned an invalid purchase status response.');
        }

        return $response;
    }

    public function verifyCallback(string $rawBody, ?string $signature, ?bool $testMode = null): bool
    {
        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $decodedSignature = base64_decode($signature, true);
        if (! is_string($decodedSignature)) {
            return false;
        }

        $publicKey = Cache::remember(
            'chip-collect-public-key-'.sha1((string) config('services.chip.api_url').':'.(int) ($testMode ?? $this->isTestMode())),
            now()->addHours(12),
            function () use ($testMode): string {
                $response = $this->request($testMode)->get($this->endpoint('/public_key/'))->throw();
                $key = $response->json();
                if (! is_string($key) || openssl_pkey_get_public($key) === false) {
                    throw new RuntimeException('CHIP returned an invalid callback public key.');
                }

                return $key;
            },
        );

        $result = openssl_verify($rawBody, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);

        return $result === 1;
    }

    private function request(?bool $testMode = null): PendingRequest
    {
        $testMode ??= $this->isTestMode();
        $secretKey = $this->secretKey($testMode);

        if (! is_string($secretKey) || $secretKey === '') {
            $mode = $testMode ? 'test' : 'live';
            throw new RuntimeException("CHIP {$mode} mode is not configured. Set the matching CHIP secret key and brand ID.");
        }

        return Http::acceptJson()
            ->asJson()
            ->withToken($secretKey)
            ->connectTimeout(10)
            ->timeout(30);
    }

    private function secretKey(bool $testMode): mixed
    {
        return $testMode
            ? config('services.chip.test_secret_key')
            : config('services.chip.secret_key');
    }

    private function endpoint(string $path): string
    {
        $baseUrl = rtrim((string) config('services.chip.api_url'), '/');
        if (! filter_var($baseUrl, FILTER_VALIDATE_URL) || parse_url($baseUrl, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('CHIP_API_URL must be a valid HTTPS URL.');
        }

        return $baseUrl.$path;
    }
}
