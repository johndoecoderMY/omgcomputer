<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                'password_confirmation' => 'new-password',
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

    public function test_password_change_keeps_the_cpanel_mailbox_password_separate(): void
    {
        $user = User::factory()->create();
        $user->emailMailbox()->create([
            'email' => 'member@example.test',
            'password' => 'old-mailbox-password',
        ]);
        Http::fake();

        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertSame('old-mailbox-password', $user->fresh()->emailMailbox->password);
        Http::assertNothingSent();
    }

    public function test_account_password_cannot_be_set_to_the_mailbox_password(): void
    {
        $user = User::factory()->create();
        $user->emailMailbox()->create([
            'email' => 'member@example.test',
            'password' => 'old-mailbox-password',
        ]);
        $this->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'old-mailbox-password',
                'password_confirmation' => 'old-mailbox-password',
            ])
            ->assertSessionHasErrorsIn('updatePassword', 'password')
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertSame('old-mailbox-password', $user->fresh()->emailMailbox->password);
    }
}
