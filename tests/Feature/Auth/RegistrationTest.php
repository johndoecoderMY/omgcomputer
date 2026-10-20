<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200)
            ->assertSee('Create your account')
            ->assertSee('name="email"', false)
            ->assertDontSee('password_confirmation')
            ->assertDontSee('falcon');
    }

    public function test_new_users_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('verification.notice'));
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $user = \App\Models\User::where('email', 'test@example.com')->firstOrFail();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'email_verified_at' => null,
            'force_password_change' => false,
        ]);
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
