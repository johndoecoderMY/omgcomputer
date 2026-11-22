<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
        $this->get(route('password.first-login'))->assertOk()
            ->assertSee('Choose a new password')
            ->assertSee('Confirm new password');

        $this->put(route('password.first-login.update'), [
            'current_password' => 'temporary-password',
            'password' => 'A-new-safe-password-2026',
            'password_confirmation' => 'A-new-safe-password-2026',
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
                'password_confirmation' => 'A-new-safe-password-2026',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue($user->fresh()->force_password_change);
    }

    public function test_new_password_requires_matching_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => 'temporary-password',
            'force_password_change' => true,
        ]);

        $this->actingAs($user)
            ->put(route('password.first-login.update'), [
                'current_password' => 'temporary-password',
                'password' => 'A-new-safe-password-2026',
                'password_confirmation' => 'A-different-safe-password-2026',
            ])
            ->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->force_password_change);
    }

    public function test_users_without_a_temporary_password_can_use_the_workspace(): void
    {
        $user = User::factory()->create(['force_password_change' => false]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Dashboard');
    }

    public function test_first_login_password_change_keeps_the_cpanel_mailbox_password_separate(): void
    {
        $user = User::factory()->create([
            'password' => 'temporary-password',
            'force_password_change' => true,
        ]);
        $user->emailMailbox()->create([
            'email' => 'member@example.test',
            'password' => 'temporary-password',
        ]);
        $this->actingAs($user)->put(route('password.first-login.update'), [
            'current_password' => 'temporary-password',
            'password' => 'A-new-safe-password-2026',
            'password_confirmation' => 'A-new-safe-password-2026',
        ])->assertRedirect(route('dashboard'));

        $this->assertTrue(Hash::check('A-new-safe-password-2026', $user->fresh()->password));
        $this->assertSame('temporary-password', $user->fresh()->emailMailbox->password);
        $this->assertFalse(Hash::check($user->fresh()->emailMailbox->password, $user->fresh()->password));
    }
}
