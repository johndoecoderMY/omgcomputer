<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\WorkspaceNotification;
use App\Services\BeamsPushNotifier;
use App\Services\StaffPresenceTracker;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPresenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.beams.enabled' => false]);
        $this->seed(PlannerRoleSeeder::class);
    }

    public function test_staff_heartbeat_lists_active_staff_and_dashboard_shows_them(): void
    {
        $admin = User::factory()->create(['name' => 'Avery Admin']);
        $admin->assignRole('admin');
        $staff = User::factory()->create(['name' => 'Sam Staff']);
        $staff->assignRole('member');
        $customer = User::factory()->create(['name' => 'Casey Customer']);
        $customer->assignRole('customer');

        $this->actingAs($admin)
            ->postJson(route('staff.presence.update'), [
                'client_id' => 'e57b8d7c-f5a3-4f66-9e2f-a6e7c0ebfa9f',
                'active' => true,
                'realtime_ready' => true,
                'include_roster' => true,
            ])
            ->assertOk()
            ->assertJsonCount(1, 'onlineStaff')
            ->assertJsonPath('onlineStaff.0.name', 'Avery Admin')
            ->assertJsonPath('onlineStaff.0.role', 'Admin');

        $this->assertTrue(app(StaffPresenceTracker::class)->isActivelyViewing($admin));
        $this->assertFalse(app(StaffPresenceTracker::class)->isActivelyViewing($staff));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Staff currently viewing')
            ->assertSee('Avery Admin');
    }

    public function test_active_staff_keep_in_app_notifications_but_do_not_get_redundant_push(): void
    {
        config(['services.beams.enabled' => true]);
        $staff = User::factory()->create();
        $staff->assignRole('member');
        $notification = new WorkspaceNotification('Update', 'An update.', '/dashboard');
        $presence = app(StaffPresenceTracker::class);

        $presence->update($staff, 'e57b8d7c-f5a3-4f66-9e2f-a6e7c0ebfa9f', true, false);
        $this->assertSame(
            ['database', 'broadcast', BeamsPushNotifier::class],
            $notification->via($staff),
        );

        $presence->update($staff, 'e57b8d7c-f5a3-4f66-9e2f-a6e7c0ebfa9f', true, true);
        $this->assertSame(['database', 'broadcast'], $notification->via($staff));

        $presence->update($staff, 'e57b8d7c-f5a3-4f66-9e2f-a6e7c0ebfa9f', false, false);
        $this->assertSame(['database', 'broadcast', BeamsPushNotifier::class], $notification->via($staff));
    }

    public function test_customers_cannot_report_staff_presence(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $this->actingAs($customer)
            ->postJson(route('staff.presence.update'), [
                'client_id' => 'e57b8d7c-f5a3-4f66-9e2f-a6e7c0ebfa9f',
                'active' => true,
                'realtime_ready' => true,
            ])
            ->assertForbidden();
    }

    public function test_presence_heartbeat_omits_roster_unless_dashboard_requests_it(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('member');

        $this->actingAs($staff)
            ->postJson(route('staff.presence.update'), [
                'client_id' => 'e57b8d7c-f5a3-4f66-9e2f-a6e7c0ebfa9f',
                'active' => true,
                'realtime_ready' => false,
            ])
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_stale_presence_no_longer_counts_as_active(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('member');
        $presence = app(StaffPresenceTracker::class);
        $clientId = 'e57b8d7c-f5a3-4f66-9e2f-a6e7c0ebfa9f';

        $presence->update($staff, $clientId, true, true);
        $staff->presenceSessions()->firstOrFail()->update([
            'last_seen_at' => now()->subSeconds(301),
        ]);

        $this->assertFalse($presence->isActivelyViewing($staff));
    }
}
