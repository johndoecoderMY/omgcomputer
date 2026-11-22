<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            $response->assertSee('Confirm new password');

            return true;
        });
    }

    public function test_password_reset_requires_matching_confirmation(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Reset-password-2026!',
            'password_confirmation' => 'Different-password-2026!',
        ])->assertSessionHasErrors('password');

        $this->assertFalse(Hash::check('Reset-password-2026!', $user->fresh()->password));
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
                'password_confirmation' => 'password',
            ]);

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('login'));

            return true;
        });
    }

    public function test_password_reset_keeps_the_cpanel_mailbox_password_separate(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->emailMailbox()->create([
            'email' => 'member@example.test',
            'password' => 'old-mailbox-password',
        ]);
        Http::fake();
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Reset-password-2026!',
            'password_confirmation' => 'Reset-password-2026!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Reset-password-2026!', $user->fresh()->password));
        $this->assertSame('old-mailbox-password', $user->fresh()->emailMailbox->password);
        Http::assertNothingSent();
    }

    public function test_password_reset_rejects_the_cpanel_mailbox_password(): void
    {
        $user = User::factory()->create();
        $user->emailMailbox()->create([
            'email' => 'member@example.test',
            'password' => 'Reset-password-2026!',
        ]);
        $token = Password::createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Reset-password-2026!',
            'password_confirmation' => 'Reset-password-2026!',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
