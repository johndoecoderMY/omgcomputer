<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class IntegrationTestController extends Controller
{
    public function whatsapp(): View
    {
        return view('reports.integrations.whatsapp', [
            'whatsappReady' => filled(config('services.meta.access_token'))
                && filled(config('services.meta.whatsapp_phone_number_id')),
            'phoneNumberIdConfigured' => filled(config('services.meta.whatsapp_phone_number_id')),
            'apiVersion' => config('services.meta.graph_api_version'),
        ]);
    }

    public function checkWhatsapp(): RedirectResponse
    {
        $accessToken = config('services.meta.access_token');
        $phoneNumberId = config('services.meta.whatsapp_phone_number_id');
        $apiVersion = config('services.meta.graph_api_version');

        if (blank($accessToken) || blank($phoneNumberId)) {
            return back()->with('integration_result', [
                'success' => false,
                'message' => 'Configure META_ACCESS_TOKEN and META_WHATSAPP_PHONE_NUMBER_ID before testing.',
            ]);
        }

        if (! is_string($apiVersion) || ! preg_match('/^v\d+\.\d+$/', $apiVersion)) {
            return back()->with('integration_result', [
                'success' => false,
                'message' => 'The configured Meta Graph API version is invalid. Use a version such as v25.0.',
            ]);
        }

        try {
            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(15)
                ->get("https://graph.facebook.com/{$apiVersion}/".rawurlencode((string) $phoneNumberId), [
                    'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,platform_type',
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('Meta WhatsApp connection test could not reach Graph API.', [
                'exception' => $exception::class,
            ]);

            return back()->with('integration_result', [
                'success' => false,
                'message' => 'Could not reach Meta Graph API. Check the server network connection and try again.',
            ]);
        }

        if (! $response->successful()) {
            $message = $response->json('error.message');

            return back()->with('integration_result', [
                'success' => false,
                'message' => is_string($message) && $message !== ''
                    ? mb_substr($message, 0, 400)
                    : "Meta returned HTTP {$response->status()}. Check the access token, phone-number ID, and permissions.",
            ]);
        }

        $phone = $response->json();
        if (! is_array($phone) || ! isset($phone['id'])) {
            return back()->with('integration_result', [
                'success' => false,
                'message' => 'Meta responded, but the response did not contain a WhatsApp phone-number profile.',
            ]);
        }

        return back()->with('integration_result', [
            'success' => true,
            'message' => 'Meta accepted the credentials and returned the WhatsApp phone-number profile.',
            'phone' => [
                'id' => $phone['id'],
                'display_phone_number' => $phone['display_phone_number'] ?? null,
                'verified_name' => $phone['verified_name'] ?? null,
                'quality_rating' => $phone['quality_rating'] ?? null,
                'code_verification_status' => $phone['code_verification_status'] ?? null,
                'platform_type' => $phone['platform_type'] ?? null,
            ],
        ]);
    }

    public function easyParcel(): View
    {
        $environment = config('services.delivery.environment', 'sandbox');
        $apiUrl = config('services.delivery.api_url');

        return view('reports.integrations.easyparcel', [
            'authenticationConfigured' => filled(config('services.delivery.authentication')),
            'apiKeyConfigured' => filled(config('services.delivery.api_key')),
            'environment' => $environment,
            'apiHost' => $this->easyParcelApiHost($apiUrl, $environment),
            'states' => $this->malaysianStates(),
        ]);
    }

    public function checkEasyParcel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pick_code' => ['required', 'regex:/^\d{5}$/'],
            'pick_state' => ['required', 'string', 'in:'.implode(',', array_keys($this->malaysianStates()))],
            'send_code' => ['required', 'regex:/^\d{5}$/'],
            'send_state' => ['required', 'string', 'in:'.implode(',', array_keys($this->malaysianStates()))],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);
        $authentication = config('services.delivery.authentication');
        $apiKey = config('services.delivery.api_key');

        if (blank($authentication) || blank($apiKey)) {
            return back()->withInput()->with('easyparcel_result', [
                'success' => false,
                'message' => 'Configure EASYPARCEL_AUTHENTICATION and EASYPARCEL_API_KEY before requesting rates.',
            ]);
        }

        $environment = config('services.delivery.environment', 'sandbox');
        $apiUrl = config('services.delivery.api_url');
        $endpoint = $this->easyParcelEndpoint($apiUrl, $environment);

        if ($endpoint === null) {
            return back()->withInput()->with('easyparcel_result', [
                'success' => false,
                'message' => 'The EasyParcel API URL must use HTTPS and point to connect.easyparcel.my or demo.connect.easyparcel.my.',
            ]);
        }

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->connectTimeout(5)
                ->timeout(20)
                ->post($endpoint, [
                    'authentication' => $authentication,
                    'api' => $apiKey,
                    'bulk' => [[
                        'pick_code' => $data['pick_code'],
                        'pick_state' => $data['pick_state'],
                        'pick_country' => 'MY',
                        'send_code' => $data['send_code'],
                        'send_state' => $data['send_state'],
                        'send_country' => 'MY',
                        'weight' => (string) $data['weight'],
                        'width' => '0',
                        'length' => '0',
                        'height' => '0',
                        'date_coll' => now()->toDateString(),
                    ]],
                ]);
        } catch (ConnectionException $exception) {
            Log::warning('EasyParcel rate test could not reach the configured API.', [
                'exception' => $exception::class,
            ]);

            return back()->withInput()->with('easyparcel_result', [
                'success' => false,
                'message' => 'Could not reach EasyParcel. Check the server network connection and API URL, then try again.',
            ]);
        }

        if (! $response->successful()) {
            return back()->withInput()->with('easyparcel_result', [
                'success' => false,
                'message' => "EasyParcel returned HTTP {$response->status()}. Check the API key and selected environment.",
            ]);
        }

        $payload = $response->json();
        $result = $this->easyParcelRateResult($payload);
        if ($result === null || strcasecmp((string) ($result['status'] ?? ''), 'success') !== 0) {
            $remarks = $result['remarks'] ?? $payload['remarks'] ?? null;

            return back()->withInput()->with('easyparcel_result', [
                'success' => false,
                'message' => is_string($remarks) && $remarks !== ''
                    ? mb_substr($remarks, 0, 400)
                    : 'EasyParcel responded, but the rate request was not successful. Check the postcode, state, and parcel weight.',
            ]);
        }

        $rates = collect($result['rates'] ?? [])
            ->filter(fn ($rate): bool => is_array($rate))
            ->map(fn (array $rate): array => [
                'courier_name' => $rate['courier_name'] ?? 'Courier',
                'service_name' => $rate['service_name'] ?? null,
                'service_detail' => $rate['service_detail'] ?? null,
                'delivery' => $rate['delivery'] ?? null,
                'price' => is_numeric($rate['price'] ?? null) ? (float) $rate['price'] : null,
            ])
            ->values()
            ->all();

        return back()->withInput()->with('easyparcel_result', [
            'success' => true,
            'message' => 'EasyParcel returned '.count($rates).' courier rate(s). No shipment was booked.',
            'rates' => $rates,
        ]);
    }

    private function easyParcelRateResult(mixed $payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }

        if (array_is_list($payload)) {
            $payload = $payload[0] ?? [];
            if (! is_array($payload)) {
                return null;
            }
        }

        $result = $payload['result'][0] ?? $payload['result'] ?? $payload['bulk'][0] ?? $payload;
        if (! is_array($result)) {
            return null;
        }

        if (! isset($result['rates']) && is_array($payload['rates'] ?? null)) {
            $result['rates'] = $payload['rates'];
        }

        if (! isset($result['status']) && isset($payload['status'])) {
            $result['status'] = $payload['status'];
        }

        return $result;
    }

    private function easyParcelEndpoint(?string $apiUrl, string $environment): ?string
    {
        $apiUrl = filled($apiUrl)
            ? trim($apiUrl)
            : ($environment === 'production'
                ? 'https://connect.easyparcel.my/'
                : 'https://demo.connect.easyparcel.my/');
        $parts = parse_url($apiUrl);

        if (! is_array($parts)
            || strtolower($parts['scheme'] ?? '') !== 'https'
            || ! in_array(strtolower($parts['host'] ?? ''), ['connect.easyparcel.my', 'demo.connect.easyparcel.my'], true)) {
            return null;
        }

        parse_str($parts['query'] ?? '', $query);
        $query['ac'] = 'MPRateCheckingBulk';
        $url = 'https://'.$parts['host'].($parts['path'] ?? '/');

        return $url.'?'.http_build_query($query);
    }

    private function easyParcelApiHost(?string $apiUrl, string $environment): string
    {
        $endpoint = $this->easyParcelEndpoint($apiUrl, $environment);

        return $endpoint === null ? 'Invalid API URL' : (string) parse_url($endpoint, PHP_URL_HOST);
    }

    private function malaysianStates(): array
    {
        return [
            'jhr' => 'Johor',
            'kdh' => 'Kedah',
            'ktn' => 'Kelantan',
            'mlk' => 'Melaka',
            'nsn' => 'Negeri Sembilan',
            'phg' => 'Pahang',
            'png' => 'Pulau Pinang',
            'prk' => 'Perak',
            'pls' => 'Perlis',
            'sbh' => 'Sabah',
            'sgr' => 'Selangor',
            'swk' => 'Sarawak',
            'trg' => 'Terengganu',
            'kul' => 'Kuala Lumpur',
            'pjy' => 'Putrajaya',
            'lbn' => 'Labuan',
        ];
    }
}
