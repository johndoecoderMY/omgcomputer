<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_offers_the_installable_staff_app(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $staff = User::factory()->create();
        $staff->assignRole('member');

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('staff-pwa.manifest').'"', false)
            ->assertSee('data-staff-pwa="true"', false)
            ->assertSee('data-staff-pwa-install', false);

        $this->actingAs($staff)->get(route('staff-pwa.manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');
        $manifest = json_decode(file_get_contents(resource_path('pwa/staff.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('OMG Computer Staff', $manifest['name']);

        $this->actingAs($staff)->get(route('staff-pwa.service-worker'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->assertHeader('Service-Worker-Allowed', '/');
        $this->assertStringContainsString(
            'self.addEventListener',
            file_get_contents(resource_path('pwa/staff-service-worker.js')),
        );
    }

    public function test_customer_workspace_does_not_offer_the_staff_app_install_action(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)->get(route('shop.panel'))
            ->assertOk()
            ->assertSee('data-staff-pwa="false"', false)
            ->assertDontSee('data-staff-pwa-install', false)
            ->assertDontSee('staff-pwa.manifest');

        $this->actingAs($customer)->get(route('staff-pwa.manifest'))
            ->assertForbidden();
        $this->actingAs($customer)->get(route('staff-pwa.service-worker'))
            ->assertForbidden();
    }

    public function test_guests_cannot_fetch_the_staff_pwa_files(): void
    {
        $this->get(route('staff-pwa.manifest'))->assertRedirect(route('login'));
        $this->get(route('staff-pwa.service-worker'))->assertRedirect(route('login'));
    }

    public function test_users_without_a_staff_role_cannot_fetch_the_staff_pwa_files(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('staff-pwa.manifest'))->assertForbidden();
        $this->actingAs($user)->get(route('staff-pwa.service-worker'))->assertForbidden();
    }
}
