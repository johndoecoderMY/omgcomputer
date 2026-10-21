<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Project;
use App\Models\RepairTicket;
use App\Models\Task;
use App\Models\User;
use App\Notifications\WorkspaceNotification;
use Database\Seeders\PlannerRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorkspaceNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_task_assignment_notifies_the_assignee(): void
    {
        Notification::fake();
        $actor = $this->adminUser();
        $assignee = User::factory()->create();
        $assignee->assignRole('member');
        $project = Project::query()->create(['name' => 'Mentoring portal']);

        $this->actingAs($actor)
            ->post(route('projects.tasks.store', $project), [
                'title' => 'Review the student proposal',
                'status' => 'backlog',
                'priority' => 'medium',
                'assigned_to' => $assignee->id,
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Task assigned to you'
            && str_contains($notification->message, 'Review the student proposal'));
        Notification::assertNotSentTo($actor, WorkspaceNotification::class);
    }

    public function test_assigned_subtasks_notify_their_assignee(): void
    {
        Notification::fake();
        $actor = $this->adminUser();
        $assignee = User::factory()->create();
        $assignee->assignRole('member');
        $project = Project::query()->create(['name' => 'Student prototype']);
        $parent = $project->tasks()->create([
            'title' => 'Build the prototype',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $this->actingAs($actor)
            ->post(route('tasks.subtasks.store', $parent), [
                'title' => 'Test the login flow',
                'status' => 'backlog',
                'priority' => 'medium',
                'assigned_to' => $assignee->id,
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => str_contains($notification->message, 'Test the login flow'));
    }

    public function test_task_completion_notifies_creator_and_assignees_but_not_the_actor(): void
    {
        Notification::fake();
        $actor = $this->adminUser();
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $assignee->assignRole('member');
        $task = Task::query()->create([
            'title' => 'Prepare launch checklist',
            'status' => 'backlog',
            'priority' => 'medium',
            'created_by_user_id' => $creator->id,
        ]);
        $task->assignees()->sync([$actor->id, $assignee->id]);

        $this->actingAs($actor)
            ->patch(route('tasks.update-status', $task), ['done' => true])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($creator, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Task marked done');
        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Task marked done');
        Notification::assertNotSentTo($actor, WorkspaceNotification::class);
    }

    public function test_project_updates_notify_the_creator_and_assignees(): void
    {
        Notification::fake();
        $creator = $this->adminUser();
        $assignee = User::factory()->create();
        $editor = $this->adminUser();

        $this->actingAs($creator)
            ->post(route('projects.store'), [
                'name' => 'Inventory portal',
                'assignees' => [$assignee->id],
                'assignees_present' => true,
            ])
            ->assertSessionHasNoErrors();

        $project = Project::query()->firstOrFail();
        $this->assertSame($creator->id, $project->created_by_user_id);
        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Project created');

        $this->actingAs($editor)
            ->put(route('projects.update', $project), [
                'name' => 'Inventory portal refresh',
                'description' => 'A refreshed workspace.',
                'assignees' => [$assignee->id],
                'assignees_present' => true,
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($creator, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Project updated');
        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Project updated');
        Notification::assertNotSentTo($editor, WorkspaceNotification::class);
    }

    public function test_booking_updates_notify_admins_and_the_new_assignee(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        Notification::fake();
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $assignee = User::factory()->create();
        $assignee->assignRole('member');
        $customer = Customer::query()->create([
            'name' => 'Booking customer',
            'email' => 'booking-customer@example.test',
        ]);
        $booking = Booking::query()->create([
            'reference' => 'BK-NOTIFY123',
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '0123456789',
            'service_id' => 'laptop-cleaning',
            'service_name' => 'Laptop cleaning',
            'starts_at' => now()->addDays(2),
            'duration_minutes' => 60,
            'status' => 'pending',
        ]);

        $this->actingAs($actor)
            ->put(route('bookings.update', $booking), [
                'status' => 'confirmed',
                'assigned_to' => $assignee->id,
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($admin, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Booking updated');
        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Booking assigned to you');
        Notification::assertNotSentTo($actor, WorkspaceNotification::class);
    }

    public function test_ticket_creation_notifies_assignees_without_notifying_the_creator(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        Notification::fake();
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        $assignee = User::factory()->create();
        $assignee->assignRole('member');
        $customer = Customer::query()->create([
            'name' => 'Ticket customer',
            'email' => 'ticket-customer@example.test',
        ]);

        $this->actingAs($actor)
            ->post(route('tickets.store'), [
                'customer_id' => $customer->id,
                'issue_description' => 'The laptop will not start.',
                'priority' => 'normal',
                'assignees' => [$assignee->id],
                'assignees_present' => true,
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Repair ticket assigned');
        Notification::assertNotSentTo($actor, WorkspaceNotification::class);
        $this->assertSame($actor->id, RepairTicket::query()->firstOrFail()->created_by_user_id);
    }

    public function test_expense_notifications_are_limited_to_other_admins(): void
    {
        $this->seed(PlannerRoleSeeder::class);
        Notification::fake();
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($actor)
            ->post(route('expenses.store', 'rental'), [
                'name' => 'Office rent',
                'amount' => 1800,
                'frequency' => 'monthly',
                'due_date' => today()->addDays(5)->toDateString(),
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($admin, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Rental added');
        Notification::assertNotSentTo($member, WorkspaceNotification::class);
        Notification::assertNotSentTo($actor, WorkspaceNotification::class);
    }

    public function test_project_events_notify_project_task_assignees(): void
    {
        Notification::fake();
        $actor = $this->adminUser();
        $assignee = User::factory()->create();
        $assignee->assignRole('member');
        $project = Project::query()->create(['name' => 'Student FYP']);
        $project->tasks()->create([
            'assigned_to' => $assignee->id,
            'title' => 'Prepare a progress review',
            'status' => 'backlog',
            'priority' => 'medium',
        ]);

        $this->actingAs($actor)
            ->post(route('events.store'), [
                'project_id' => $project->id,
                'title' => 'FYP progress review',
                'event_type' => 'progress_review',
                'starts_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($assignee, WorkspaceNotification::class, fn (WorkspaceNotification $notification): bool => $notification->title === 'Project event scheduled'
            && str_contains($notification->message, 'FYP progress review'));
        Notification::assertNotSentTo($actor, WorkspaceNotification::class);
    }

    public function test_users_can_read_and_clear_their_notifications(): void
    {
        $user = User::factory()->create();
        $project = Project::query()->create(['name' => 'Notification test project']);
        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => WorkspaceNotification::class,
            'data' => [
                'title' => 'Task assigned to you',
                'message' => 'A task needs your attention.',
                'url' => route('projects.show', $project),
            ],
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertSee('id="unread-notifications-count" class="notification-count', false)
            ->assertSee('aria-label="1 unread notifications"', false)
            ->assertSee('Your Notifications')
            ->assertSee('notification-unread', false)
            ->assertSee('Task assigned to you');

        $this->get(route('notifications.read', $notification))
            ->assertRedirect(route('projects.show', $project));
        $this->assertNotNull($notification->fresh()->read_at);

        $unread = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => WorkspaceNotification::class,
            'data' => ['title' => 'Another update', 'message' => 'Details.'],
        ]);
        $this->post(route('notifications.read-all'))
            ->assertRedirect(route('notifications.index'));

        $this->assertNotNull($unread->fresh()->read_at);
    }

    public function test_users_cannot_read_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $notification = $owner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => WorkspaceNotification::class,
            'data' => ['title' => 'Private update', 'message' => 'Details.'],
        ]);

        $this->actingAs($otherUser)
            ->get(route('notifications.read', $notification))
            ->assertNotFound();
    }

    private function adminUser(): User
    {
        $this->seed(PlannerRoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
