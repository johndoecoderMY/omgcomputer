<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirstLoginPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_with_a_temporary_password_must_change_it_before_using_the_workspace(): void
    {
        $user = User::factory()->create([
            'password' => 'temporary-password',
            'force_password_change' => true,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'temporary-password',
        ])->assertRedirect(route('password.first-login'));

        $this->get(route('projects.index'))->assertRedirect(route('password.first-login'));
        $this->get(route('password.first-login'))->assertOk()->assertSee('Choose a new password');

        $this->put(route('password.first-login.update'), [
            'current_password' => 'temporary-password',
            'password' => 'A-new-safe-password-2026',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($user->fresh()->force_password_change);
        $this->assertTrue(Hash::check('A-new-safe-password-2026', $user->fresh()->password));
    }

    public function test_invalid_temporary_password_cannot_be_replaced(): void
    {
        $user = User::factory()->create([
            'password' => 'temporary-password',
            'force_password_change' => true,
        ]);

        $this->actingAs($user)
            ->put(route('password.first-login.update'), [
                'current_password' => 'wrong-current-password',
                'password' => 'A-new-safe-password-2026',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->force_password_change);
    }

    public function test_users_without_a_temporary_password_can_use_the_workspace(): void
    {
        $user = User::factory()->create(['force_password_change' => false]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Dashboard');
    }

    public function test_first_login_password_change_updates_the_connected_mailbox(): void
    {
        $user = User::factory()->create([
            'password' => 'temporary-password',
            'force_password_change' => true,
        ]);
        $user->emailMailbox()->create([
            'email' => 'member@example.test',
            'password' => 'temporary-password',
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

        $this->actingAs($user)->put(route('password.first-login.update'), [
            'current_password' => 'temporary-password',
            'password' => 'A-new-safe-password-2026',
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue(Hash::check('A-new-safe-password-2026', $user->fresh()->password));
        $this->assertSame('A-new-safe-password-2026', $user->fresh()->emailMailbox->password);
        Http::assertSent(fn (Request $request): bool => $request['password'] === 'A-new-safe-password-2026');
    }
}
