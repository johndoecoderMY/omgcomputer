<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
            $response = $this->get('/reset-password/'.$notification->token);

            $response->assertStatus(200);

            return true;
        });
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
            $response = $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_password_reset_updates_the_connected_mailbox_password(): void
    {
        Notification::fake();
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
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Reset-password-2026!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Reset-password-2026!', $user->fresh()->password));
        $this->assertSame('Reset-password-2026!', $user->fresh()->emailMailbox->password);
        Http::assertSent(fn (Request $request): bool => $request['password'] === 'Reset-password-2026!');
    }
}
