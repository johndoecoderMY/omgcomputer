<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ChipCollect
{
    public function testConnection(): void
    {
        $key = $this->request()
            ->get($this->endpoint('/public_key/'))
            ->throw()
            ->json();

        if (! is_string($key) || openssl_pkey_get_public($key) === false) {
            throw new RuntimeException('CHIP returned an invalid public key. Check the API URL and credentials.');
        }
    }

    public function createPurchase(array $payload): array
    {
        $response = $this->request()
            ->post($this->endpoint('/purchases/'), $payload)
            ->throw()
            ->json();
        if (! is_array($response)) {
            throw new RuntimeException('CHIP returned an invalid purchase response.');
        }

        return $response;
    }

    public function purchase(string $purchaseId): array
    {
        $response = $this->request()
            ->get($this->endpoint('/purchases/'.rawurlencode($purchaseId).'/'))
            ->throw()
            ->json();
        if (! is_array($response)) {
            throw new RuntimeException('CHIP returned an invalid purchase status response.');
        }

        return $response;
    }

    public function verifyCallback(string $rawBody, ?string $signature): bool
    {
        if (! is_string($signature) || $signature === '') {
            return false;
        }

        $decodedSignature = base64_decode($signature, true);
        if (! is_string($decodedSignature)) {
            return false;
        }

        $publicKey = Cache::remember(
            'chip-collect-public-key-'.sha1((string) config('services.chip.api_url')),
            now()->addHours(12),
            function (): string {
                $response = $this->request()->get($this->endpoint('/public_key/'))->throw();
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

    private function request(): PendingRequest
    {
        $secretKey = config('services.chip.secret_key');
        if (! is_string($secretKey) || $secretKey === '') {
            throw new RuntimeException('CHIP is not configured. Set CHIP_SECRET_KEY and CHIP_BRAND_ID.');
        }

        return Http::acceptJson()
            ->asJson()
            ->withToken($secretKey)
            ->connectTimeout(10)
            ->timeout(30);
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
