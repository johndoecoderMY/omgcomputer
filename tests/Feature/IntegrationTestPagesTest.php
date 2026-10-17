<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IntegrationTestPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_check_whatsapp_phone_profile_without_sending_a_message(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        config([
            'services.meta.access_token' => 'test-meta-token',
            'services.meta.whatsapp_phone_number_id' => '123456789',
            'services.meta.graph_api_version' => 'v25.0',
        ]);
        Http::fake([
            'https://graph.facebook.com/v25.0/123456789*' => Http::response([
                'id' => '123456789',
                'display_phone_number' => '+60 12-345 6789',
                'verified_name' => 'Example Repairs',
                'quality_rating' => 'GREEN',
                'code_verification_status' => 'VERIFIED',
                'platform_type' => 'CLOUD_API',
            ]),
        ]);

        $this->actingAs($admin)->get(route('reports.integrations.whatsapp'))
            ->assertOk()
            ->assertSee('WhatsApp Cloud API')
            ->assertSee('Test connection');
        $this->actingAs($admin)->post(route('reports.integrations.whatsapp.check'))
            ->assertRedirect(route('reports.integrations.whatsapp'));

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'GET'
            && $request->hasHeader('Authorization', 'Bearer test-meta-token')
            && str_contains($request->url(), 'fields=id%2Cdisplay_phone_number'));

        $this->actingAs($admin)->get(route('reports.integrations.whatsapp'))
            ->assertOk()
            ->assertSee('Connection successful')
            ->assertSee('Example Repairs')
            ->assertSee('+60 12-345 6789')
            ->assertDontSee('test-meta-token');
    }

    public function test_whatsapp_check_reports_meta_auth_failure_without_exposing_token(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        config([
            'services.meta.access_token' => 'private-meta-token',
            'services.meta.whatsapp_phone_number_id' => '123456789',
        ]);
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'error' => ['message' => 'Invalid OAuth access token.'],
            ], 401),
        ]);

        $this->actingAs($admin)->get(route('reports.integrations.whatsapp'))->assertOk();
        $this->actingAs($admin)->post(route('reports.integrations.whatsapp.check'))
            ->assertRedirect(route('reports.integrations.whatsapp'));
        $this->actingAs($admin)->get(route('reports.integrations.whatsapp'))
            ->assertOk()
            ->assertSee('Invalid OAuth access token.')
            ->assertSee('Connection not verified')
            ->assertDontSee('private-meta-token');
    }

    public function test_easyparcel_rate_checker_returns_quoted_rates_without_booking(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        config([
            'services.delivery.authentication' => 'test-easyparcel-auth',
            'services.delivery.api_key' => 'test-easyparcel-key',
            'services.delivery.api_url' => 'https://demo.connect.easyparcel.my/',
            'services.delivery.environment' => 'sandbox',
        ]);
        Http::fake([
            'https://demo.connect.easyparcel.my/*' => Http::response([
                'result' => [[
                    'status' => 'Success',
                    'remarks' => '',
                    'rates' => [[
                        'courier_name' => 'Example Courier',
                        'service_name' => 'Parcel delivery',
                        'service_detail' => 'pickup',
                        'delivery' => '1-2 days',
                        'price' => '8.50',
                    ]],
                ]],
            ]),
        ]);

        $this->actingAs($admin)->get(route('reports.integrations.easyparcel'))
            ->assertOk()
            ->assertSee('EasyParcel Rate Checker')
            ->assertSee('Pickup postcode')
            ->assertSee('Check courier rates');
        $this->actingAs($admin)->post(route('reports.integrations.easyparcel.rates'), [
            'pick_code' => '10050',
            'pick_state' => 'png',
            'send_code' => '11950',
            'send_state' => 'png',
            'weight' => '1',
        ])->assertRedirect(route('reports.integrations.easyparcel'));

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'POST'
            && str_contains($request->url(), 'ac=MPRateCheckingBulk')
            && $request['authentication'] === 'test-easyparcel-auth'
            && $request['api'] === 'test-easyparcel-key'
            && $request['bulk'][0]['pick_country'] === 'MY'
            && $request['bulk'][0]['send_country'] === 'MY');

        $this->actingAs($admin)->get(route('reports.integrations.easyparcel'))
            ->assertOk()
            ->assertSee('Rate lookup result')
            ->assertSee('Example Courier')
            ->assertSee('RM 8.50')
            ->assertSee('No shipment was booked.')
            ->assertDontSee('test-easyparcel-key');
    }

    public function test_customer_cannot_use_admin_integration_test_pages(): void
    {
        $customer = User::factory()->create();
        Role::findOrCreate('customer', 'web');
        $customer->assignRole('customer');

        $this->actingAs($customer)->get(route('reports.integrations.whatsapp'))->assertForbidden();
        $this->actingAs($customer)->get(route('reports.integrations.easyparcel'))->assertForbidden();
    }
}
