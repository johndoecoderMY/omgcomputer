<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_assign_admins_and_members_but_not_customers(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = $this->userWithRole('admin');
        $member = $this->userWithRole('member');
        $customer = $this->userWithRole('customer');
        $customer->assignRole('member');

        $this->actingAs($admin)->post(route('projects.store'), [
            'name' => 'Staff-assigned project',
            'assignees_present' => '1',
            'assignees' => [$admin->id, $member->id],
        ])->assertSessionHasNoErrors();

        $project = Project::query()->where('name', 'Staff-assigned project')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$admin->id, $member->id],
            $project->assignees()->pluck('users.id')->all(),
        );

        $this->actingAs($admin)->post(route('projects.store'), [
            'name' => 'Invalid customer assignment',
            'assignees_present' => '1',
            'assignees' => [$customer->id],
        ])->assertSessionHasErrors('assignees.0');

        $this->assertDatabaseMissing('projects', ['name' => 'Invalid customer assignment']);
    }

    public function test_members_cannot_assign_people_and_updates_without_assignment_keep_existing_assignees(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        $member = $this->userWithRole('member');
        $assignee = $this->userWithRole('member');
        $customer = Customer::query()->create([
            'name' => 'Assignment test customer',
            'email' => 'assignment@example.test',
        ]);

        $this->actingAs($member)->post(route('projects.store'), [
            'name' => 'Member project',
            'assignees_present' => '1',
            'assignees' => [$assignee->id],
        ])->assertSessionHasErrors('assignees');

        $this->actingAs($member)->post(route('tasks.store'), [
            'title' => 'Member task',
            'status' => 'backlog',
            'priority' => 'medium',
            'assignees_present' => '1',
            'assignees' => [$assignee->id],
        ])->assertSessionHasErrors('assignees');

        $this->actingAs($member)->post(route('tasks.store'), [
            'title' => 'Member assigned-to task',
            'status' => 'backlog',
            'priority' => 'medium',
            'assigned_to' => $assignee->id,
        ])->assertSessionHasErrors('assigned_to');

        $this->actingAs($member)->post(route('tickets.store'), [
            'customer_id' => $customer->id,
            'issue_description' => 'The test device needs inspection.',
            'priority' => 'normal',
            'assignees_present' => '1',
            'assignees' => [$assignee->id],
        ])->assertSessionHasErrors('assignees');

        $task = Task::query()->create([
            'title' => 'Existing assigned task',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);
        $task->assignees()->sync([$assignee->id]);
        $task->update(['assigned_to' => $assignee->id]);

        $this->actingAs($member)->put(route('tasks.update', $task), [
            'title' => 'Existing assigned task updated',
            'status' => 'in_progress',
            'priority' => 'medium',
        ])->assertSessionHasNoErrors();
        $this->assertEquals([$assignee->id], $task->fresh()->assignees()->pluck('users.id')->all());

        $ticket = RepairTicket::query()->create([
            'ticket_number' => 'RT-2026-000001',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'issue_description' => 'The test device needs inspection.',
            'status' => 'new',
            'priority' => 'normal',
            'received_at' => now(),
            'assigned_to' => $assignee->id,
        ]);
        $ticket->assignees()->sync([$assignee->id]);

        $this->actingAs($member)->put(route('tickets.update', $ticket), [
            'status' => 'diagnosing',
            'priority' => 'normal',
        ])->assertSessionHasNoErrors();
        $this->assertEquals([$assignee->id], $ticket->fresh()->assignees()->pluck('users.id')->all());

        $booking = Booking::query()->create([
            'reference' => 'BK-ASGNTEST1',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '0123456789',
            'service_id' => 'laptop-cleaning',
            'service_name' => 'Laptop cleaning',
            'starts_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'status' => 'pending',
            'assigned_to' => $assignee->id,
        ]);

        $this->actingAs($member)->put(route('bookings.update', $booking), [
            'status' => 'pending',
            'assigned_to' => $member->id,
        ])->assertSessionHasErrors('assigned_to');

        $this->actingAs($member)->put(route('bookings.update', $booking), [
            'status' => 'confirmed',
        ])->assertSessionHasNoErrors();
        $this->assertSame($assignee->id, $booking->fresh()->assigned_to);
        $this->assertSame($assignee->id, $booking->fresh()->repairTicket->assigned_to);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
