<?php

namespace Tests\Feature;

use App\Models\MetaAdsConnection;
use App\Models\MetaDataDeletionRequest;
use App\Models\MetaPageConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetaDataDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.meta.app_secret' => 'meta-deletion-test-secret']);
    }

    public function test_public_instructions_page_shows_facebook_deletion_steps_and_callback_url(): void
    {
        $this->get(route('data-deletion.instructions'))
            ->assertOk()
            ->assertSee('Data Deletion Instructions')
            ->assertSee('Business integrations')
            ->assertSee(route('data-deletion.callback'))
            ->assertSee('Meta callback URL')
            ->assertSee(route('privacy'))
            ->assertSee('remove published posts directly on Facebook');
    }

    public function test_signed_meta_request_deletes_only_matching_connections_and_returns_status_url(): void
    {
        $user = User::factory()->create();
        MetaAdsConnection::query()->create([
            'account_id' => 'act_123456',
            'account_name' => 'Matching ad account',
            'access_token' => 'matching-ad-token',
            'meta_user_id' => '123456789',
            'connected_by_user_id' => $user->id,
        ]);
        MetaAdsConnection::query()->create([
            'account_id' => 'act_999999',
            'account_name' => 'Other ad account',
            'access_token' => 'other-ad-token',
            'meta_user_id' => '999999999',
            'connected_by_user_id' => $user->id,
        ]);
        MetaPageConnection::query()->create([
            'page_id' => '123456',
            'page_name' => 'Matching Page',
            'access_token' => 'matching-page-token',
            'meta_user_id' => '123456789',
            'connected_by_user_id' => $user->id,
        ]);
        MetaPageConnection::query()->create([
            'page_id' => '999999',
            'page_name' => 'Other Page',
            'access_token' => 'other-page-token',
            'meta_user_id' => '999999999',
            'connected_by_user_id' => $user->id,
        ]);

        $response = $this->post(route('data-deletion.callback'), [
            'signed_request' => $this->signedRequest('123456789'),
        ])->assertOk()
            ->assertJsonStructure(['url', 'confirmation_code']);

        $this->assertDatabaseCount('meta_ads_connections', 1);
        $this->assertDatabaseHas('meta_ads_connections', ['meta_user_id' => '999999999']);
        $this->assertDatabaseCount('meta_page_connections', 1);
        $this->assertDatabaseHas('meta_page_connections', ['meta_user_id' => '999999999']);
        $this->assertDatabaseCount('meta_data_deletion_requests', 1);

        $confirmationCode = $response->json('confirmation_code');
        $this->assertSame(
            route('data-deletion.status', ['confirmationCode' => $confirmationCode]),
            $response->json('url'),
        );
        $this->assertDatabaseHas('meta_data_deletion_requests', [
            'confirmation_code_hash' => hash('sha256', $confirmationCode),
        ]);
        $this->assertNotSame($confirmationCode, MetaDataDeletionRequest::query()->firstOrFail()->confirmation_code_hash);

        $this->get($response->json('url'))
            ->assertOk()
            ->assertSee('Deletion request completed')
            ->assertSee('associated with your Facebook account');
    }

    public function test_signed_meta_request_is_idempotent_and_invalid_signatures_do_not_delete_connections(): void
    {
        $user = User::factory()->create();
        MetaPageConnection::query()->create([
            'page_id' => '123456',
            'page_name' => 'Page to retain',
            'access_token' => 'page-token',
            'meta_user_id' => '123456789',
            'connected_by_user_id' => $user->id,
        ]);
        $validSignedRequest = $this->signedRequest('123456789');
        [, $signedPayload] = explode('.', $validSignedRequest, 2);

        $this->post(route('data-deletion.callback'), [
            'signed_request' => 'invalid.'.$signedPayload,
        ])->assertUnauthorized();
        $this->assertDatabaseCount('meta_page_connections', 1);

        $firstResponse = $this->post(route('data-deletion.callback'), [
            'signed_request' => $validSignedRequest,
        ])->assertOk();
        $this->post(route('data-deletion.callback'), [
            'signed_request' => $validSignedRequest,
        ])->assertOk();

        $this->assertDatabaseCount('meta_page_connections', 0);
        $this->assertDatabaseCount('meta_data_deletion_requests', 2);
        $this->get($firstResponse->json('url'))->assertOk()->assertSee('Deletion request completed');
    }

    private function signedRequest(string $metaUserId): string
    {
        $payload = rtrim(strtr(base64_encode(json_encode([
            'algorithm' => 'HMAC-SHA256',
            'user_id' => $metaUserId,
        ], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $signature = rtrim(strtr(base64_encode(hash_hmac(
            'sha256',
            $payload,
            (string) config('services.meta.app_secret'),
            true,
        )), '+/', '-_'), '=');

        return $signature.'.'.$payload;
    }
}
