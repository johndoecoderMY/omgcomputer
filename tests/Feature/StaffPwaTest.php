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
            ->assertSee('data-staff-pwa-install', false)
            ->assertSee('assets/img/omg/apple-touch-icon.png')
            ->assertSee('assets/img/omg/favicon-32x32.png')
            ->assertSee('assets/img/omg/favicon-16x16.png')
            ->assertSee('assets/img/omg/favicon.ico');

        $this->actingAs($staff)->get(route('staff-pwa.manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');
        $manifest = json_decode(file_get_contents(resource_path('pwa/staff.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('OMG Computer Staff', $manifest['name']);
        $this->assertSame('maskable', collect($manifest['icons'])->firstWhere('purpose', 'maskable')['purpose']);
        $this->assertSame(['Bookings', 'Repair queue', 'Tasks'], array_column($manifest['shortcuts'], 'name'));
        $this->assertSame([512, 512], array_slice(getimagesize(public_path('assets/img/omg/android-chrome-maskable-512x512.png')), 0, 2));
        $this->assertFileExists(public_path('offline.html'));

        $this->actingAs($staff)->get(route('staff-pwa.service-worker'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
            ->assertHeader('Service-Worker-Allowed', '/');

        $staffWorker = file_get_contents(resource_path('pwa/staff-service-worker.js'));
        $beamsWorker = file_get_contents(public_path('service-worker.js'));
        $runtime = file_get_contents(public_path('assets/js/staff-service-worker-runtime.js'));
        $appScript = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString("importScripts('/assets/js/staff-service-worker-runtime.js')", $staffWorker);
        $this->assertStringContainsString("importScripts('https://js.pusher.com/beams/service-worker.js')", $beamsWorker);
        $this->assertStringContainsString("importScripts('/assets/js/staff-service-worker-runtime.js')", $beamsWorker);
        $this->assertStringContainsString("request.mode === 'navigate'", $runtime);
        $this->assertStringContainsString('caches.match(OFFLINE_URL)', $runtime);
        $this->assertStringContainsString('return `/build/${file}`;', $runtime);
        $this->assertStringContainsString('ACTIVATE_STAFF_PWA_UPDATE', $runtime);
        $this->assertStringContainsString("beamsInstanceId ? '/service-worker.js' : '/staff-service-worker.js'", $appScript);
        $this->assertStringContainsString("updateViaCache: 'none'", $appScript);
        $this->assertStringContainsString('In Safari, tap Share, then Add to Home Screen.', $appScript);
        $this->assertStringContainsString('void playNotificationSoundOnce(notification.id);', $appScript);
        $this->assertStringNotContainsString("if (notification.title === 'New booking received')", $appScript);
        $this->assertLessThan(
            strpos($appScript, "notificationsChannel.notification((notification) => {"),
            strpos($appScript, "window.addEventListener('beforeinstallprompt'"),
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
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');
        $this->actingAs($customer)->get(route('staff-pwa.service-worker'))
            ->assertForbidden();
    }

    public function test_guests_can_fetch_the_staff_manifest_but_not_the_service_worker(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('assets/img/omg/apple-touch-icon.png')
            ->assertSee('assets/img/omg/favicon-32x32.png')
            ->assertSee('assets/img/omg/favicon-16x16.png');

        $this->get(route('staff-pwa.manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');
        $this->get(route('staff-pwa.service-worker'))->assertRedirect(route('login'));
    }

    public function test_users_without_a_staff_role_cannot_fetch_the_staff_pwa_files(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('staff-pwa.manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');
        $this->actingAs($user)->get(route('staff-pwa.service-worker'))->assertForbidden();
    }
}
