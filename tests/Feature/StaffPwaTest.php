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
        $staff = User::factory()->create();

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.asset('staff.webmanifest').'"', false)
            ->assertSee('data-staff-pwa="true"', false)
            ->assertSee('data-staff-pwa-install', false);
    }

    public function test_customer_workspace_does_not_offer_the_staff_app_install_action(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)->get(route('shop.panel'))
            ->assertOk()
            ->assertSee('data-staff-pwa="false"', false)
            ->assertDontSee('data-staff-pwa-install', false);
    }
}
