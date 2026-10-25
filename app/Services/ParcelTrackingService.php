<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ParcelTrackingService
{
    public function isConfigured(): bool
    {
        return filled(config('services.parcel_tracking.api_key'));
    }

    public function track(string $trackingNumber): array
    {
        $apiKey = config('services.parcel_tracking.api_key');
        if (blank($apiKey)) {
            throw new RuntimeException('Configure TRACK17_API_KEY on the server before tracking parcels.');
        }

        $baseUrl = rtrim((string) config('services.parcel_tracking.api_url', 'https://api.17track.net'), '/');
        $urlParts = parse_url($baseUrl);
        if (! is_array($urlParts)
            || strtolower($urlParts['scheme'] ?? '') !== 'https'
            || strtolower($urlParts['host'] ?? '') !== 'api.17track.net'
            || isset($urlParts['user'])
            || isset($urlParts['pass'])
            || isset($urlParts['query'])
            || isset($urlParts['fragment'])
            || ! in_array($urlParts['path'] ?? '', ['', '/'], true)) {
            throw new RuntimeException('The 17TRACK API URL must use the official HTTPS API host.');
        }

        $request = Http::withHeaders(['17token' => $apiKey])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20);

        try {
            $registered = $request->post($baseUrl.'/track/v2.4/register', [
                ['number' => $trackingNumber],
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not reach 17TRACK. Check the server connection and try again.', 0, $exception);
        }

        if (! $registered->successful()) {
            throw new RuntimeException("17TRACK could not register this tracking number (HTTP {$registered->status()}).");
        }

        $registration = $registered->json();
        if (! is_array($registration) || (int) ($registration['code'] ?? -1) !== 0) {
            throw new RuntimeException('17TRACK rejected this tracking request. Check the API key and available tracking quota.');
        }

        $registrationData = is_array($registration['data'] ?? null) ? $registration['data'] : [];
        $registeredItem = collect($registrationData['accepted'] ?? [])
            ->first(fn ($item): bool => is_array($item)
                && strcasecmp((string) ($item['number'] ?? ''), $trackingNumber) === 0);
        if (! is_array($registeredItem)) {
            $rejection = collect($registrationData['rejected'] ?? [])
                ->first(fn ($item): bool => is_array($item)
                    && strcasecmp((string) ($item['number'] ?? ''), $trackingNumber) === 0);
            $reason = data_get($rejection, 'error.message');

            throw new RuntimeException(is_string($reason) && $reason !== ''
                ? mb_substr($reason, 0, 300)
                : '17TRACK did not accept this tracking number. Check the number and available tracking quota.');
        }

        $trackingRequest = ['number' => $trackingNumber];
        if (is_numeric($registeredItem['carrier'] ?? null)) {
            $trackingRequest['carrier'] = (int) $registeredItem['carrier'];
        }

        try {
            $response = $request->post($baseUrl.'/track/v2.4/gettrackinfo', [
                $trackingRequest,
            ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('The parcel was registered, but its tracking details could not be retrieved. Please try again.', 0, $exception);
        }

        if (! $response->successful()) {
            throw new RuntimeException("17TRACK could not retrieve tracking details (HTTP {$response->status()}).");
        }

        $payload = $response->json();
        if (! is_array($payload) || (int) ($payload['code'] ?? -1) !== 0) {
            throw new RuntimeException('17TRACK returned an invalid tracking response.');
        }

        $trackingData = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $item = collect($trackingData['accepted'] ?? [])
            ->first(fn ($item): bool => is_array($item)
                && strcasecmp((string) ($item['number'] ?? ''), $trackingNumber) === 0);
        if (! is_array($item)) {
            throw new RuntimeException('17TRACK has not returned a result for this number yet. Please try again shortly.');
        }

        $trackInfo = is_array($item['track_info'] ?? null) ? $item['track_info'] : [];
        $latestStatus = is_array($trackInfo['latest_status'] ?? null) ? $trackInfo['latest_status'] : [];
        $latestEvent = is_array($trackInfo['latest_event'] ?? null) ? $trackInfo['latest_event'] : [];
        $providers = $trackInfo['tracking']['providers'] ?? [];
        $events = collect($providers)
            ->filter(fn ($provider): bool => is_array($provider))
            ->flatMap(fn (array $provider) => collect($provider['events'] ?? [])
                ->filter(fn ($event): bool => is_array($event))
                ->map(fn (array $event): array => [
                    'time' => $event['time_iso'] ?? $event['time_utc'] ?? null,
                    'location' => $event['location'] ?? null,
                    'description' => $event['description']
                        ?? data_get($event, 'description_translation.description')
                        ?? $event['sub_status']
                        ?? null,
                    'stage' => $event['stage'] ?? null,
                ]))
            ->values()
            ->all();

        return [
            'number' => $trackingNumber,
            'carrier' => data_get($providers, '0.provider.name') ?? data_get($providers, '0.provider.alias'),
            'status' => $this->statusLabel((string) ($latestStatus['status'] ?? '')),
            'status_description' => $latestStatus['sub_status_descr'] ?? null,
            'latest_update' => $latestEvent['time_iso'] ?? $latestEvent['time_utc'] ?? null,
            'latest_event' => $latestEvent['description']
                ?? data_get($latestEvent, 'description_translation.description')
                ?? null,
            'events' => $events,
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'NotFound' => 'Not found',
            'InfoReceived' => 'Info received',
            'InTransit' => 'In transit',
            'Expired' => 'Expired',
            'AvailableForPickup' => 'Available for pickup',
            'OutForDelivery' => 'Out for delivery',
            'DeliveryFailure' => 'Delivery failed',
            'Delivered' => 'Delivered',
            'Exception' => 'Exception',
            default => 'Tracking update pending',
        };
    }
}
