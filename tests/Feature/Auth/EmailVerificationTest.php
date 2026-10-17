<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_is_not_required_for_workspace_access(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertFalse($user instanceof MustVerifyEmail);
        $this->assertFalse(app('router')->has('verification.notice'));
    }
}
