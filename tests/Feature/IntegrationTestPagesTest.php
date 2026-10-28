<?php

namespace Tests\Feature;

use App\Mail\IntegrationTestMail;
use App\Models\User;
use App\Models\WhatsAppInboundMessage;
use App\Notifications\IntegrationTestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IntegrationTestPagesTest extends TestCase
{
    public function test_admin_can_test_chip_connection_without_exposing_secret(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        config([
            'services.chip.secret_key' => 'private-chip-secret',
            'services.chip.brand_id' => 'brand-123',
            'services.chip.api_url' => 'https://gate.chip-in.asia/api/v1',
            'services.chip.currency' => 'MYR',
            'services.chip.enabled' => true,
        ]);
        $publicKey = "-----BEGIN PUBLIC KEY-----\n".
            "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqPF3VE4+gT75ApxadwRf\n".
            "bw4gGIx2d4xw9bK4SIi9kGvbRv1piftAl/cbqvjm5fE99q0UulHjU0MCQgLDvj5L\n".
            "kSzspKhzIs3wkbu4/i+MBj78GMv4Qa453dTiVcZG5A0F1988gQPei8s0do281iXT\n".
            "tW/o43CQXHKPnG7iIi47QCT7JoTI8iX8VJizMtfAowIGNWh803UuYbti4dRyic/C\n".
            "grBkzuwHBbvtpcp8ncVQ6wKUkUn5ZbXBbrhBNmbXgDzQgsb4HErymjqYcd2ySyVD\n".
            "1jNA1MMnJZhbDqrBRhYjsbUtJTrSpPlHD2XezGVKVQKzJVPemAbSS3/40zkaRAaD\n".
            "EQIDAQAB\n-----END PUBLIC KEY-----";
        Http::fake([
            'https://gate.chip-in.asia/api/v1/public_key/' => Http::response(json_encode($publicKey), 200, ['Content-Type' => 'application/json']),
        ]);

        $this->actingAs($admin)->get(route('reports.integrations.chip'))
            ->assertOk()
            ->assertSee('CHIP Collect')
            ->assertSee('gate.chip-in.asia')
            ->assertDontSee('private-chip-secret');
        $this->actingAs($admin)->post(route('reports.integrations.chip.check'))
            ->assertRedirect(route('reports.integrations.chip'));
        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'GET'
            && $request->url() === 'https://gate.chip-in.asia/api/v1/public_key/'
            && $request->hasHeader('Authorization', 'Bearer private-chip-secret'));
        $this->actingAs($admin)->get(route('reports.integrations.chip'))
            ->assertOk()
            ->assertSee('Connection successful')
            ->assertDontSee('private-chip-secret');
    }

    public function test_chip_connection_test_requires_admin_access(): void
    {
        Role::findOrCreate('member', 'web');
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->get(route('reports.integrations.chip'))->assertForbidden();
        $this->actingAs($member)->post(route('reports.integrations.chip.check'))->assertForbidden();
    }

    use RefreshDatabase;

    public function test_admin_can_test_cpanel_connection_without_exposing_the_api_token(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        config([
            'services.cpanel.enabled' => true,
            'services.cpanel.host' => 'cpanel.example.test',
            'services.cpanel.port' => 2083,
            'services.cpanel.username' => 'test-cpanel-user',
            'services.cpanel.api_token' => 'private-cpanel-token',
            'services.cpanel.email_domain' => 'example.test',
        ]);
        Http::fake([
            'https://cpanel.example.test:2083/execute/Email/list_pops*' => Http::response([
                'data' => [
                    ['suspended_incoming' => 0, 'suspended_login' => 0, 'login' => 'team@example.test', 'email' => 'team@example.test'],
                    ['suspended_incoming' => 0, 'suspended_login' => 0, 'login' => 'support@example.test', 'email' => 'support@example.test'],
                    ['login' => 'Main Account', 'email' => 'test-cpanel-user'],
                ],
                'errors' => null,
                'warnings' => null,
                'status' => 1,
                'messages' => null,
                'metadata' => ['transformed' => 1],
            ]),
        ]);

        $this->actingAs($admin)->get(route('reports.integrations.cpanel'))
            ->assertOk()
            ->assertSee('cPanel Email UAPI')
            ->assertSee('cpanel.example.test')
            ->assertSee('example.test')
            ->assertDontSee('private-cpanel-token');

        $this->actingAs($admin)->post(route('reports.integrations.cpanel.check'))
            ->assertRedirect(route('reports.integrations.cpanel'));

        Http::assertSent(fn (ClientRequest $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), '/execute/Email/list_pops'));
        Http::assertSent(fn (ClientRequest $request): bool => $request->hasHeader(
            'Authorization',
            'cpanel test-cpanel-user:private-cpanel-token',
        ));
        Http::assertSent(fn (ClientRequest $request): bool => str_contains($request->url(), 'example.test'));

        $this->actingAs($admin)->get(route('reports.integrations.cpanel'))
            ->assertOk()
            ->assertSee('Connection successful')
            ->assertSee('Mailboxes found: 2')
            ->assertSee('team@example.test')
            ->assertSee('support@example.test')
            ->assertDontSee('private-cpanel-token');
    }

    public function test_cpanel_connection_test_reports_invalid_api_credentials(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        config([
            'services.cpanel.enabled' => true,
            'services.cpanel.host' => 'cpanel.example.test',
            'services.cpanel.port' => 2083,
            'services.cpanel.username' => 'test-cpanel-user',
            'services.cpanel.api_token' => 'invalid-token',
            'services.cpanel.email_domain' => 'example.test',
        ]);
        Http::fake([
            'https://cpanel.example.test:2083/execute/Email/list_pops*' => Http::response([], 403),
        ]);

        $this->actingAs($admin)->get(route('reports.integrations.cpanel'))->assertOk();
        $this->actingAs($admin)->post(route('reports.integrations.cpanel.check'))
            ->assertRedirect(route('reports.integrations.cpanel'));
        $this->actingAs($admin)->get(route('reports.integrations.cpanel'))
            ->assertOk()
            ->assertSee('Connection not verified')
            ->assertSee('Check the cPanel username and API token');
    }

    public function test_cpanel_connection_test_shows_safe_uapi_error_details(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        config([
            'services.cpanel.enabled' => true,
            'services.cpanel.host' => 'cpanel.example.test',
            'services.cpanel.port' => 2083,
            'services.cpanel.username' => 'test-cpanel-user',
            'services.cpanel.api_token' => 'private-cpanel-token',
            'services.cpanel.email_domain' => 'mail.omgcomputer.my',
        ]);
        Http::fake([
            'https://cpanel.example.test:2083/execute/Email/list_pops*' => Http::response([
                'data' => null,
                'errors' => ['The domain mail.omgcomputer.my is not configured for this account.'],
                'status' => 0,
            ]),
        ]);

        $this->actingAs($admin)->get(route('reports.integrations.cpanel'))->assertOk();
        $this->actingAs($admin)->post(route('reports.integrations.cpanel.check'))
            ->assertRedirect(route('reports.integrations.cpanel'));
        $this->actingAs($admin)->get(route('reports.integrations.cpanel'))
            ->assertOk()
            ->assertSee('The domain mail.omgcomputer.my is not configured for this account.')
            ->assertSee('not the mail-server hostname')
            ->assertDontSee('private-cpanel-token');
    }

    public function test_cpanel_connection_test_requires_admin_access(): void
    {
        Role::findOrCreate('member', 'web');
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)->get(route('reports.integrations.cpanel'))->assertForbidden();
        $this->actingAs($member)->post(route('reports.integrations.cpanel.check'))->assertForbidden();
    }

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

    public function test_whatsapp_permission_error_explains_required_meta_access(): void
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
                'error' => [
                    'message' => 'Application does not have permission for this action',
                    'type' => 'OAuthException',
                    'code' => 10,
                ],
            ], 403),
        ]);

        $this->actingAs($admin)->get(route('reports.integrations.whatsapp'))->assertOk();
        $this->actingAs($admin)->post(route('reports.integrations.whatsapp.check'))
            ->assertRedirect(route('reports.integrations.whatsapp'));
        $this->actingAs($admin)->get(route('reports.integrations.whatsapp'))
            ->assertOk()
            ->assertSee('Connection not verified')
            ->assertSee('whatsapp_business_management')
            ->assertSee('assign the WhatsApp Business Account and phone number');
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
            ->assertSee('Are you open today?')
            ->assertSee('Suggest a reply')
            ->assertSee('Open WhatsApp draft')
            ->assertSee('60123456789');

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

    public function test_parcel_tracker_registers_number_and_displays_17track_status(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        config([
            'services.parcel_tracking.api_key' => 'test-17track-key',
            'services.parcel_tracking.api_url' => 'https://api.17track.net',
        ]);
        Http::fakeSequence()
            ->push([
                'code' => 0,
                'data' => [
                    'accepted' => [['number' => 'MY123456789', 'carrier' => 3011]],
                    'rejected' => [],
                ],
            ])
            ->push([
                'code' => 0,
                'data' => [
                    'accepted' => [[
                        'number' => 'MY123456789',
                        'track_info' => [
                            'latest_status' => [
                                'status' => 'InTransit',
                                'sub_status' => 'InTransit_Departure',
                                'sub_status_descr' => 'Parcel departed sorting center',
                            ],
                            'latest_event' => [
                                'time_iso' => '2026-10-25T02:00:00+08:00',
                                'description' => 'Parcel departed sorting center',
                            ],
                            'tracking' => [
                                'providers' => [[
                                    'provider' => ['name' => 'J&T Express Malaysia'],
                                    'events' => [[
                                        'time_iso' => '2026-10-25T02:00:00+08:00',
                                        'location' => 'Kuala Lumpur',
                                        'description' => 'Parcel departed sorting center',
                                        'stage' => 'InTransit',
                                    ]],
                                ]],
                            ],
                        ],
                    ]],
                    'rejected' => [],
                ],
            ]);

        $this->actingAs($admin)->get(route('reports.integrations.tracking'))
            ->assertOk()
            ->assertSee('Parcel Tracker')
            ->assertSee('Tracking number')
            ->assertSee('Track parcel');
        $this->actingAs($admin)->post(route('reports.integrations.tracking.lookup'), [
            'tracking_number' => 'MY123456789',
        ])->assertRedirect(route('reports.integrations.tracking'));

        Http::assertSentCount(2);
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/track/v2.4/register')
            && $request->hasHeader('17token', 'test-17track-key')
            && $request->data() === [['number' => 'MY123456789']]);
        Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/track/v2.4/gettrackinfo')
            && $request->hasHeader('17token', 'test-17track-key')
            && $request->data() === [['number' => 'MY123456789', 'carrier' => 3011]]);

        $this->actingAs($admin)->get(route('reports.integrations.tracking'))
            ->assertOk()
            ->assertSee('Tracking result')
            ->assertSee('MY123456789')
            ->assertSee('In transit')
            ->assertSee('J&T Express Malaysia')
            ->assertSee('Kuala Lumpur')
            ->assertSee('Parcel departed sorting center')
            ->assertDontSee('test-17track-key');
    }

    public function test_customer_cannot_use_admin_integration_test_pages(): void
    {
        $customer = User::factory()->create();
        Role::findOrCreate('customer', 'web');
        $customer->assignRole('customer');

        $this->actingAs($customer)->get(route('reports.integrations.whatsapp'))->assertForbidden();
        $this->actingAs($customer)->get(route('reports.integrations.whatsapp.inbox'))->assertForbidden();
        $this->actingAs($customer)->get(route('reports.integrations.tracking'))->assertForbidden();
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
