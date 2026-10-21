<?php

namespace Tests\Feature;

use App\Mail\IntegrationTestMail;
use App\Models\WhatsAppInboundMessage;
use App\Notifications\IntegrationTestNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
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
            ->assertSee('Webhook callback URL')
            ->assertSee('META_APP_SECRET')
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

    public function test_meta_can_verify_the_whatsapp_webhook_with_the_configured_token(): void
    {
        config(['services.meta.webhook_verify_token' => 'verify-secret']);

        $this->get('/webhooks/meta/whatsapp?hub.mode=subscribe&hub.verify_token=verify-secret&hub.challenge=test-challenge')
            ->assertOk()
            ->assertSeeText('test-challenge');

        $this->get('/webhooks/meta/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=test-challenge')
            ->assertForbidden();
    }

    public function test_signed_whatsapp_webhook_stores_inbound_messages_once(): void
    {
        config([
            'services.meta.app_secret' => 'webhook-app-secret',
            'services.meta.whatsapp_phone_number_id' => 'phone-id-123',
        ]);
        $payload = json_encode([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => ['phone_number_id' => 'phone-id-123'],
                        'contacts' => [[
                            'wa_id' => '60123456789',
                            'profile' => ['name' => 'Example Customer'],
                        ]],
                        'messages' => [[
                            'id' => 'wamid.example-message-1',
                            'from' => '60123456789',
                            'timestamp' => '1792580000',
                            'type' => 'text',
                            'text' => ['body' => 'I need help with my laptop.'],
                        ]],
                    ],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);
        $signature = 'sha256='.hash_hmac('sha256', $payload, 'webhook-app-secret');

        foreach ([1, 2] as $_) {
            $this->call('POST', route('webhooks.whatsapp.receive'), [], [], [], [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ], $payload)->assertOk();
        }

        $this->assertDatabaseCount('whatsapp_inbound_messages', 1);
        $this->assertDatabaseHas('whatsapp_inbound_messages', [
            'meta_message_id' => 'wamid.example-message-1',
            'wa_id' => '60123456789',
            'contact_name' => 'Example Customer',
            'message_type' => 'text',
            'message_body' => 'I need help with my laptop.',
        ]);
    }

    public function test_whatsapp_webhook_rejects_invalid_signatures(): void
    {
        config([
            'services.meta.app_secret' => 'webhook-app-secret',
            'services.meta.whatsapp_phone_number_id' => 'phone-id-123',
        ]);
        $payload = json_encode(['object' => 'whatsapp_business_account'], JSON_THROW_ON_ERROR);

        $this->call('POST', route('webhooks.whatsapp.receive'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=invalid',
        ], $payload)->assertUnauthorized();

        $this->assertDatabaseCount('whatsapp_inbound_messages', 0);
    }

    public function test_admin_can_view_and_poll_the_whatsapp_inbox(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        WhatsAppInboundMessage::query()->create([
            'meta_message_id' => 'wamid.inbox-test',
            'phone_number_id' => 'phone-id-123',
            'wa_id' => '60123456789',
            'contact_name' => 'Inbox Customer',
            'message_type' => 'text',
            'message_body' => 'Are you open today?',
            'received_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('reports.integrations.whatsapp.inbox'))
            ->assertOk()
            ->assertSee('WhatsApp Inbox')
            ->assertSee('Inbox Customer')
            ->assertSee('Are you open today?');

        $this->actingAs($admin)->getJson(route('reports.integrations.whatsapp.inbox.poll'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('messages.0.wa_id', '60123456789')
            ->assertJsonPath('messages.0.message_body', 'Are you open today?');
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
        $this->actingAs($customer)->get(route('reports.integrations.whatsapp.inbox'))->assertForbidden();
        $this->actingAs($customer)->get(route('reports.integrations.easyparcel'))->assertForbidden();
        $this->actingAs($customer)->get(route('reports.integrations.notifications'))->assertForbidden();
        $this->actingAs($customer)->post(route('reports.integrations.notifications.send'))->assertForbidden();
        $this->actingAs($customer)->post(route('reports.integrations.email.send'))->assertForbidden();
    }

    public function test_admin_can_send_a_test_notification_to_their_own_account(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        Notification::fake();

        $this->actingAs($admin)->get(route('reports.integrations.notifications'))
            ->assertOk()
            ->assertSee('Notification & Email Tests')
            ->assertSee('Send test notification')
            ->assertSee($admin->email);

        $this->actingAs($admin)->post(route('reports.integrations.notifications.send'))
            ->assertRedirect(route('reports.integrations.notifications'));

        Notification::assertSentTo($admin, IntegrationTestNotification::class, fn (IntegrationTestNotification $notification): bool => $notification->title === 'Test notification');
    }

    public function test_admin_can_send_a_test_email_to_their_own_account(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        Mail::fake();

        $this->actingAs($admin)->post(route('reports.integrations.email.send'))
            ->assertRedirect(route('reports.integrations.notifications'));

        Mail::assertSent(IntegrationTestMail::class, fn (IntegrationTestMail $mail): bool => $mail->recipientName === $admin->name
            && $mail->hasTo($admin->email));
    }
}
