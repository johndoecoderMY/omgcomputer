<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('<title>Computer Repair, Website Design &amp; Development | OMG Computer</title>', false)
            ->assertSee('Sign in')
            ->assertSee('Straightforward website package estimates')
            ->assertSee('RM800–1,500')
            ->assertSee('RM2,000–4,000')
            ->assertSee('From RM4,500')
            ->assertSee('illustrative estimates in Malaysian ringgit')
            ->assertSee('rel="preload"', false)
            ->assertSee('as="font" type="font/woff2" crossorigin', false)
            ->assertSee(route('register'))
            ->assertSee('href="'.route('login').'"', false)
            ->assertDontSee('Staff sign in')
            ->assertDontSee('Go to workspace')
            ->assertDontSee('<title>Computer Repair, Website Design &amp; Development - OMG Computer</title>', false);
    }

    public function test_landing_page_shows_the_signed_in_users_account_menu(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create(['name' => 'Signed In Staff']);
        $user->assignRole('admin');

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Signed In Staff')
            ->assertSee($user->email)
            ->assertSee('Email inbox')
            ->assertSee(route('email.inbox'))
            ->assertSee('Calendar')
            ->assertSee(route('calendar'))
            ->assertSee('Tasks')
            ->assertSee(route('tasks.index'))
            ->assertSee('Profile and account')
            ->assertSee(route('profile.edit'))
            ->assertSee('Log out')
            ->assertSee('action="'.route('logout').'"', false)
            ->assertDontSee('Signed in as')
            ->assertDontSee('Create account')
            ->assertDontSee('Staff sign in')
            ->assertDontSee('>Sign in</a>', false);
    }
}
