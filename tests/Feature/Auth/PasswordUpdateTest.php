<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/profile');
    }

    public function test_password_change_updates_the_connected_mailbox_password(): void
    {
        $user = User::factory()->create();
        $user->emailMailbox()->create([
            'email' => 'member@example.test',
            'password' => 'old-mailbox-password',
        ]);
        config([
            'services.cpanel.enabled' => true,
            'services.cpanel.host' => 'cpanel.example.test',
            'services.cpanel.port' => 2083,
            'services.cpanel.username' => 'cpanel-user',
            'services.cpanel.api_token' => 'private-api-token',
            'services.cpanel.email_domain' => 'example.test',
        ]);
        Http::fake([
            'https://cpanel.example.test:2083/execute/Email/passwd_pop' => Http::response([
                'result' => ['status' => 1],
            ]),
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertSame('new-password', $user->fresh()->emailMailbox->password);
        Http::assertSent(fn (Request $request): bool => $request['email'] === 'member'
            && $request['domain'] === 'example.test'
            && $request['password'] === 'new-password');
    }

    public function test_password_is_not_changed_when_mailbox_password_sync_fails(): void
    {
        $user = User::factory()->create();
        $user->emailMailbox()->create([
            'email' => 'member@example.test',
            'password' => 'old-mailbox-password',
        ]);
        config([
            'services.cpanel.enabled' => true,
            'services.cpanel.host' => 'cpanel.example.test',
            'services.cpanel.port' => 2083,
            'services.cpanel.username' => 'cpanel-user',
            'services.cpanel.api_token' => 'private-api-token',
            'services.cpanel.email_domain' => 'example.test',
        ]);
        Http::fake([
            'https://cpanel.example.test:2083/execute/Email/passwd_pop' => Http::response([
                'result' => ['status' => 0],
            ]),
        ]);

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password')
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertSame('old-mailbox-password', $user->fresh()->emailMailbox->password);
    }
}
